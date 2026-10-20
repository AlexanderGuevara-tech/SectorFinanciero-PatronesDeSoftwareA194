# Semana 9 — Facade para transferencias web

## Objetivo y problema

Exponer una operación simple de transferencia al controlador web sin obligarlo a construir el comando financiero ni a conocer el canal. Antes, `ControladorCuentas` coordinaba directamente `TransferirFondosDTO` y `CanalWeb`; esa preparación pertenece al borde de aplicación, no a la respuesta HTTP. **Facade** encaja porque `TransferenciaWebFacade` reúne los seis datos de entrada en un DTO y entrega la operación al canal existente, sin reemplazar el procesamiento.

## Responsabilidades y recorrido real

| Componente | Responsabilidad |
|---|---|
| `ControladorCuentas` | Recibe `PeticionTransferirFondos`, obtiene actor y datos validados; interpreta `ResultadoOperacion` y devuelve redirección o errores. |
| `TransferenciaWebFacade` | Construye un único `TransferirFondosDTO`, llama a `CanalWeb::transferir` y devuelve el mismo resultado. No autoriza, consulta repositorios ni escribe ledger. |
| `CanalWeb` / `CanalTransferencia` | Conservan el **Bridge**: el canal delega al contrato `ProcesadorTransferencia`. |
| `ProcesadorConTrazabilidad` / `ProcesadorEstandar` | El **Decorator** registra inicio y fin en retornos normales; el procesador estándar invoca `TransferirFondos`. El contenedor enlaza el contrato al procesador decorado. |
| `TransferirFondos` | Dentro de `UnidadDeTrabajo`, consulta idempotencia, autoriza, valida reglas internas y externas, y solo si se aprueban actualiza saldos, operación, dos líneas de ledger y clave idempotente. |

**Ruta web integrada:** `ControladorCuentas` → `TransferenciaWebFacade` → `CanalWeb` → `CanalTransferencia::transferir` → `ProcesadorConTrazabilidad` → `ProcesadorEstandar` → `TransferirFondos`. En este último, idempotencia y autorización preceden al **Composite** interno (`CadenaValidacionTransferencia` → `EspecificacionCompuestaTransferencia`: `MismaCuenta`, `CuentasExistentes`, `AlcanceDeCuentas`, `MonedaCoincidente`, `SaldoSuficiente`, en ese orden). Después se evalúa `PoliticaValidacionExterna`, que usa puertos **Adapter** KYC, riesgo y límite con implementaciones simuladas. Ante un fallo retorna un rechazo antes de las escrituras; si hay una política adicional, se consulta después de la externa. La fachada no agrega ni omite ninguna de estas etapas.

## Fragmentos del código actual

`app/Http/Controllers/ControladorCuentas.php` (dentro de `transfer`):

```php
$result = $this->transferenciaWebFacade->transferir(
    actorId: (int) auth()->id(),
    sourceAccountId: (int) $request->validated('source_account_id'),
    destinationAccountId: (int) $request->validated('destination_account_id'),
    currency: $request->validated('currency'),
    amount: $request->validated('amount'),
    requestKey: $request->validated('request_key'),
);
```

`app/Application/Account/TransferenciaWebFacade.php` (operación pública y delegación):

```php
public function transferir(
    int $actorId,
    int $sourceAccountId,
    int $destinationAccountId,
    string $currency,
    string $amount,
    string $requestKey,
): ResultadoOperacion {
    return $this->canalWeb->transferir(new TransferirFondosDTO(
        actorId: $actorId,
        sourceAccountId: $sourceAccountId,
        destinationAccountId: $destinationAccountId,
        currency: $currency,
        amount: $amount,
        requestKey: $requestKey,
    ));
}
```

`app/Application/Account/CanalTransferencia.php` (Bridge conservado):

```php
public function transferir(TransferirFondosDTO $command): ResultadoOperacion
{
    return $this->processor->ejecutar($command);
}
```

`app/Providers/AppServiceProvider.php` (composición del Decorator):

```php
$this->app->bind(ProcesadorTransferencia::class, fn ($app): ProcesadorTransferencia => new ProcesadorConTrazabilidad(
    $app->make(ProcesadorEstandar::class),
    $app->make(LoggerInterface::class),
));
```

## Verificación y límites

Evidencia verificada suministrada para la implementación: suite unitaria Account **27 tests / 84 assertions**; `OfficerLedgerRoutesTest` **4 tests / 23 assertions**; `CommandLedgerTransactionsTest` **8 tests / 41 assertions**; **Pint y `git diff --check` aprobados**. `tests/Unit/Application/Account/TransferenciaWebFacadeTest.php` comprueba el mapeo de seis entradas, delegación y conservación de resultados aprobados y rechazados. No se atribuyen estos resultados a una nueva ejecución para esta documentación.

La revisión nativa fue **abandonada por autorización explícita del usuario** luego de que dos salidas de revisores fueran rechazadas por rutas de prueba malformadas; **no se admitieron hallazgos**. Esto no constituye aprobación de la revisión ni defecto de código. No se afirma ningún commit.

Los adaptadores externos son simulados; no se afirma integración con proveedores reales ni cumplimiento regulatorio. El log de fin del decorador ocurre tras el retorno normal, no está garantizado ante excepción; no equivale a auditoría persistente. Estos diagramas representan el recorrido lógico y no verifican concurrencia MySQL/InnoDB.

## Conclusión

Facade simplifica la entrada web sin mover reglas financieras: Bridge mantiene canal y procesador separados, Decorator envuelve al procesador, Composite valida reglas internas y Adapter aísla controles externos simulados. Diagramas editables y PNG para Word: `Semana 9 - Facade UML.md`, `Semana 9 - UML Facade.png` y `Semana 9 - UML Integrado.png`.
