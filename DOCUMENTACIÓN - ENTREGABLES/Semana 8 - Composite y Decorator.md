# Semana 8 — Transferencias con Composite y Decorator

## Resumen y objetivo

La Semana 8 agrega **Composite** para ordenar las validaciones internas de transferencias y **Decorator** para registrar el inicio y el fin del procesamiento sin modificar el procesador concreto. Son extensiones del flujo existente: **Adapter** sigue aislando los controles externos simulados y **Bridge** sigue separando canal y procesador.

## Problema abordado

La transferencia debe detenerse ante la primera regla interna incumplida, antes de afectar saldos o ledger. A la vez, se necesita trazabilidad del procesamiento sin copiar la ejecución financiera en cada canal ni incluir datos sensibles del comando en los mensajes de log.

## Patrones y límites de responsabilidad

| Patrón | Responsabilidad verificada |
|---|---|
| Composite | `EspecificacionTransferencia` define `fallo(contexto)`; las cinco especificaciones concretas y `EspecificacionCompuestaTransferencia` comparten ese contrato. El compuesto recorre hijos en orden y devuelve el primer fallo o `null`. |
| Decorator | `ProcesadorTransferenciaDecorator` implementa `ProcesadorTransferencia` y delega en otro procesador; `ProcesadorConTrazabilidad` registra `transfer.processing.started`, ejecuta el delegado y registra `transfer.processing.finished`. |
| Adapter (conservado) | `PoliticaValidacionExterna` utiliza los puertos `PuertoKycTransferencia`, `PuertoRiesgoTransferencia` y `PuertoLimiteTransferencia`, enlazados a adaptadores simulados. No es un hijo del Composite: la frontera externa permanece separada. |
| Bridge (conservado) | `CanalTransferencia` conserva la referencia intercambiable a `ProcesadorTransferencia`; `CanalWeb` y `CanalSucursal` pueden usar procesadores concretos o decorados. Decorator envuelve al procesador, no reemplaza la separación canal/procesador. |

## Flujo integrado

```text
CanalWeb / CanalSucursal (Bridge)
  → ProcesadorTransferencia → ProcesadorConTrazabilidad (Decorator)
  → ProcesadorEstandar → TransferirFondos
  → idempotencia y autorización
  → CadenaValidacionTransferencia → EspecificacionCompuestaTransferencia
     → MismaCuenta → CuentasExistentes → AlcanceDeCuentas
     → MonedaCoincidente → SaldoSuficiente (Composite interno)
  → PoliticaValidacionExterna → puertos KYC / riesgo / límite (Adapter externo)
  → si se aprueba: saldos, operación, dos líneas de ledger e idempotencia
```

`TransferirFondos` ejecuta el proceso dentro de `UnidadDeTrabajo`: consulta idempotencia y autorización antes del compuesto; luego evalúa la política externa y, si existe, la política adicional. Un fallo retorna `ResultadoOperacion::rejected` antes de las escrituras de saldos, operación, ledger y clave idempotente. `ProcesadorControlado` puede pasar una política adicional; no todos los canales pasan necesariamente por él. Los mensajes del decorador no incluyen el DTO ni contexto en la implementación actual; el mensaje final se registra después del retorno normal del delegado, no en un bloque `finally`.

## Evidencia exacta en código

| Evidencia | Ruta |
|---|---|
| Contrato, compuesto, cadena y contexto con carga de cuentas bloqueadas | `app/Application/Account/EspecificacionTransferencia.php`, `app/Application/Account/EspecificacionCompuestaTransferencia.php`, `app/Application/Account/CadenaValidacionTransferencia.php`, `app/Application/Account/ContextoValidacionTransferencia.php` |
| Hojas internas: misma cuenta, cuentas existentes, alcance, moneda y saldo | `app/Application/Account/Especificaciones/MismaCuenta.php`, `app/Application/Account/Especificaciones/CuentasExistentes.php`, `app/Application/Account/Especificaciones/AlcanceDeCuentas.php`, `app/Application/Account/Especificaciones/MonedaCoincidente.php`, `app/Application/Account/Especificaciones/SaldoSuficiente.php` |
| Envoltorio y registro antes/después de delegar | `app/Application/Account/ProcesadorTransferenciaDecorator.php`, `app/Application/Account/ProcesadorConTrazabilidad.php` |
| Contrato de procesador, Bridge y composición del contenedor | `app/Application/Account/ProcesadorTransferencia.php`, `app/Application/Account/CanalTransferencia.php`, `app/Application/Account/CanalWeb.php`, `app/Application/Account/CanalSucursal.php`, `app/Providers/AppServiceProvider.php` |
| Caso de uso, procesadores y frontera externa | `app/Application/Account/TransferirFondos.php`, `app/Application/Account/ProcesadorEstandar.php`, `app/Application/Account/ProcesadorControlado.php`, `app/Application/Account/PoliticaValidacionExterna.php` |

## Evidencia de pruebas y comprobaciones

- `tests/Unit/Application/Account/TransferenciaValidationTest.php`: hojas, orden, primer fallo y cortocircuito del compuesto y la cadena.
- `tests/Unit/Application/Account/TransferProcessorTest.php`: identidad de comando/resultado, secuencia de logs sin payload, resolución del procesador decorado y transferencias aprobadas/rechazadas.
- `tests/Feature/CommandLedgerTransactionsTest.php`: doble partida, reglas rechazadas sin cambios, controles simulados e idempotencia, entre otros escenarios.
- Evidencia verificada suministrada para esta entrega: suite unitaria Account **25 tests / 73 assertions**; `CommandLedgerTransactionsTest` **8 tests / 41 assertions**; **Pint y comprobación de diff aprobados**. Estos resultados describen las comprobaciones reportadas, no certifican integración con proveedores reales ni cumplimiento normativo.

## Limitaciones

- KYC, riesgo y límites usan adaptadores simulados; no hay integración externa real ni afirmación de cumplimiento regulatorio.
- El decorador registra inicio y fin del retorno normal; no se verificó un log de fin cuando el delegado lanza una excepción, ni se afirma auditoría persistente.
- Los diagramas muestran relaciones de clases y el recorrido principal, no una demostración de concurrencia MySQL/InnoDB.

## Conclusión

Composite hace explícito y cortocircuitable el conjunto de reglas **internas**. Decorator añade trazabilidad alrededor de un procesador sin alterar su resultado normal. Ambos se suman al Adapter de validación **externa** y al Bridge de **canales y procesadores**, mientras `TransferirFondos` mantiene el flujo transaccional existente. Los diagramas se detallan en `Semana 8 - Composite y Decorator UML.md`.
