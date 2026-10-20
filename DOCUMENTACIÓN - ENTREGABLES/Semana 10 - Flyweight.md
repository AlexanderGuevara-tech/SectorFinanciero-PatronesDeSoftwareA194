# Semana 10 — Flyweight para productos de préstamo

## Contexto y problema

Distintas solicitudes de préstamo pueden elegir el mismo producto, pero cada préstamo conserva datos propios: solicitante, prestatario, cuenta de destino, capital, estado y clave de solicitud. Construir otra definición de producto para cada solicitud duplicaría en memoria los mismos términos. **Flyweight** permite reutilizar una definición inmutable por identificador dentro del catálogo, sin compartir el estado individual de los préstamos.

## Solución y participantes

| Rol | Clase real | Responsabilidad |
|---|---|---|
| Fábrica y almacén de Flyweights | `CatalogoProductosPrestamo` | Construye una `DefinicionProductoPrestamo` por entrada al inicializarse; `buscar(id)` devuelve esa misma instancia para el mismo identificador o `null` si no existe. |
| Flyweight (estado compartido) | `DefinicionProductoPrestamo` | Objeto `final readonly` con identificador, nombre, moneda, tasa anual y plazo en meses; valida su construcción y no admite mutaciones de propiedades. |
| Contexto (estado propio) | `Prestamo` | Mantiene la referencia a `producto` junto con identificadores de actores y cuenta, capital, clave, estado, aprobador y transacción. Cada préstamo mantiene sus propios datos. |
| Cliente | `ServicioPrestamos::solicitar` | Obtiene la definición por `productId`, comprueba su existencia y la compatibilidad de moneda y destino antes de solicitar la persistencia. |
| Persistencia | `RepositorioPrestamosEloquent` | Guarda los términos del producto en la fila de `loans`; al hidratar, busca el Flyweight por `product_id` y reutiliza esa definición si coinciden los términos descriptivos y financieros persistidos. Si no hay definición o difiere alguno de esos términos, reconstruye una definición histórica con los valores almacenados. |

## Recorrido implementado

1. `CatalogoProductosPrestamo` crea las definiciones a partir de `config('loans.products', [])` al resolverse desde el contenedor.
2. `ServicioPrestamos::solicitar` busca el producto por identificador y verifica la elegibilidad del destino; entrega la definición al repositorio. Dos solicitudes con el mismo producto del mismo catálogo pueden recibir la **misma instancia**, pero no comparten capital, prestatario, cuenta ni estado.
3. `RepositorioPrestamosEloquent::solicitar` guarda `product_id`, `product_name`, `currency`, `annual_rate` y `term_months` con los datos propios del préstamo.
4. Al leer un préstamo, `hydrate` busca el producto actual usando el `product_id` persistido y compara nombre, moneda, tasa anual y plazo. Si la definición existe y esos términos coinciden, usa la instancia del catálogo; si falta o difiere algún término, crea una `DefinicionProductoPrestamo` con los valores históricos almacenados. Cambiar el catálogo **no reescribe préstamos existentes**.

## Evidencia en la implementación

| Comportamiento | Ruta |
|---|---|
| Almacén por ID, construcción y búsqueda | `app/Domain/Loan/CatalogoProductosPrestamo.php` |
| Campos inmutables y validación de la definición | `app/Domain/Loan/DefinicionProductoPrestamo.php` |
| Referencia al producto y datos propios del préstamo | `app/Domain/Loan/Prestamo.php` |
| Selección del producto en la solicitud | `app/Application/Loan/ServicioPrestamos.php` |
| Términos persistidos y reconstrucción histórica en `hydrate` | `app/Infrastructure/Persistence/RepositorioPrestamosEloquent.php` |
| Prueba de identidad y de términos históricos | `tests/Unit/Domain/Loan/CatalogoProductosPrestamoTest.php`, `tests/Feature/PrestamosTest.php` (`test_two_loans_share_only_product_definition_and_retain_historical_terms`) |

## Ventajas, costos y límites

- La reutilización evita crear una definición por solicitud cuando se usa el mismo catálogo; la inmutabilidad impide que una solicitud altere los términos compartidos.
- La instantánea persistida protege los términos históricos aunque el catálogo actual cambie o deje de incluir el producto. La contrapartida es almacenar también los términos en cada fila y comparar al hidratar; una definición histórica reconstruida **no tiene por qué ser la misma instancia** que otras definiciones.
- El contenedor registra el catálogo como `singleton` de la aplicación; esto no prueba un pool global entre procesos ni objetos compartidos entre servidores. La identidad de instancia solo se afirma dentro del mismo catálogo.
- Autorización, idempotencia de solicitud y desembolso, cuenta interna CORE configurada, bloqueo de cuentas, movimientos de saldo y ledger pertenecen a la lógica de integración de `ServicioPrestamos`, **no** al mecanismo Flyweight. El ejemplo de producto configurado es académico; no se infieren tasas ni requisitos comerciales.

## Conclusión

Flyweight comparte la definición inmutable del producto mientras el préstamo conserva su estado propio y sus términos persistidos. El diagrama editable y sus límites están en `Semana 10 - Flyweight UML.md`; no se requiere una imagen para conservar el modelo Mermaid.
