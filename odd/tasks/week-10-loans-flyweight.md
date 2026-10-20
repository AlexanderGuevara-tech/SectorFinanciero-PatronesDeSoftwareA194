# Semana 10 — Préstamos con Flyweight

## Objetivo

Implementar un flujo académico de préstamos que reutilice definiciones inmutables de productos mediante **Flyweight**, y que acredite el dinero desembolsado en una cuenta bancaria CORE del cliente usando el ledger existente.

## Decisiones confirmadas

- Los productos de ejemplo tendrán tasas y plazos configurables para el MVP académico; no se presentarán como condiciones comerciales reales.
- El desembolso de un préstamo aprobado debe consignarse en una cuenta bancaria CORE perteneciente al cliente.
- La contrapartida será una cuenta interna CORE de fondeo representada por una cuenta bancaria existente, cuyo ID se configura y cuyo saldo debe estar financiado; el sistema no debe inventar ni sembrar dinero.
- La aprobación requiere un operador autorizado distinto del solicitante.
- Las definiciones de producto son estado intrínseco compartido e inmutable; cada préstamo conserva su estado extrínseco (cliente, cuenta destino, capital, saldo, estado y operación asociada).
- No reemplazar Singleton, Adapter, Bridge, Composite, Decorator ni Facade; Flyweight se agrega al lado de ellos.

## Alcance inicial

- Catálogo/factory de productos de préstamo Flyweight.
- Solicitud y evaluación con términos tomados del producto seleccionado.
- Aprobación con autorización adecuada y destino asociado a una cuenta del cliente.
- Desembolso atómico al ledger central, con idempotencia y protección contra doble acreditación.
- Pruebas de identidad compartida del flyweight, aislamiento de estado por préstamo y recorrido de desembolso.
- Las reglas de pagos, intereses devengados, mora y amortización posterior al desembolso quedan fuera de este corte hasta acordarlas explícitamente.

## Criterios de aceptación

- Consultar repetidamente el mismo tipo de producto devuelve la misma instancia inmutable de definición.
- Dos préstamos pueden compartir esa definición sin compartir cliente, capital, saldo, cuenta destino ni estado.
- La cuenta CORE de fondeo debe estar explícitamente marcada como interna en el dominio/persistencia; un simple ID de configuración o titular diferente no basta.
- No se puede aprobar/desembolsar un préstamo hacia una cuenta que no pertenezca al cliente prestatario.
- Un préstamo solo se desembolsa si está aprobado y su solicitud tiene una clave idempotente válida.
- El desembolso acredita la cuenta dentro de la misma unidad de trabajo y deja ledger balanceado; un reintento no duplica fondos.
- Fallos de autorización, estado, cuenta o persistencia no dejan saldo/ledger parcialmente alterados.
- Los patrones existentes conservan clases, contratos y rutas demostrables.

## Tareas

- [x] 1. Explorar el dominio de cuentas, catálogo de productos, ledger, idempotencia y autorización.
- [x] 2. Cerrar la parametrización académica, la cuenta interna de fondeo configurada y la aprobación por operador distinto.
- [x] 3. Escribir pruebas RED de Flyweight, solicitud/aprobación y restricciones de cuenta destino.
- [x] 4. Implementar catálogo Flyweight inmutable y entidades/casos de uso de préstamo.
- [x] 5. Integrar persistencia, autorización, idempotencia y desembolso atómico al ledger.
- [x] 6. Marcar/verificar explícitamente que la cuenta configurada de fondeo es interna de CORE; no basta con que tenga otro titular.
- [ ] 7. Repetir pruebas focalizadas y revisión independiente tras corregir el hallazgo.
- [ ] 8. Ejecutar pruebas focalizadas y regresiones financieras; aplicar formato PHP.
- [ ] 9. Preparar evidencia documental y UML si se solicita.

## Riesgos y decisiones aún por verificar

- Revisión independiente inicial: CRITICAL por falta de prueba de titularidad CORE. Corregido mediante `accounts.es_interna` (default false), mapeo de dominio/persistencia y guard en desembolso; se agregó prueba de no mutación para fuentes no internas. Revisión independiente posterior pendiente.
- La API actual del ledger solo admite dos líneas balanceadas vinculadas a cuentas bancarias; la operación de préstamo se registra como `loan_disbursement`, no como `transfer`.
- El catálogo Flyweight debe compartir solo definición inmutable, nunca saldos ni datos de prestatarios.
- Tasas/plazos de ejemplo se documentan como fixtures/configuración académica, no como recomendaciones ni oferta financiera real.
- No implementar cronograma de cuotas, cálculo de interés pagado, mora ni pagos mientras falten reglas del producto.

## Evidencia esperada

- Pruebas de dominio del catálogo y préstamos.
- Pruebas feature de ownership de cuenta, autorización, idempotencia, ledger y rollback.
- Resultados de pruebas y limitaciones pendientes.
- Rutas exactas de código y explicación de Flyweight junto a los patrones previos.

## Estado

Flujo backend implementado: catálogo Flyweight, solicitud/evaluación, aprobación por operador distinto, cuenta interna marcada `es_interna`, desembolso atómico/idempotente con operación `loan_disbursement` y ledger balanceado. Pruebas focalizadas y Pint pasaron. Falta regresión ampliada y segunda revisión independiente. Pagos/amortización/mora y UI permanecen fuera del alcance; sin commit.
