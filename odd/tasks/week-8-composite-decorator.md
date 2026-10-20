# Semana 8 — Validaciones compuestas y procesadores decorados

## Objetivo

Extender de forma aditiva el flujo académico de transferencias con los patrones **Composite** y **Decorator**, conservando íntegramente las implementaciones demostrables de **Adapter** y **Bridge** de la Semana 7.

## Patrones principales

- **Composite:** tratar una especificación individual y un conjunto ordenado de especificaciones mediante el mismo contrato `EspecificacionTransferencia`.
- **Decorator:** envolver cualquier `ProcesadorTransferencia` para agregar comportamiento transversal sin modificar el procesador concreto ni la abstracción de canal.

## Decisión de diseño

- El Composite agrupará las especificaciones internas existentes y conservará el orden y cortocircuito al primer fallo.
- El Decorator envolverá la interfaz existente `ProcesadorTransferencia` y agregará trazabilidad segura alrededor de la ejecución.
- Los puertos y adaptadores externos de KYC, riesgo y límites permanecerán como evidencia independiente de Adapter.
- Los canales y procesadores concretos permanecerán como evidencia independiente de Bridge.
- Ninguna clase de patrón anterior será eliminada, renombrada o reemplazada.

## Alcance funcional

- Incorporar una especificación compuesta compatible con las especificaciones existentes.
- Integrar el Composite al flujo real sin cambiar las reglas financieras ni su precedencia.
- Incorporar un decorador de procesador con comportamiento observable y verificable.
- Demostrar que el decorador puede envolver procesadores estándar o controlados.
- Mantener ledger, idempotencia, autorización y transacción atómica en el caso de uso central.
- Preparar evidencia documental y UML donde convivan Adapter, Bridge, Composite y Decorator.

## Fuera de alcance

- Reemplazar Adapter o Bridge.
- Integraciones reales con proveedores externos.
- Cambios de esquema o auditoría persistente nueva.
- Nuevos productos financieros.
- Modificaciones destructivas sobre ledger, saldos o historial.

## Criterios de aceptación

- Una especificación individual y una compuesta implementan el mismo contrato.
- El Composite evalúa sus elementos en orden, devuelve el primer fallo y no ejecuta elementos posteriores.
- Las validaciones exitosas continúan hasta completar el conjunto.
- El Decorator implementa `ProcesadorTransferencia`, delega el mismo DTO y conserva el resultado del procesador envuelto.
- El comportamiento adicional del Decorator es observable mediante pruebas sin duplicar la lógica de transferencia.
- Las pruebas existentes de Adapter y Bridge continúan pasando sin modificar sus contratos.
- La documentación identifica claramente la responsabilidad y ubicación de los cuatro patrones.

## Tareas

- [x] 1. Mapear la arquitectura actual, los puntos de extensión y las pruebas relevantes.
- [x] 2. Escribir pruebas RED para el Composite: éxito, orden, primer fallo y cortocircuito.
- [x] 3. Implementar e integrar el Composite sin eliminar la cadena ni las especificaciones existentes.
- [x] 4. Escribir pruebas RED para el Decorator: delegación, resultado y comportamiento adicional.
- [x] 5. Implementar el Decorator manteniendo intactos los procesadores y canales existentes.
- [x] 6. Ejecutar pruebas focalizadas, verificar regresiones de Semana 7 y aplicar Pint.
- [x] 7. Preparar documentación y diagramas UML con evidencia de los cuatro patrones.

## Riesgos

- Duplicar la responsabilidad de `CadenaValidacionTransferencia` en lugar de integrarla explícitamente con el Composite.
- Mezclar las validaciones externas basadas en DTO con las especificaciones internas basadas en contexto y ocultar el Adapter existente.
- Activar un decorador mediante wiring opaco y dificultar la demostración del Bridge original.
- Registrar datos sensibles en la trazabilidad del Decorator.

## Evidencia esperada

- Pruebas automatizadas del Composite y Decorator.
- Pruebas de regresión de Adapter y Bridge.
- Diagrama UML de cada patrón y un diagrama integrado del flujo.
- Fragmentos de código con rutas y explicaciones breves para el documento Word.
- Resultados de pruebas y limitaciones pendientes.

## Evidencia de implementación

- Composite: `EspecificacionCompuestaTransferencia` implementa el contrato existente, evalúa hijos en orden y corta ante el primer fallo.
- Integración: `CadenaValidacionTransferencia` permanece en la ruta real y delega la evaluación al Composite.
- RED observado: tres pruebas nuevas fallaron por ausencia de `EspecificacionCompuestaTransferencia`.
- GREEN observado: `TransferenciaValidationTest.php` pasó con 10 pruebas y 17 aserciones mediante PHP 8.3 explícito.
- Adapter y Bridge no fueron modificados durante esta tarea.
- Decorator: `ProcesadorTransferenciaDecorator` conserva el contrato y la delegación; `ProcesadorConTrazabilidad` agrega eventos seguros antes y después de ejecutar.
- Integración: el binding predeterminado envuelve explícitamente `ProcesadorEstandar` sin alterar la abstracción Bridge.
- RED observado: tres pruebas nuevas fallaron por ausencia de `ProcesadorConTrazabilidad`; las dos pruebas anteriores pasaron.
- GREEN observado: `TransferProcessorTest.php` pasó con 5 pruebas y 26 aserciones mediante PHP 8.3 explícito.
- Pint: aplicado correctamente sobre los siete archivos PHP de Semana 8; corrigió formato en dos archivos de pruebas.
- Regresión unitaria: `tests/Unit/Application/Account` pasó con 25 pruebas y 73 aserciones.
- Regresión transaccional: `CommandLedgerTransactionsTest.php` pasó con 8 pruebas y 41 aserciones.
- Integridad del diff: `git diff --check` pasó sin errores.
- Documentación: `Semana 8 - Composite y Decorator.md` y `Semana 8 - Composite y Decorator UML.md` describen responsabilidades, evidencia y diagramas Mermaid.
- UML para Word: tres imágenes PNG de 2600 × 1700 px para Composite, Decorator y el flujo integrado de los cuatro patrones.

## Estado

Implementación, pruebas, formato, documentación y UML completados. Sin commit porque no fue solicitado.
