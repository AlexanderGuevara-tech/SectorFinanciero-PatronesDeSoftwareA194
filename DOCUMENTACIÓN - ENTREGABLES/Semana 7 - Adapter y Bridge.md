# Semana 7 — Transferencias multicanal con Adapter y Bridge

## Resumen

En la Semana 7 se diseñó una extensión académica del flujo de transferencias bancarias para demostrar dos patrones estructurales: **Adapter** y **Bridge**. El objetivo fue permitir que una transferencia pueda recibirse desde distintos canales y pasar por validadores externos simulados, sin acoplar el núcleo financiero a implementaciones concretas ni duplicar la lógica de ledger, saldos o idempotencia.

## Objetivo de la semana

Diseñar una solución de software para transferencias bancarias multicanal que mantenga la seguridad, consistencia y trazabilidad del sistema, aplicando patrones que favorezcan mantenibilidad, escalabilidad e integración con controles externos simulados.

## Problema abordado

Un sistema bancario no debe ejecutar una transferencia directamente desde un controlador o canal específico. Antes de afectar saldos o registrar movimientos contables, la operación debe validar reglas como:

- estado del cliente frente a KYC;
- riesgo de fraude;
- límites de transferencia;
- permisos del usuario;
- consistencia de cuentas y saldos;
- idempotencia;
- registro contable en ledger.

Además, el sistema debe poder evolucionar hacia distintos canales de atención, como web, sucursal, cajero automático, aplicación móvil o API externa, sin duplicar reglas financieras en cada canal.

## Patrones aplicados

| Patrón | Propósito en la solución |
|---|---|
| Adapter | Desacoplar el sistema de validadores externos simulados como KYC, fraude y límites. |
| Bridge | Separar los canales de transferencia de los procesadores o políticas de ejecución. |

## Aplicación del patrón Adapter

El patrón **Adapter** se aplicó creando puertos internos que representan las necesidades del sistema bancario, sin depender de proveedores concretos.

### Puertos internos

```text
PuertoKycTransferencia
PuertoRiesgoTransferencia
PuertoLimiteTransferencia
```

### Adaptadores simulados

```text
KycSimulado
RiesgoSimulado
LimiteSimulado
```

Estos adaptadores permiten simular respuestas externas, por ejemplo:

- cliente rechazado por KYC;
- operación sospechosa por fraude;
- monto superior al límite permitido.

La lógica principal de transferencia no conoce los detalles de esos proveedores simulados. Solo consume los puertos definidos por la aplicación.

## Aplicación del patrón Bridge

El patrón **Bridge** se aplicó separando dos dimensiones que pueden variar de manera independiente:

1. el canal desde donde se origina la transferencia;
2. el procesador o política que decide cómo ejecutarla.

### Canales

```text
CanalWeb
CanalSucursal
```

### Procesadores

```text
ProcesadorEstandar
ProcesadorControlado
```

De esta manera se evita crear una clase por cada combinación posible, como:

```text
TransferenciaWebEstandar
TransferenciaWebControlada
TransferenciaSucursalEstandar
TransferenciaSucursalControlada
```

En cambio, cada canal delega la operación en un procesador intercambiable.

## Flujo resultante

```text
Canal de transferencia
        ↓
Procesador de transferencia
        ↓
Caso de uso TransferirFondos
        ↓
Validaciones internas y externas simuladas
        ↓
Unidad de trabajo transaccional
        ↓
Ledger e idempotencia
```

El canal no contiene reglas financieras complejas. El procesador tampoco duplica ledger ni manejo de saldos. Ambos reutilizan el flujo seguro existente.

## Evidencia en código

### Aplicación

```text
app/Application/Account/CanalTransferencia.php
app/Application/Account/CanalWeb.php
app/Application/Account/CanalSucursal.php
app/Application/Account/ProcesadorTransferencia.php
app/Application/Account/ProcesadorEstandar.php
app/Application/Account/ProcesadorControlado.php
app/Application/Account/PoliticaValidacionExterna.php
app/Application/Account/PuertoKycTransferencia.php
app/Application/Account/PuertoRiesgoTransferencia.php
app/Application/Account/PuertoLimiteTransferencia.php
```

### Infraestructura

```text
app/Infrastructure/Account/KycSimulado.php
app/Infrastructure/Account/RiesgoSimulado.php
app/Infrastructure/Account/LimiteSimulado.php
```

### Pruebas

```text
tests/Unit/Application/Account/TransferChannelTest.php
tests/Unit/Application/Account/TransferProcessorTest.php
tests/Feature/CommandLedgerTransactionsTest.php
```

## Validación realizada

Se ejecutaron pruebas automatizadas focalizadas sobre el flujo de transferencia.

```text
20 tests, 68 assertions — OK
8 tests, 41 assertions — OK
```

También se aplicó formato con Laravel Pint.

## Relación con los objetivos del proyecto

| Objetivo | Aporte de la Semana 7 |
|---|---|
| Analizar requerimientos funcionales y no funcionales | Se modelaron validaciones previas a una operación financiera sensible. |
| Seleccionar patrones adecuados | Se aplicaron Adapter y Bridge para desacoplamiento, mantenibilidad y escalabilidad. |
| Integrar diferentes canales de atención | Bridge permite separar canales como web o sucursal de la lógica de procesamiento. |
| Incorporar KYC, fraude y trazabilidad | Adapter permite simular validadores externos de KYC, riesgo y límites. |

## Limitaciones

- Los proveedores son simulados; no representan integraciones reales.
- No se afirma cumplimiento regulatorio real.
- No se agregó auditoría persistente de rechazos por canal, porque requiere cambios de base de datos.
- La evidencia de concurrencia MySQL/InnoDB de la Semana 6 continúa pendiente.

## Conclusión

La Semana 7 demuestra cómo aplicar **Adapter** y **Bridge** en un contexto bancario realista. Adapter permite integrar validadores externos simulados sin acoplar el dominio a proveedores concretos. Bridge permite separar canales de transferencia y procesadores de ejecución, evitando duplicación de clases y preparando el sistema para crecer hacia canales como web, sucursal, cajero, móvil o API externa.

La solución conserva el flujo financiero existente: las transferencias aprobadas siguen utilizando la unidad de trabajo transaccional, ledger de doble partida e idempotencia. Las operaciones rechazadas no alteran saldos ni generan movimientos contables.
