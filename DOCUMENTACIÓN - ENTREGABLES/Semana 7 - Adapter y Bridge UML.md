# Semana 7 — Adapter y Bridge con diagramas UML

## Propósito

Este documento complementa la entrega de la Semana 7 con diagramas UML para explicar cómo se aplicaron los patrones **Adapter** y **Bridge** en el flujo de transferencias bancarias multicanal.

## Resumen de la solución

La transferencia no se ejecuta directamente desde el canal. Primero pasa por una abstracción de canal, luego por un procesador de transferencia y finalmente reutiliza el caso de uso financiero existente.

```text
Canal
  → Procesador
  → TransferirFondos
  → Validaciones internas/externas
  → Unit of Work
  → Ledger
```

## Patrón Bridge

Bridge separa dos jerarquías que pueden variar de forma independiente:

| Dimensión | Clases |
|---|---|
| Canal de transferencia | `CanalWeb`, `CanalSucursal` |
| Procesador de transferencia | `ProcesadorEstandar`, `ProcesadorControlado` |

Esto evita crear una clase por cada combinación posible.

### Diagrama UML — Bridge

```mermaid
classDiagram
    class CanalTransferencia {
        <<interface>>
        +codigo() string
        +transferir(TransferirFondosDTO) ResultadoOperacion
    }

    class CanalWeb {
        -ProcesadorTransferencia procesador
        +codigo() string
        +transferir(TransferirFondosDTO) ResultadoOperacion
    }

    class CanalSucursal {
        -ProcesadorTransferencia procesador
        +codigo() string
        +transferir(TransferirFondosDTO) ResultadoOperacion
    }

    class ProcesadorTransferencia {
        <<interface>>
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }

    class ProcesadorEstandar {
        -TransferirFondos transferirFondos
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }

    class ProcesadorControlado {
        -TransferirFondos transferirFondos
        -PoliticaTransferencia politica
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }

    CanalTransferencia <|.. CanalWeb
    CanalTransferencia <|.. CanalSucursal
    CanalWeb --> ProcesadorTransferencia
    CanalSucursal --> ProcesadorTransferencia
    ProcesadorTransferencia <|.. ProcesadorEstandar
    ProcesadorTransferencia <|.. ProcesadorControlado
    ProcesadorEstandar --> TransferirFondos
    ProcesadorControlado --> TransferirFondos
    ProcesadorControlado --> PoliticaTransferencia
```

## Patrón Adapter

Adapter permite que el sistema use proveedores externos simulados sin acoplar el caso de uso financiero a una implementación concreta.

### Puertos internos

```text
PuertoKycTransferencia
PuertoRiesgoTransferencia
PuertoLimiteTransferencia
```

### Adaptadores simulados

```text
KycSimulado
RiesgoSimulado
LimiteSimulado
```

### Diagrama UML — Adapter

```mermaid
classDiagram
    class PoliticaValidacionExterna {
        -PuertoKycTransferencia kyc
        -PuertoRiesgoTransferencia riesgo
        -PuertoLimiteTransferencia limite
        +validar(TransferirFondosDTO) TipoFalloOperacion|null
    }

    class PuertoKycTransferencia {
        <<interface>>
        +estaAprobado(int clienteId) bool
    }

    class PuertoRiesgoTransferencia {
        <<interface>>
        +esSospechosa(TransferirFondosDTO) bool
    }

    class PuertoLimiteTransferencia {
        <<interface>>
        +permite(string moneda, string monto) bool
    }

    class KycSimulado {
        +estaAprobado(int clienteId) bool
    }

    class RiesgoSimulado {
        +esSospechosa(TransferirFondosDTO) bool
    }

    class LimiteSimulado {
        +permite(string moneda, string monto) bool
    }

    PoliticaValidacionExterna --> PuertoKycTransferencia
    PoliticaValidacionExterna --> PuertoRiesgoTransferencia
    PoliticaValidacionExterna --> PuertoLimiteTransferencia
    PuertoKycTransferencia <|.. KycSimulado
    PuertoRiesgoTransferencia <|.. RiesgoSimulado
    PuertoLimiteTransferencia <|.. LimiteSimulado
```

## Diagrama UML integrado

```mermaid
classDiagram
    class CanalTransferencia {
        <<interface>>
        +codigo() string
        +transferir(TransferirFondosDTO) ResultadoOperacion
    }

    class CanalWeb
    class CanalSucursal

    class ProcesadorTransferencia {
        <<interface>>
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }

    class ProcesadorEstandar
    class ProcesadorControlado

    class TransferirFondos {
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }

    class PoliticaTransferencia {
        <<interface>>
        +validar(TransferirFondosDTO) TipoFalloOperacion|null
    }

    class PoliticaValidacionExterna {
        +validar(TransferirFondosDTO) TipoFalloOperacion|null
    }

    class UnidadDeTrabajo
    class RepositorioLedger
    class RepositorioIdempotencia

    CanalTransferencia <|.. CanalWeb
    CanalTransferencia <|.. CanalSucursal
    CanalWeb --> ProcesadorTransferencia
    CanalSucursal --> ProcesadorTransferencia
    ProcesadorTransferencia <|.. ProcesadorEstandar
    ProcesadorTransferencia <|.. ProcesadorControlado
    ProcesadorEstandar --> TransferirFondos
    ProcesadorControlado --> TransferirFondos
    ProcesadorControlado --> PoliticaTransferencia
    PoliticaTransferencia <|.. PoliticaValidacionExterna
    TransferirFondos --> PoliticaValidacionExterna
    TransferirFondos --> UnidadDeTrabajo
    TransferirFondos --> RepositorioLedger
    TransferirFondos --> RepositorioIdempotencia
```

## Diagrama de secuencia

```mermaid
sequenceDiagram
    actor Usuario
    participant Canal as CanalWeb / CanalSucursal
    participant Procesador as ProcesadorTransferencia
    participant CasoUso as TransferirFondos
    participant Politica as PoliticaValidacionExterna
    participant KYC as KycSimulado
    participant Riesgo as RiesgoSimulado
    participant Limite as LimiteSimulado
    participant UoW as UnidadDeTrabajo
    participant Ledger as Ledger

    Usuario->>Canal: Solicita transferencia
    Canal->>Procesador: transferir(dto)
    Procesador->>CasoUso: ejecutar(dto)
    CasoUso->>Politica: validar(dto)
    Politica->>KYC: estaAprobado(cliente)
    Politica->>Riesgo: esSospechosa(dto)
    Politica->>Limite: permite(moneda, monto)

    alt Validación rechazada
        Politica-->>CasoUso: TipoFalloOperacion
        CasoUso-->>Procesador: Resultado rechazado
        Procesador-->>Canal: Resultado rechazado
        Canal-->>Usuario: Transferencia no ejecutada
    else Validación aprobada
        CasoUso->>UoW: ejecutar transacción
        UoW->>Ledger: registrar doble partida
        Ledger-->>UoW: confirmación
        UoW-->>CasoUso: commit
        CasoUso-->>Procesador: Resultado aprobado
        Procesador-->>Canal: Resultado aprobado
        Canal-->>Usuario: Transferencia ejecutada
    end
```

## Lectura académica

### Por qué Adapter

Adapter se justifica porque KYC, fraude y límites representan servicios externos o módulos independientes. El sistema define sus propios puertos y los adaptadores traducen las respuestas simuladas hacia el lenguaje interno de la aplicación.

### Por qué Bridge

Bridge se justifica porque el canal de origen y el procesador de transferencia son dimensiones independientes. Un canal web puede usar un procesador estándar o controlado; una sucursal también puede usar cualquiera de ellos sin crear una clase específica por combinación.

## Limitaciones

- Los adaptadores son simulados.
- No se integran proveedores reales de KYC, fraude o AML.
- No se agregó persistencia de auditoría para rechazos por canal.
- La evidencia de concurrencia MySQL/InnoDB de Semana 6 sigue pendiente.

## Conclusión

La solución demuestra cómo aplicar **Adapter** para desacoplar validadores externos simulados y **Bridge** para combinar canales y procesadores sin duplicar lógica financiera. El flujo mantiene la unidad de trabajo transaccional, ledger de doble partida e idempotencia como núcleo seguro de la operación bancaria.
