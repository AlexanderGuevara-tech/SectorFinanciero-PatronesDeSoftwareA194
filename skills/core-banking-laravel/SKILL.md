---
name: core-banking-laravel
description: "Trigger: CORE bancario, Laravel, MySQL, cuentas, transacciones, prestamos, inversiones, fraude, KYC o AML. Planifica y desarrolla funcionalidades bancarias seguras y trazables."
license: Apache-2.0
metadata:
  author: sector-financiero
  version: "1.0"
---

# Core Banking Laravel

## Activation Contract
Aplica al planificar, diseñar, implementar o revisar funcionalidades del Sistema Bancario CORE.

## Hard Rules
- Usa Laravel con MySQL e InnoDB.
- Usa `DECIMAL` y moneda explícita; nunca `float` para dinero.
- Registra cada cambio financiero mediante un ledger de doble partida.
- Ejecuta operaciones monetarias dentro de transacciones de base de datos.
- Usa idempotencia, bloqueo de filas y reversos como nuevas operaciones.
- No coloques reglas financieras complejas en controladores ni en modelos Eloquent.
- Mantén separación entre dominio, aplicación, infraestructura e interfaces.
- Audita usuario, canal, fecha, operación, resultado y motivo.
- No afirmes cumplimiento regulatorio sin validar jurisdicción y requisitos legales.

## Decision Gates
| Situación | Decisión |
| --- | --- |
| Operación monetaria | Caso de uso transaccional con ledger y auditoría |
| Reintento de solicitud | `idempotency-key` y resultado reutilizable |
| Proveedor externo | Puerto en dominio y adapter en infraestructura |
| Proceso lento | Job, cola y evento confiable mediante Outbox |
| Fraude | Evaluación síncrona antes de confirmar la operación |
| AML y reportes | Procesamiento asíncrono con alertas trazables |
| Nuevo canal | Consumir la API central sin duplicar reglas de negocio |

## Execution Steps
1. Define alcance, actores, reglas, invariantes y criterios de aceptación.
2. Identifica agregados, permisos, estados y límites transaccionales.
3. Diseña migraciones, índices, relaciones y estrategia de concurrencia.
4. Implementa el caso de uso y sus pruebas unitarias e integración.
5. Integra API, autenticación, autorización, auditoría, eventos y observabilidad.
6. Verifica idempotencia, reversibilidad, seguridad y consistencia entre canales.

## Project Phases
1. Fundación Laravel, MySQL, autenticación, pruebas y observabilidad.
2. Clientes, cuentas, ledger, transferencias, reversos y conciliación.
3. Préstamos, intereses, amortización, pagos y mora.
4. Inversiones, vencimientos, rendimientos y liquidación.
5. API omnicanal, web, móvil, ATM y sucursal simulados.
6. Fraude, KYC, AML, alertas, revisión manual y reportes.
7. Concurrencia, seguridad, recuperación, backups y rendimiento.

## Output Contract
Cada cambio debe documentar alcance, decisiones, modelo de datos, reglas, riesgos, pruebas, integraciones reales o simuladas y pendientes.

## References
- `README.md`
- `Documentación Sistema Bancario Core.docx`
- `Objetivos - Semana 2.docx`
