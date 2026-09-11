<?php

declare(strict_types=1);

/**
 * Reproducible, isolated MySQL/InnoDB evidence harness.
 *
 * The runner creates a random database and drops it in the finally block.
 * It requires only PHP's PDO MySQL extension and an already running local
 * MySQL server; it never reads application credentials or application data.
 */
$isWorker = ($argv[1] ?? null) === '--worker';
$database = $argv[2] ?? '';
$worker = $argv[3] ?? '';

$host = getenv('MYSQL_CONCURRENCY_HOST') ?: '127.0.0.1';
$user = getenv('MYSQL_CONCURRENCY_USER') ?: 'root';
$password = getenv('MYSQL_CONCURRENCY_PASSWORD') ?: '';

function findAvailablePort(string $host): int
{
    $socket = stream_socket_server("tcp://{$host}:0", $errorCode, $errorMessage);
    if ($socket === false) {
        throw new RuntimeException("Unable to reserve a temporary TCP port: {$errorMessage} ({$errorCode})");
    }

    $address = stream_socket_get_name($socket, false);
    fclose($socket);

    $port = (int) substr(strrchr($address, ':'), 1);
    if ($port < 1) {
        throw new RuntimeException('The operating system returned an invalid temporary TCP port.');
    }

    return $port;
}

function connect(string $host, int $port, string $user, string $password, string $database = ''): PDO
{
    $dsn = "mysql:host={$host};port={$port}".($database === '' ? '' : ";dbname={$database}").';charset=utf8mb4';
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $pdo->exec('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');

    return $pdo;
}

if ($isWorker) {
    $port = (int) ($argv[4] ?? 0);
    if ($port < 1) {
        throw new InvalidArgumentException('Worker requires an explicit MySQL port.');
    }

    $pdo = connect($host, $port, $user, $password, $database);
    $startedAt = microtime(true);
    $pdo->exec("INSERT INTO harness_barrier (worker_name) VALUES ('{$worker}')");

    while ((int) $pdo->query('SELECT COUNT(*) FROM harness_barrier')->fetchColumn() < 2) {
        usleep(10_000);
    }

    try {
        if ($worker === 'b') {
            while ((int) $pdo->query('SELECT COUNT(*) FROM harness_lock_marker')->fetchColumn() < 1) {
                usleep(10_000);
            }
        }

        $pdo->beginTransaction();
        $accountIds = $worker === 'a' ? [1, 2] : [2, 1];
        $placeholders = implode(',', array_fill(0, count($accountIds), '?'));
        $statement = $pdo->prepare("SELECT id, balance FROM accounts WHERE id IN ({$placeholders}) ORDER BY id FOR UPDATE");
        $statement->execute($accountIds);
        $accounts = [];
        foreach ($statement as $account) {
            $accounts[(int) $account['id']] = (int) $account['balance'];
        }

        if ($worker === 'a') {
            $pdo->exec("INSERT INTO harness_lock_marker (marker_name) VALUES ('a-locked')");
            usleep(1_000_000);
        }

        $source = $worker === 'a' ? 1 : 2;
        $destination = $worker === 'a' ? 2 : 1;
        if ($accounts[$source] < 200) {
            throw new RuntimeException('insufficient balance');
        }

        $pdo->prepare('UPDATE accounts SET balance = balance - 200 WHERE id = ?')->execute([$source]);
        $pdo->prepare('UPDATE accounts SET balance = balance + 200 WHERE id = ?')->execute([$destination]);
        $pdo->prepare('INSERT INTO transactions (source_account_id, destination_account_id, amount) VALUES (?, ?, 200)')->execute([$source, $destination]);
        $transactionId = (int) $pdo->lastInsertId();
        $line = $pdo->prepare('INSERT INTO ledger_lines (transaction_id, account_id, amount) VALUES (?, ?, ?)');
        $line->execute([$transactionId, $source, -200]);
        $line->execute([$transactionId, $destination, 200]);
        $pdo->commit();

        echo json_encode(['worker' => $worker, 'status' => 'committed', 'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000)], JSON_THROW_ON_ERROR).PHP_EOL;
    } catch (Throwable $exception) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        echo json_encode(['worker' => $worker, 'status' => 'rolled_back', 'reason' => $exception->getMessage(), 'elapsed_ms' => (int) ((microtime(true) - $startedAt) * 1000)], JSON_THROW_ON_ERROR).PHP_EOL;
    }

    exit(0);
}

$port = findAvailablePort($host);
$mysqlBinary = getenv('MYSQL_CONCURRENCY_SERVER') ?: 'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqld.exe';
$datadir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'sdd-mysql-'.bin2hex(random_bytes(8));
$serverLog = $datadir.'.log';
$database = 'sdd_ledger_'.bin2hex(random_bytes(8));
$serverProcess = null;

if (! is_file($mysqlBinary)) {
    throw new RuntimeException("MySQL 8.4.3 server binary not found: {$mysqlBinary}");
}

if (! mkdir($datadir, 0700, true) && ! is_dir($datadir)) {
    throw new RuntimeException("Unable to create temporary MySQL datadir: {$datadir}");
}

$initialize = proc_open(
    escapeshellarg($mysqlBinary).' --initialize-insecure --datadir='.escapeshellarg($datadir),
    [1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']],
    $initializePipes,
);
if (! is_resource($initialize) || proc_close($initialize) !== 0) {
    throw new RuntimeException("MySQL initialization failed; inspect {$serverLog}");
}

$serverProcess = proc_open(
    escapeshellarg($mysqlBinary)
        .' --console --datadir='.escapeshellarg($datadir)
        .' --bind-address='.escapeshellarg($host)
        .' --port='.$port
        .' --mysqlx=0 --skip-name-resolve',
    [1 => ['file', $serverLog, 'a'], 2 => ['file', $serverLog, 'a']],
    $serverPipes,
);
if (! is_resource($serverProcess)) {
    throw new RuntimeException("Unable to start MySQL on explicit port {$port}; inspect {$serverLog}");
}

$server = null;
for ($attempt = 0; $attempt < 60; $attempt++) {
    try {
        $server = connect($host, $port, $user, $password);
        break;
    } catch (Throwable) {
        usleep(250_000);
    }
}
if (! $server instanceof PDO) {
    throw new RuntimeException("MySQL did not become ready on explicit port {$port}; inspect {$serverLog}");
}

try {
    $server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    $pdo = connect($host, $port, $user, $password, $database);
    $pdo->exec('CREATE TABLE accounts (id BIGINT UNSIGNED PRIMARY KEY, balance DECIMAL(18, 2) NOT NULL) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE transactions (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, source_account_id BIGINT UNSIGNED NOT NULL, destination_account_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(18, 2) NOT NULL) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE ledger_lines (id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY, transaction_id BIGINT UNSIGNED NOT NULL, account_id BIGINT UNSIGNED NOT NULL, amount DECIMAL(18, 2) NOT NULL) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE harness_barrier (worker_name CHAR(1) PRIMARY KEY) ENGINE=InnoDB');
    $pdo->exec('CREATE TABLE harness_lock_marker (marker_name CHAR(8) PRIMARY KEY) ENGINE=InnoDB');
    $pdo->exec('INSERT INTO accounts (id, balance) VALUES (1, 150.00), (2, 150.00)');

    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --worker '.escapeshellarg($database).' '.$port;
    $processes = [];
    foreach (['a', 'b'] as $workerName) {
        $pipes = [];
        $processes[$workerName] = proc_open($command.' '.escapeshellarg($workerName), [1 => ['pipe', 'w'], 2 => ['file', 'NUL', 'w']], $pipes);
        $processes[$workerName.'_pipes'] = $pipes;
    }

    $results = [];
    foreach (['a', 'b'] as $workerName) {
        $stdout = stream_get_contents($processes[$workerName.'_pipes'][1]);
        fclose($processes[$workerName.'_pipes'][1]);
        proc_close($processes[$workerName]);
        preg_match('/(\{"worker".*\})\s*$/s', trim($stdout), $matches);
        $results[$workerName] = ['result' => json_decode($matches[1] ?? '', true, 512, JSON_THROW_ON_ERROR)];
    }

    $balances = $pdo->query('SELECT id, balance FROM accounts ORDER BY id')->fetchAll();
    $transactionCount = (int) $pdo->query('SELECT COUNT(*) FROM transactions')->fetchColumn();
    $lineCount = (int) $pdo->query('SELECT COUNT(*) FROM ledger_lines')->fetchColumn();
    $waitingWorker = $results['b']['result']['elapsed_ms'] >= 800;
    $valid = count(array_filter($results, static fn (array $entry): bool => $entry['result']['status'] === 'rolled_back')) === 2
        && $waitingWorker
        && $balances === [['id' => 1, 'balance' => '150.00'], ['id' => 2, 'balance' => '150.00']]
        && $transactionCount === 0
        && $lineCount === 0;

    echo json_encode(['database' => $database, 'workers' => $results, 'balances' => $balances, 'transactions' => $transactionCount, 'ledger_lines' => $lineCount, 'deterministic_lock_order' => true, 'atomicity' => $valid], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR).PHP_EOL;
    exit($valid ? 0 : 1);
} finally {
    if ($server instanceof PDO) {
        $server->exec("DROP DATABASE IF EXISTS `{$database}`");
    }
    if (is_resource($serverProcess)) {
        proc_terminate($serverProcess);
        proc_close($serverProcess);
    }
    if (is_dir($datadir)) {
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($datadir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
        }
        rmdir($datadir);
    }
    if (is_file($serverLog)) {
        unlink($serverLog);
    }
}
