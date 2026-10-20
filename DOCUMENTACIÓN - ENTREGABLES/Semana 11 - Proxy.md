# Semana 11 — Protection Proxy para consulta de préstamos

## Contexto y problema

La consulta web de un préstamo necesita ocultar los datos de otro cliente sin impedir que el prestatario vinculado vea los propios. Una autorización basada **solo** en `view-loans` bloquearía al prestatario sin rol; consultar primero el préstamo completo expondría datos antes de decidir el acceso. El **Protection Proxy** interpone la verificación ante el sujeto real y conserva el mismo contrato de consulta.

## Participantes y composición

| Rol | Clase real | Responsabilidad |
|---|---|---|
| Cliente | `ControladorPrestamos::show` | Recibe el actor autenticado y el parámetro `loan`; invoca `ConsultaPrestamo::porId` y convierte `null` en HTTP 404. |
| Sujeto común | `ConsultaPrestamo` | Contrato `porId(User $actor, int $loanId): ?Prestamo` utilizado por el controlador y ambas implementaciones. |
| Protection Proxy | `ProxyConsultaPrestamo` | Consulta solo `borrower_id`, autoriza al prestatario vinculado o al operador habilitado dentro de alcance; deniega sin delegar. |
| Sujeto real | `ServicioConsultaPrestamo` | Delega la lectura completa a `RepositorioPrestamos::porId($loanId)`; no aplica la autorización por sí mismo. |
| Colaboradores | `RepositorioPrestamos` / `RepositorioPrestamosEloquent`, `AlcanceClientes` / `AlcanceClientesOperador` | El repositorio obtiene el prestatario por ID antes de cargar el préstamo; el alcance comprueba si existe una cuenta de ese cliente operada por el actor. |
| Composición | `AppServiceProvider` | Vincula `ConsultaPrestamo` a un `ProxyConsultaPrestamo` que envuelve `ServicioConsultaPrestamo` y recibe repositorio y alcance. Registra el Gate `view-loans` sobre permisos del usuario. |

La ruta **`GET /loans/{loan}`**, nombre **`loans.show`**, está bajo middleware **`auth`**. No tiene middleware exclusivo `can:view-loans`: el Proxy debe permitir también la consulta del prestatario vinculado. El seeder registra `view-loans`, pero no lo asigna automáticamente a los roles existentes.

## Secuencia de autorización

1. `ControladorPrestamos::show(int $loan)` obtiene `auth()->user()` y llama a `ConsultaPrestamo::porId($actor, $loan)`; el contenedor entrega el Proxy.
2. `ProxyConsultaPrestamo::porId` llama a `RepositorioPrestamos::borrowerIdPorId($loanId)`; la implementación Eloquent lee únicamente `borrower_id` por ID. Si no existe, retorna `null` sin invocar al sujeto real.
3. Si `User.customer_id` **no es `null`** y equivale a `borrower_id`, permite la consulta como prestatario, sin exigir `view-loans`.
4. En cualquier otro caso, requiere **ambas** condiciones: `$actor->can('view-loans')` y `AlcanceClientes::contiene((int) $actor->id, $borrowerId)`. El alcance concreto usa la existencia de una cuenta con `accounts.operado_por = actorId` y `accounts.customer_id = borrowerId`. Si falla alguna condición, retorna `null` sin delegar.
5. Solo si se autorizó, delega una vez a `ServicioConsultaPrestamo::porId`, que consulta el préstamo completo mediante `RepositorioPrestamos::porId`. El controlador traduce cualquier `null` a 404: desde HTTP no distingue un préstamo inexistente de uno no autorizado.

| Situación (con sesión autenticada) | ¿Delegación al sujeto real? | Respuesta de `show` |
|---|---|---|
| ID de préstamo inexistente | No | 404 |
| `customer_id` explícito igual a `borrower_id` | Sí, aunque no tenga `view-loans` | Vista si el sujeto real devuelve el préstamo; 404 si devuelve `null` |
| No es prestatario; tiene `view-loans` **y** alcance sobre el cliente | Sí | Vista si existe al cargarlo; de lo contrario 404 |
| No es prestatario; falta permiso o alcance | No | 404 |

## Evidencia y límites

| Hecho | Fuente |
|---|---|
| Contrato, orden de comprobaciones y delegación | `app/Application/Loan/ConsultaPrestamo.php`, `app/Application/Loan/ProxyConsultaPrestamo.php`, `app/Application/Loan/ServicioConsultaPrestamo.php` |
| Binding, Gate, ruta y conversión a 404 | `app/Providers/AppServiceProvider.php`, `routes/web.php`, `app/Http/Controllers/ControladorPrestamos.php` |
| Proyección `borrower_id` y alcance de operador | `app/Infrastructure/Persistence/RepositorioPrestamosEloquent.php`, `app/Infrastructure/Persistence/AlcanceClientesOperador.php` |
| Vínculo opcional y permiso sin asignación inicial | `database/migrations/2026_09_10_000006_add_customer_id_to_users_table.php`, `database/seeders/DatabaseSeeder.php` |
| Casos permitidos, denegados y sin delegación | `tests/Feature/LoanQueryProxyTest.php`, `tests/Feature/LoanQueryRoutesTest.php` |

- El vínculo usuario–cliente es el `User.customer_id` nullable, asignado explícitamente; ni la coincidencia de IDs de usuario y cliente ni el correo electrónico constituyen prueba de vínculo. La cuenta operada determina **alcance**, pero no reemplaza el permiso `view-loans` para un operador no prestatario.
- La lectura previa de `borrower_id` evita hidratar el préstamo antes de autorizar. El precio es una consulta adicional y una posible variación entre esa lectura y la posterior carga completa; el código no establece un bloqueo transaccional en este flujo.
- La protección corresponde a la ruta de **lectura/consulta** que usa `ConsultaPrestamo`; inyectar o llamar directamente a `ServicioConsultaPrestamo` no reproduce este control. Solicitud, aprobación y desembolso siguen servicios y reglas distintos; el Proxy no calcula financiación ni concede permiso para mutaciones.
- El 404 disimula inexistencia y denegación en este endpoint, no garantiza por sí solo protección en otros consumidores del repositorio ni hace afirmaciones sobre temporización o concurrencia.

## Conclusión

El Proxy controla **quién puede consultar** antes de cargar el préstamo completo, dejando la lectura al sujeto real y la respuesta HTTP al controlador. El diagrama de clases y la secuencia editables están en `Semana 11 - Proxy UML.md` (sin PNG generado).
