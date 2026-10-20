# Semana 10 — UML de Flyweight en préstamos

El diagrama representa la reutilización de definiciones dentro de un catálogo y la frontera con los términos históricos persistidos. Las flechas indican dependencias y referencias de objetos, no un pool compartido entre procesos.

## Diagrama de clases — Flyweight y persistencia

```mermaid
classDiagram
    class CatalogoProductosPrestamo {
        -definitions: array
        +buscar(string id) DefinicionProductoPrestamo|null
    }
    class DefinicionProductoPrestamo {
        <<readonly>>
        +id: string
        +name: string
        +currency: string
        +annualRate: string
        +termMonths: int
    }
    class Prestamo {
        <<readonly>>
        +id: int
        +borrowerId: int
        +destinationAccountId: int
        +applicantId: int
        +producto: DefinicionProductoPrestamo
        +principal: string
        +requestKey: string
        +status: string
        +approvedBy: int|null
        +transactionId: int|null
    }
    class ServicioPrestamos {
        -catalog: CatalogoProductosPrestamo
        -loans: RepositorioPrestamos
        +solicitar(int applicantId, int borrowerId, int destinationAccountId, string productId, string principal, string requestKey) Prestamo
    }
    class RepositorioPrestamos {
        <<interface>>
        +solicitar(int borrowerId, int destinationAccountId, int applicantId, DefinicionProductoPrestamo product, string principal, string requestKey) Prestamo
    }
    class RepositorioPrestamosEloquent {
        -catalog: CatalogoProductosPrestamo
        +solicitar(int borrowerId, int destinationAccountId, int applicantId, DefinicionProductoPrestamo product, string principal, string requestKey) Prestamo
        -hydrate(object record) Prestamo
    }
    class loans {
        <<tabla>>
        product_id
        product_name
        currency
        annual_rate
        term_months
        principal
        status
    }
    CatalogoProductosPrestamo "1" o-- "0..*" DefinicionProductoPrestamo : conserva por ID
    ServicioPrestamos --> CatalogoProductosPrestamo : busca producto
    ServicioPrestamos --> RepositorioPrestamos : solicita
    RepositorioPrestamos <|.. RepositorioPrestamosEloquent
    RepositorioPrestamosEloquent --> CatalogoProductosPrestamo : compara al hidratar
    RepositorioPrestamosEloquent ..> DefinicionProductoPrestamo : reconstruye si difiere
    RepositorioPrestamosEloquent ..> loans : guarda y lee términos
    RepositorioPrestamosEloquent ..> Prestamo : devuelve
    Prestamo "0..*" --> "1" DefinicionProductoPrestamo : producto
```

## Lectura y límites del modelo

- Dos `Prestamo` del mismo catálogo pueden referenciar la misma `DefinicionProductoPrestamo` cuando seleccionan el mismo producto. Sus datos individuales permanecen separados.
- `RepositorioPrestamosEloquent` guarda la instantánea de los términos en `loans`. Si los términos actuales del catálogo no coinciden con los persistidos, `hydrate` construye una definición histórica con los términos de esa fila; el diagrama **no** promete identidad de objeto para definiciones históricas.
- `DefinicionProductoPrestamo` valida moneda (`COP` o `USD`), formato de tasa anual y plazo positivo al construirla. Esta restricción pertenece a la definición, no a reglas de desembolso.
- Desembolso, ledger, cuenta interna CORE e idempotencia no forman parte de este diagrama Flyweight: son responsabilidades de integración separadas en `ServicioPrestamos`.

**Referencias:** `app/Domain/Loan/CatalogoProductosPrestamo.php`, `app/Domain/Loan/DefinicionProductoPrestamo.php`, `app/Domain/Loan/Prestamo.php`, `app/Application/Loan/ServicioPrestamos.php`, `app/Domain/Loan/RepositorioPrestamos.php`, `app/Infrastructure/Persistence/RepositorioPrestamosEloquent.php`. Explicación y evidencia en `Semana 10 - Flyweight.md`. El código Mermaid es editable; no se encontró un renderizador Mermaid local instalado para generar `Semana 10 - UML Flyweight.png`.
