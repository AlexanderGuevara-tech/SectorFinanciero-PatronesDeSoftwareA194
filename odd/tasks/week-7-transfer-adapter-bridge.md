# Semana 7 — Transferencias multicanal con Adapter y Bridge

## Objetivo

Diseñar e implementar una ruta académica para transferencias bancarias donde el sistema pueda recibir operaciones desde distintos canales y consultar validadores externos simulados sin acoplar la lógica financiera a implementaciones concretas.

## Patrones principales

- **Adapter:** integrar proveedores simulados de KYC, fraude y límites mediante puertos internos del sistema.
- **Bridge:** separar los canales de transferencia de la política/procesador que valida y ejecuta la operación.

## Alcance funcional

- Modelar canales de transferencia: web, sucursal, cajero/API simulada o equivalentes según el estado real del código.
- Modelar procesadores o políticas de transferencia combinables con esos canales.
- Integrar adaptadores simulados para KYC, fraude y límites.
- Conservar las reglas financieras existentes: ledger, idempotencia, auditoría, reversos y transacción atómica.
- Agregar pruebas automatizadas que demuestren las combinaciones relevantes.

## Fuera de alcance

- Integraciones reales con proveedores externos.
- Nuevos productos financieros.
- Cambios destructivos sobre ledger o historial existente.
- Cumplimiento regulatorio real; solo simulación académica documentada.

## Criterios de aceptación

- Un canal no contiene reglas financieras complejas ni llamadas directas a infraestructura externa.
- Un procesador puede reutilizarse desde más de un canal.
- Los adaptadores traducen proveedores simulados hacia puertos internos estables.
- Una transferencia aprobada usa el flujo transaccional existente.
- Una transferencia rechazada o sospechosa registra motivo y no altera saldos ni ledger.
- Las pruebas cubren al menos una transferencia aprobada, una rechazada por KYC/límite/fraude, y una combinación canal/procesador que demuestre Bridge.

## Tareas

- [x] 1. Mapear el estado actual de transferencias, ledger, autorización, tests y convenciones.
- [x] 2. Definir el corte mínimo implementable para Semana 7 sin reabrir blockers de Semana 6 más de lo necesario.
- [x] 3. Diseñar los puertos internos para validadores simulados y los adapters concretos.
- [x] 4. Diseñar la abstracción de canal y la implementación/procesador para Bridge.
- [x] 5. Escribir pruebas RED para casos aprobados, rechazados y combinación multicanal.
- [x] 6. Implementar Adapter + Bridge manteniendo la lógica financiera fuera de controladores y modelos Eloquent.
- [x] 7. Ejecutar pruebas focalizadas, formatear PHP con Pint y actualizar evidencia.

## Riesgos

- La verificación de Semana 6 dejó pendiente evidencia MySQL/InnoDB de concurrencia; no se debe afirmar consistencia concurrente completa sin esa prueba.
- Bridge puede parecer forzado si no se demuestra al menos una variación real entre canal y procesador.
- Adapter debe representar una frontera externa simulada, no un wrapper decorativo sin valor arquitectónico.

## Evidencia esperada

- Tests automatizados.
- Diagrama o explicación breve de Adapter y Bridge.
- Rutas/clases afectadas.
- Resultado de verificación y limitaciones pendientes.

## Implementación y verificación

- Adapter: puertos `PuertoKycTransferencia`, `PuertoRiesgoTransferencia` y `PuertoLimiteTransferencia`; adaptadores deterministas `KycSimulado`, `RiesgoSimulado` y `LimiteSimulado`; `PoliticaValidacionExterna` traduce respuestas a códigos de rechazo antes de alterar saldos.
- Bridge: `CanalWeb` y `CanalSucursal` delegan en `ProcesadorTransferencia`; `ProcesadorEstandar` y `ProcesadorControlado` reutilizan `TransferirFondos`, este último añade una política dentro del mismo límite transaccional. No hay ledger ni idempotencia duplicados. El endpoint web usa el canal web.
- RED observado: dos tests fallaron por códigos/puerto faltantes; un test adicional falló por el método de identidad del canal faltante. GREEN: ambos archivos de tests unitarios pasan (4 tests, 28 aserciones).
- `php artisan test --compact tests/Unit/Application/Account tests/Feature --filter=Transfer` mediante PHP 8.3 explícito: 19 tests, 60 aserciones, OK.
- `php artisan test --compact tests/Feature/CommandLedgerTransactionsTest.php` mediante PHP 8.3 explícito: 8 tests, 41 aserciones, OK (incluye KYC/límite sin mutación de saldos, transacciones, ledger ni claves).
- `vendor/bin/pint --dirty --format agent` mediante PHP 8.3 explícito: aplicado; el comando directo no arrancó porque `php` no está en PATH de Bash.

## Estado

Implementación y validación focalizada completas; sin commit por instrucción del usuario.

## Mapeo técnico inicial

- El flujo actual existe en `TransferirFondos`, `TransferirFondosDTO`, `CadenaValidacionTransferencia`, `EspecificacionTransferencia`, `ContextoValidacionTransferencia` y especificaciones bajo `app/Application/Account/Especificaciones/`.
- El endpoint actual es `POST /accounts/transfer`, protegido por `auth` y `can:manage-accounts`, con entrada por `PeticionTransferirFondos` y `ControladorCuentas`.
- Adapter encaja naturalmente como puerto de aplicación para KYC, fraude y límites con implementaciones simuladas en infraestructura.
- Bridge todavía no existe de forma natural: para defenderlo académicamente hay que crear dos ejes reales, canal y procesador/política, sin duplicar ledger ni lógica financiera.
- La concurrencia MySQL/InnoDB de Semana 6 sigue siendo una limitación de evidencia si no se ejecuta el harness correspondiente.
- Las decisiones negativas devuelven códigos de motivo pero el esquema actual no permite persistir auditoría de rechazos o canal sin migraciones; esto queda pendiente, no se afirma trazabilidad persistente de rechazos.
- Los adapters simulados permiten todo por defecto; deben sustituirse por una política restrictiva antes de cualquier uso no académico. Una repetición idempotente aprobada conserva la respuesta original aunque cambie la política o el canal.

## Corte seleccionado

El corte confirmado es **Adapter + Bridge académico completo**:

- Adapter: puertos internos para validación simulada de KYC, fraude y límites, con adapters concretos configurables/fake.
- Bridge: separar canal de transferencia y procesador/política, demostrando al menos dos combinaciones sin duplicar el caso de uso ni el ledger.
