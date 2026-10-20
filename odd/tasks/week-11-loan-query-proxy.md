# Semana 11 — Proxy de protección para consultas de préstamos

## Objetivo

Agregar una consulta de préstamos protegida mediante **Proxy**, sin reemplazar los patrones existentes. El prestatario podrá consultar únicamente préstamos vinculados a su cliente; un operador podrá consultar préstamos solo si tiene el permiso `view-loans` y el cliente está dentro de su alcance autorizado.

## Decisiones confirmadas

- Se agrega una relación explícita nullable `users.customer_id` hacia `customers.id`; no se inferirá identidad por coincidencia de IDs o correos.
- El flujo administrativo existente permitirá asignar o quitar el cliente asociado a un usuario.
- `view-loans` se registra como permiso disponible para asignación explícita mediante el administrador de roles; no se otorga automáticamente a todos los usuarios.
- El acceso del operador se limita además mediante `AlcanceClientes` existente.
- El Proxy delega la consulta real y oculta la existencia de préstamos ajenos a usuarios sin acceso.
- Se conserva Singleton, Adapter, Bridge, Composite, Decorator, Facade y Flyweight.

## Alcance

- Relación usuario-cliente, migración aditiva y asignación en administración de usuarios.
- Permiso `view-loans`.
- Contrato de consulta, servicio real y `ProxyConsultaPrestamo`.
- Ruta autenticada y controlador de lectura para demostrar la integración real del Proxy.
- Pruebas de prestatario propietario, préstamo ajeno, operador autorizado dentro/fuera de alcance, usuario sin permiso y delegación.

## Fuera de alcance

- Cambios a reglas financieras, desembolso, pagos o cronograma de préstamos.
- Asignar automáticamente `view-loans` a roles o usuarios.
- Exponer datos de préstamos no filtrados.

## Tareas

- [x] 1. Extender usuario-cliente de forma nullable y habilitar asignación segura desde administración.
- [ ] 2. Registrar `view-loans` y agregar la consulta real y su Proxy de protección.
- [ ] 3. Exponer la consulta autenticada y cubrir aislamiento de prestatario y operador con pruebas.
- [ ] 4. Ejecutar pruebas focalizadas y regresiones, aplicar Pint y revisar el diff.
- [ ] 5. Completar revisión nativa del candidato y registrar evidencia/commits.

## Riesgos

- Un usuario existente sin `customer_id` no puede actuar como prestatario hasta que un administrador lo vincule.
- El permiso `view-loans` debe asignarse explícitamente a los roles autorizados; la existencia del permiso no concede acceso por sí sola.
- El alcance operativo reutiliza el criterio actual de cuentas (`operado_por`); no se inventa un alcance distinto para préstamos.

## Evidencia del trabajo

- RED: `UsuarioClienteLinkTest.php` falló antes de implementar: 0/7 pasaron; faltaban columna, relación y validación.
- GREEN: `UsuarioClienteLinkTest.php` pasó con 9 tests y 44 assertions; cubre asignación, remoción, validación, autorización y usuarios legacy sin vínculo.
- Migración `2026_09_10_000006_add_customer_id_to_users_table.php`: FK nullable a `customers`, `nullOnDelete`; IDs/correos no se usan para inferir vínculos.
- `git diff --check` pasó. Regresión ampliada y Pint quedan pendientes.

## Estado

Tarea 1 completada. Proxy, permiso `view-loans` y consulta web siguen pendientes. No se modifican reglas financieras ni patrones anteriores.
