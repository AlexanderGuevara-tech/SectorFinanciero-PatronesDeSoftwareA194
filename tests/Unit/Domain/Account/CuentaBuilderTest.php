<?php

namespace Tests\Unit\Domain\Account;

use App\Domain\Account\CuentaAhorro;
use App\Domain\Account\CuentaBuilder;
use App\Domain\Account\CuentaCorriente;
use App\Domain\Account\EstadoCuenta;
use App\Domain\Account\Moneda;
use App\Domain\Account\PaqueteCuenta;
use App\Domain\Account\PoliticaComisionPorFamilia;
use App\Domain\Account\PoliticaSobregiroPorFamilia;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class CuentaBuilderTest extends TestCase
{
    #[Test]
    public function test_builds_a_complete_account_from_all_constructor_values(): void
    {
        $product = new CuentaCorriente('empresarial');
        $package = new PaqueteCuenta(
            familia: 'empresarial',
            cuenta: $product,
            comision: new PoliticaComisionPorFamilia('empresarial'),
            sobregiro: new PoliticaSobregiroPorFamilia('empresarial'),
        );

        $account = (new CuentaBuilder)
            ->withBalance('125.50')
            ->withCurrency(new Moneda('USD'))
            ->withStatus(EstadoCuenta::Bloqueada)
            ->withType('checking')
            ->forCustomer(7)
            ->operatedBy(11)
            ->withProduct($product)
            ->withFamily('empresarial')
            ->withPackage($package)
            ->withId(19)
            ->build();

        $this->assertSame('125.50', $account->saldo());
        $this->assertSame('USD', $account->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Bloqueada, $account->estado());
        $this->assertSame('checking', $account->tipo());
        $this->assertSame(7, $account->customerId());
        $this->assertSame(11, $account->operadoPorId());
        $this->assertSame($product, $account->producto());
        $this->assertSame('empresarial', $account->familia());
        $this->assertSame($package, $account->paquete());
        $this->assertSame(19, $account->id());
    }

    #[Test]
    public function test_uses_safe_defaults_for_optional_account_values(): void
    {
        $account = (new CuentaBuilder)
            ->withType('savings')
            ->forCustomer(7)
            ->withProduct(new CuentaAhorro)
            ->build();

        $this->assertSame('0', $account->saldo());
        $this->assertSame('COP', $account->moneda()->codigo());
        $this->assertSame(EstadoCuenta::Activa, $account->estado());
        $this->assertSame('personal', $account->familia());
        $this->assertSame('personal', $account->paquete()->familia);
        $this->assertSame(null, $account->operadoPorId());
        $this->assertSame(null, $account->id());
    }

    #[Test]
    public function test_rejects_building_without_required_values(): void
    {
        $builder = (new CuentaBuilder)->forCustomer(7)->withProduct(new CuentaAhorro);

        $this->expectExceptionMessage('An account type is required.');

        $builder->build();
    }

    #[Test]
    public function test_rejects_building_without_a_customer(): void
    {
        $builder = (new CuentaBuilder)
            ->withType('savings')
            ->withProduct(new CuentaAhorro);

        $this->expectExceptionMessage('A customer is required.');

        $builder->build();
    }

    #[Test]
    public function test_rejects_building_without_a_product(): void
    {
        $builder = (new CuentaBuilder)
            ->withType('savings')
            ->forCustomer(7);

        $this->expectExceptionMessage('An account product is required.');

        $builder->build();
    }

    #[Test]
    public function test_rejects_a_package_that_does_not_match_the_family_or_product(): void
    {
        $package = new PaqueteCuenta(
            familia: 'personal',
            cuenta: new CuentaAhorro('personal'),
            comision: new PoliticaComisionPorFamilia('personal'),
            sobregiro: new PoliticaSobregiroPorFamilia('personal'),
        );

        $this->expectExceptionMessage('Account package does not match account family or product.');

        (new CuentaBuilder)
            ->withType('checking')
            ->forCustomer(7)
            ->withProduct(new CuentaCorriente('empresarial'))
            ->withFamily('empresarial')
            ->withPackage($package)
            ->build();
    }
}
