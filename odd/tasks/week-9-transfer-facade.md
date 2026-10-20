# Semana 9 — Fachada web de transferencias

## Objetivo

Aplicar el patrón **Facade** para ofrecer al controlador web una operación simple de transferencia, conservando el flujo existente de canales, procesadores, validaciones, autorización, idempotencia, transacción y ledger.

## Patrón principal

- **Facade:** `TransferenciaWebFacade` encapsulará la creación de `TransferirFondosDTO` y la delegación al `CanalWeb` existente.

## Decisión de diseño

- La fachada se ubicará entre `ControladorCuentas` y `CanalWeb`.
- El controlador conservará validación HTTP, obtención del actor y construcción de la respuesta.
- La fachada no contendrá reglas financieras ni accederá a repositorios.
- `TransferirFondos` seguirá siendo responsable de autorización, idempotencia, validaciones, transacción, saldos y ledger.
- Adapter, Bridge, Composite y Decorator permanecerán visibles y sin reemplazos.

## Flujo esperado

```text
ControladorCuentas
        ↓
TransferenciaWebFacade
        ↓
CanalWeb (Bridge)
        ↓
ProcesadorTransferencia decorado
        ↓
TransferirFondos
        ↓
Composite + Adapter + transacción + ledger
```

## Criterios de aceptación

- La fachada expone una operación clara con los datos necesarios para transferir.
- La fachada construye el DTO una sola vez y delega al canal web.
- El controlador deja de conocer `TransferirFondosDTO` y `CanalWeb` directamente.
- El resultado y las respuestas HTTP existentes no cambian.
- La fachada no duplica reglas financieras, autorización, idempotencia ni persistencia.
- Las pruebas demuestran la delegación y preservan las regresiones de transferencias y ledger.

## Tareas

- [x] 1. Explorar el flujo actual y seleccionar la ubicación de Facade.
- [x] 2. Escribir pruebas RED para construcción del comando, delegación y retorno del resultado.
- [x] 3. Implementar `TransferenciaWebFacade`.
- [x] 4. Integrar la fachada en `ControladorCuentas` sin cambiar respuestas HTTP.
- [x] 5. Ejecutar pruebas focalizadas, regresiones de patrones anteriores y Pint.
- [x] 6. Preparar documentación y UML de Semana 9 si se solicita.

## Riesgos

- Crear un wrapper ceremonial que no simplifique al cliente.
- Mover reglas financieras al controlador o a la fachada.
- Ocultar el Bridge en lugar de reutilizar `CanalWeb`.
- Cambiar accidentalmente respuestas, códigos de fallo o semántica idempotente.

## Evidencia esperada

- Pruebas unitarias de la fachada.
- Pruebas feature del endpoint y ledger sin regresiones.
- Rutas exactas de clases y explicación del flujo integrado.

## Evidencia de implementación

- `TransferenciaWebFacade` construye un único `TransferirFondosDTO`, delega a `CanalWeb` y conserva la instancia de `ResultadoOperacion`.
- `ControladorCuentas` ya no construye el DTO ni depende directamente del canal; conserva validación, actor, errores, redirección y mensaje exitoso.
- RED observado: dos pruebas fallaron por ausencia de `TransferenciaWebFacade`.
- GREEN focalizado: 2 pruebas y 11 aserciones de la fachada.
- Regresión inicial: `OfficerLedgerRoutesTest.php` pasó con 4 pruebas y 23 aserciones.
- Pint pasó sobre los tres archivos PHP de Semana 9.
- Verificación independiente: suite unitaria Account con 27 pruebas y 84 aserciones; rutas de ledger con 4 pruebas y 23 aserciones; transacciones con 8 pruebas y 41 aserciones.
- `git diff --check` pasó sin errores.
- Documentación: `Semana 9 - Facade.md` y `Semana 9 - Facade UML.md` incluyen objetivo, evidencia de código, Mermaid, pruebas y limitaciones.
- UML para Word: `Semana 9 - UML Facade.png` (3200 × 1880) y `Semana 9 - UML Integrado.png` (3300 × 2380), ambos validados con Pillow.
- Revisión nativa: modelo configurado como `openai-codex/gpt-5.6-sol` con esfuerzo alto; dos salidas fueron rechazadas por rutas de prueba malformadas, sin hallazgos admitidos, y la revisión fue abandonada por autorización explícita del usuario.

## Estado

Facade implementada, integrada, formateada, verificada y documentada. Sin commit porque no fue solicitado.
