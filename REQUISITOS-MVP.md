# Requisitos del MVP — Sistema Bancario CORE

## 1. Propósito

Definir el alcance inicial del Sistema Bancario CORE para gestionar operaciones financieras de forma segura, consistente, auditable y trazable.

Este documento constituye la base para el diseño de la arquitectura y la implementación incremental durante las 16 semanas.

## 2. Alcance del MVP

El MVP debe permitir:

1. Gestionar clientes, usuarios y cuentas bancarias.
2. Registrar créditos, débitos y transferencias.
3. Mantener saldos consistentes mediante un ledger de doble partida.
4. Gestionar préstamos e inversiones como módulos que utilizan el núcleo financiero.
5. Exponer las operaciones mediante una API centralizada para distintos canales.
6. Evaluar operaciones sospechosas y generar alertas de fraude.
7. Registrar información y estados básicos de KYC y AML.
8. Auditar las operaciones relevantes.

Las integraciones con biometría, entidades gubernamentales, listas internacionales y proveedores externos serán simuladas mediante puertos y adaptadores.

## 3. Actores

| Actor | Responsabilidad |
|---|---|
| Cliente | Consulta cuentas, movimientos, préstamos e inversiones; inicia operaciones permitidas. |
| Operador de sucursal | Gestiona clientes y operaciones autorizadas en nombre del cliente. |
| Administrador | Gestiona usuarios, roles, parámetros y revisa auditoría. |
| Analista de fraude | Revisa alertas y decide aprobar, bloquear o escalar operaciones. |
| Analista KYC/AML | Revisa validaciones de identidad y alertas de cumplimiento. |
| Canal externo | Consume la API central: web, móvil, ATM o sucursal. |
| Sistema CORE | Aplica las reglas, registra operaciones y mantiene la fuente única de verdad. |

## 4. Casos de uso principales

### Clientes y cuentas

- Crear y actualizar un cliente.
- Registrar el estado KYC de un cliente.
- Abrir, bloquear, desbloquear y cerrar una cuenta.
- Consultar saldo y movimientos.

### Operaciones financieras

- Acreditar fondos.
- Debitar fondos.
- Transferir fondos entre cuentas.
- Consultar el estado de una operación mediante una clave de idempotencia.
- Reversar una operación mediante una nueva operación compensatoria.

### Préstamos

- Solicitar y evaluar un préstamo.
- Aprobar o rechazar una solicitud.
- Desembolsar el préstamo.
- Generar cronograma de amortización.
- Registrar pagos y calcular mora.

### Inversiones

- Consultar productos de inversión.
- Crear una inversión.
- Consultar rendimiento y vencimiento.
- Liquidar una inversión vencida.

### Riesgo y cumplimiento

- Evaluar una operación antes de confirmarla.
- Generar una alerta de fraude.
- Aprobar, bloquear o dejar en revisión una operación.
- Ejecutar validaciones KYC simuladas.
- Monitorear operaciones y generar alertas AML.
- Registrar la resolución de una alerta.

## 5. Reglas e invariantes de negocio

1. Una cuenta no puede quedar con saldo negativo, salvo que el producto lo autorice explícitamente.
2. Toda operación monetaria debe generar débitos y créditos balanceados en el ledger.
3. El saldo no se modifica sin una operación financiera registrada.
4. Las operaciones monetarias se ejecutan dentro de una transacción de base de datos.
5. Una solicitud repetida con la misma clave de idempotencia debe devolver el resultado original y no duplicar fondos.
6. Una reversión se registra como una operación nueva; nunca se elimina ni se modifica la operación original.
7. Las cuentas involucradas en una transferencia deben bloquearse de forma consistente para evitar condiciones de carrera.
8. Una operación marcada como sospechosa debe bloquearse o quedar en revisión antes de confirmar el movimiento.
9. Cada operación debe conservar usuario, canal, fecha, resultado y motivo.
10. Los importes deben almacenarse con `DECIMAL` y moneda explícita; nunca con `float`.
11. Préstamos e inversiones deben utilizar el mismo núcleo contable que las cuentas.
12. Los canales no deben duplicar reglas de negocio: deben consumir los casos de uso centrales.

## 6. Requisitos no funcionales

### Seguridad

- Autenticación y autorización basada en roles y permisos.
- Protección de credenciales y datos sensibles.
- Validación de entradas y control de acceso por operación.
- Registro de eventos de seguridad y operaciones críticas.

### Consistencia y confiabilidad

- Integridad referencial en la base de datos.
- Transacciones ACID para operaciones monetarias.
- Reintentos seguros mediante idempotencia.
- Trazabilidad completa de cambios y decisiones.

### Mantenibilidad

- Separación entre dominio, aplicación, infraestructura e interfaces.
- Reglas financieras fuera de controladores y modelos Eloquent.
- Uso documentado de patrones de software.
- Pruebas unitarias para reglas de dominio y pruebas de integración para casos de uso.

### Omnicanalidad

- API centralizada para web, móvil, ATM y sucursal.
- Respuestas y errores consistentes en todos los canales.
- Ningún canal debe acceder directamente a las tablas para ejecutar operaciones.

## 7. Fuera del alcance inicial

- Integración real con bancos, gobiernos o proveedores biométricos.
- Cumplimiento legal certificado para una jurisdicción específica.
- Aplicaciones móviles nativas publicadas en tiendas.
- Conexión con cajeros físicos.
- Motor avanzado de machine learning para fraude.

## 8. Criterios de aceptación del MVP

- Un cliente puede abrir una cuenta y consultar su saldo.
- Una transferencia válida actualiza correctamente ambas cuentas y el ledger.
- Una transferencia sin fondos falla sin alterar los saldos.
- Repetir una solicitud con la misma clave no duplica la operación.
- Una reversión conserva el historial y compensa el movimiento original.
- Préstamos e inversiones generan sus movimientos en el ledger central.
- Web, móvil, ATM y sucursal simulados utilizan la misma API y reglas.
- Una regla de fraude puede bloquear o enviar una operación a revisión.
- KYC y AML pueden generar estados y alertas trazables.
- Las pruebas cubren los escenarios financieros críticos.

## 9. Decisiones pendientes

- Definir moneda y zona horaria oficiales del proyecto.
- Definir los límites de transferencia y sobregiro.
- Elegir los productos concretos de préstamo e inversión del MVP.
- Definir las reglas iniciales de fraude y AML.
- Confirmar si el frontend será una interfaz web funcional o una simulación de canales.
