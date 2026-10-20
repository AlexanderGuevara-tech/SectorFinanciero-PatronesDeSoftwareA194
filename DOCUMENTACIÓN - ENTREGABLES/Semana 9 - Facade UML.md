# Semana 9 — UML de Facade e integración

Estos diagramas describen la ruta web implementada, no todas las variantes posibles del Bridge. Las versiones PNG legibles para Word están en `Semana 9 - UML Facade.png` y `Semana 9 - UML Integrado.png`.

## Facade: frontera web y delegación

`ControladorCuentas` conserva validación HTTP, actor y respuesta. `TransferenciaWebFacade` recibe seis valores, crea `TransferirFondosDTO` y devuelve directamente el resultado de `CanalWeb`.

```mermaid
classDiagram
    class ControladorCuentas {
        -transferenciaWebFacade: TransferenciaWebFacade
        +transfer(PeticionTransferirFondos) RedirectResponse
    }
    class TransferenciaWebFacade {
        -canalWeb: CanalWeb
        +transferir(int actorId, int sourceAccountId, int destinationAccountId, string currency, string amount, string requestKey) ResultadoOperacion
    }
    class TransferirFondosDTO {
        +actorId: int
        +sourceAccountId: int
        +destinationAccountId: int
        +currency: string
        +amount: string
        +requestKey: string
    }
    class CanalTransferencia {
        <<abstract>>
        #processor: ProcesadorTransferencia
        +transferir(TransferirFondosDTO) ResultadoOperacion
    }
    class CanalWeb {
        +codigo() string
    }
    class ProcesadorTransferencia {
        <<interface>>
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }
    class ResultadoOperacion
    ControladorCuentas --> TransferenciaWebFacade : invoca
    TransferenciaWebFacade ..> TransferirFondosDTO : crea
    TransferenciaWebFacade --> CanalWeb : delega
    CanalTransferencia <|-- CanalWeb
    CanalTransferencia --> ProcesadorTransferencia : Bridge
    TransferenciaWebFacade ..> ResultadoOperacion : retorna sin alterar
```

## Flujo integrado: cinco patrones

Las flechas representan el camino de una petición web con el binding actual de `ProcesadorTransferencia` en `AppServiceProvider`. El Composite interno se evalúa antes de la política externa Adapter; la política adicional es opcional. Un rechazo corta el flujo antes de escribir saldos o ledger.

```mermaid
flowchart TD
    HTTP[ControladorCuentas<br/>PeticionTransferirFondos] -->|seis datos validados| F[TransferenciaWebFacade<br/>crea TransferirFondosDTO]
    F -->|delegación| W[CanalWeb / CanalTransferencia<br/>Bridge: canal → procesador]
    W --> D[ProcesadorConTrazabilidad<br/>Decorator: inicio → delegado → fin normal]
    D --> P[ProcesadorEstandar]
    P --> T[TransferirFondos<br/>UnidadDeTrabajo]
    T --> I{Idempotencia<br/>y autorización}
    I -->|continúa| C[CadenaValidacionTransferencia<br/>Composite: cinco reglas internas en orden]
    C -->|sin fallo| A[PoliticaValidacionExterna<br/>Adapter: puertos KYC / riesgo / límite simulados]
    A -->|sin fallo; política adicional opcional| L[Saldos + operación + dos líneas de ledger<br/>+ clave idempotente]
    I -->|repetición o rechazo| R[ResultadoOperacion]
    C -->|primer fallo| R
    A -->|fallo| R
    L --> R
    R -->|mismo resultado por canal y Facade| HTTP
```

**Referencias:** `app/Http/Controllers/ControladorCuentas.php`, `app/Application/Account/TransferenciaWebFacade.php`, `app/Application/Account/CanalTransferencia.php`, `app/Application/Account/ProcesadorConTrazabilidad.php`, `app/Application/Account/TransferirFondos.php`, `app/Application/Account/EspecificacionCompuestaTransferencia.php`, `app/Application/Account/PoliticaValidacionExterna.php`, `app/Providers/AppServiceProvider.php`. El flujo detallado y las limitaciones se explican en `Semana 9 - Facade.md`.
