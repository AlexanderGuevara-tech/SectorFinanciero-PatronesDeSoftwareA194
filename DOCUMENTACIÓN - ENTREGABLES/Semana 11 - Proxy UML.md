# Semana 11 — UML de Protection Proxy para préstamos

Los diagramas muestran la consulta `GET /loans/{loan}` registrada bajo `auth`. El contrato inyectado en el controlador es `ConsultaPrestamo`; el sujeto real **no** se inyecta allí directamente. Mermaid es editable; no se generó PNG porque no hay CLI local.

## Diagrama de clases — participantes reales

```mermaid
classDiagram
    class ControladorPrestamos {
        -consultaPrestamo: ConsultaPrestamo
        +show(int loan) View
    }
    class ConsultaPrestamo {
        <<interface>>
        +porId(User actor, int loanId) Prestamo|null
    }
    class ProxyConsultaPrestamo {
        -subject: ConsultaPrestamo
        -loans: RepositorioPrestamos
        -scope: AlcanceClientes
        +porId(User actor, int loanId) Prestamo|null
    }
    class ServicioConsultaPrestamo {
        -loans: RepositorioPrestamos
        +porId(User actor, int loanId) Prestamo|null
    }
    class RepositorioPrestamos {
        <<interface>>
        +borrowerIdPorId(int id) int|null
        +porId(int id) Prestamo|null
    }
    class RepositorioPrestamosEloquent {
        +borrowerIdPorId(int id) int|null
        +porId(int id) Prestamo|null
    }
    class AlcanceClientes {
        <<interface>>
        +contiene(int actorId, int customerId) bool
    }
    class AlcanceClientesOperador {
        +contiene(int actorId, int customerId) bool
    }
    class User {
        +customer_id: int|null
        +can(string ability) bool
    }
    class Prestamo {
        +borrowerId: int
    }
    class AppServiceProvider
    ControladorPrestamos --> ConsultaPrestamo : inyecta y consulta
    ConsultaPrestamo <|.. ProxyConsultaPrestamo
    ConsultaPrestamo <|.. ServicioConsultaPrestamo
    AppServiceProvider ..> ProxyConsultaPrestamo : vincula al contrato
    ProxyConsultaPrestamo --> ConsultaPrestamo : envuelve sujeto real
    ProxyConsultaPrestamo --> RepositorioPrestamos : lee borrower_id
    ProxyConsultaPrestamo --> AlcanceClientes : verifica alcance
    ProxyConsultaPrestamo ..> User : vinculo y permiso
    ServicioConsultaPrestamo --> RepositorioPrestamos : carga prestamo
    RepositorioPrestamos <|.. RepositorioPrestamosEloquent
    AlcanceClientes <|.. AlcanceClientesOperador
    ServicioConsultaPrestamo ..> Prestamo : retorna
```

`AppServiceProvider` construye el Proxy con `ServicioConsultaPrestamo`, `RepositorioPrestamos` y `AlcanceClientes`; la relación del Proxy hacia `ConsultaPrestamo` representa ese sujeto envuelto. `User.customer_id` es opcional y no se infiere de email ni de la igualdad de IDs.

## Diagrama de secuencia — autorización antes de lectura completa

```mermaid
sequenceDiagram
    actor Visitante as Usuario autenticado
    participant Web as ControladorPrestamos
    participant Proxy as ProxyConsultaPrestamo
    participant Repo as RepositorioPrestamos
    participant Scope as AlcanceClientes
    participant Real as ServicioConsultaPrestamo
    Visitante->>Web: GET /loans/{loan} (auth)
    Web->>Proxy: porId(actor, loanId) vía ConsultaPrestamo
    Proxy->>Repo: borrowerIdPorId(loanId)
    Repo-->>Proxy: borrowerId o null (solo borrower_id)
    alt préstamo inexistente
        Proxy-->>Web: null
    else préstamo existente
        alt actor.customer_id no nulo e igual a borrowerId
            Note over Proxy: Autorizado como prestatario sin view-loans
        else otro actor
            Proxy->>Proxy: actor.can('view-loans')
            opt solo si tiene permiso
                Proxy->>Scope: contiene(actor.id, borrowerId)
                Scope-->>Proxy: true o false
            end
        end
        alt prestatario vinculado o permiso y alcance
            Proxy->>Real: porId(actor, loanId)
            Real->>Repo: porId(loanId)
            Repo-->>Real: Prestamo o null
            Real-->>Proxy: Prestamo o null
            Proxy-->>Web: Prestamo o null
        else sin permiso o fuera de alcance
            Proxy-->>Web: null (sin delegar)
        end
    end
    alt resultado null
        Web-->>Visitante: HTTP 404
    else resultado Prestamo
        Web-->>Visitante: vista prestamos.show
    end
```

## Lectura y límites

- Solo los dos caminos autorizados (vínculo explícito, o permiso **y** alcance) llegan al sujeto real; el Proxy delega **una vez**. Si falta permiso, el alcance ni siquiera se consulta.
- Para un operador no prestatario, permiso y alcance son necesarios a la vez: `AlcanceClientesOperador::contiene` verifica una cuenta con `operado_por = actor.id` y `customer_id = borrowerId`. `view-loans` está registrado, no asignado automáticamente.
- La ruta no usa `can:view-loans` como guard exclusivo: el Proxy resuelve el acceso de prestatarios y operadores. `null` por ausencia o denegación llega al mismo 404; el sujeto real carga el préstamo sin efectuar ese control.
- La secuencia ilustra lectura, no solicitud, aprobación ni desembolso. No atribuye reglas de financiación al Proxy ni promete atomicidad entre la primera lectura de `borrower_id` y la carga del préstamo.

**Referencias:** `app/Application/Loan/ConsultaPrestamo.php`, `app/Application/Loan/ProxyConsultaPrestamo.php`, `app/Application/Loan/ServicioConsultaPrestamo.php`, `app/Domain/Loan/RepositorioPrestamos.php`, `app/Infrastructure/Persistence/RepositorioPrestamosEloquent.php`, `app/Application/Account/AlcanceClientes.php`, `app/Infrastructure/Persistence/AlcanceClientesOperador.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/ControladorPrestamos.php`, `routes/web.php`. Explicación y matriz en `Semana 11 - Proxy.md`.
