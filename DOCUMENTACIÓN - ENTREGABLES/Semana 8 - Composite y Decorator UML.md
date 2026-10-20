# Semana 8 — UML de Composite y Decorator

Los diagramas describen las relaciones verificadas en `app/Application/Account/` y la composición registrada en `app/Providers/AppServiceProvider.php`. Las imágenes PNG del mismo directorio están preparadas para insertar en Word: `Semana 8 - UML Composite.png`, `Semana 8 - UML Decorator.png` y `Semana 8 - UML Integrado.png`.

## Composite: especificaciones internas

`CadenaValidacionTransferencia` crea el compuesto con la lista recibida; `TransferirFondos` declara el orden de las cinco hojas. Cada hijo recibe el mismo `ContextoValidacionTransferencia` y devuelve `TipoFalloOperacion` o `null`.

```mermaid
classDiagram
    class EspecificacionTransferencia {
        <<interface>>
        +fallo(ContextoValidacionTransferencia) TipoFalloOperacion|null
    }
    class EspecificacionCompuestaTransferencia {
        -especificaciones: list~EspecificacionTransferencia~
        +fallo(ContextoValidacionTransferencia) TipoFalloOperacion|null
    }
    class MismaCuenta
    class CuentasExistentes
    class AlcanceDeCuentas
    class MonedaCoincidente
    class SaldoSuficiente
    class CadenaValidacionTransferencia {
        +validar(ContextoValidacionTransferencia) TipoFalloOperacion|null
    }
    class ContextoValidacionTransferencia
    EspecificacionTransferencia <|.. EspecificacionCompuestaTransferencia
    EspecificacionTransferencia <|.. MismaCuenta
    EspecificacionTransferencia <|.. CuentasExistentes
    EspecificacionTransferencia <|.. AlcanceDeCuentas
    EspecificacionTransferencia <|.. MonedaCoincidente
    EspecificacionTransferencia <|.. SaldoSuficiente
    EspecificacionCompuestaTransferencia o--> EspecificacionTransferencia : contiene hijos
    CadenaValidacionTransferencia ..> EspecificacionCompuestaTransferencia : crea
    EspecificacionTransferencia ..> ContextoValidacionTransferencia : evalua
```

## Decorator: trazabilidad del procesador

El decorador implementa el mismo contrato que el procesador envuelto. `ProcesadorConTrazabilidad` usa `LoggerInterface` y delega sin alterar el resultado en el retorno normal. El contenedor enlaza `ProcesadorTransferencia` a un decorador sobre `ProcesadorEstandar`.

```mermaid
classDiagram
    class ProcesadorTransferencia {
        <<interface>>
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }
    class ProcesadorTransferenciaDecorator {
        <<abstract>>
        #processor: ProcesadorTransferencia
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }
    class ProcesadorConTrazabilidad {
        -logger: LoggerInterface
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }
    class ProcesadorEstandar {
        -transfer: TransferirFondos
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }
    class LoggerInterface {
        <<interface>>
        +info(message)
    }
    class TransferirFondos
    ProcesadorTransferencia <|.. ProcesadorTransferenciaDecorator
    ProcesadorTransferenciaDecorator <|-- ProcesadorConTrazabilidad
    ProcesadorTransferencia <|.. ProcesadorEstandar
    ProcesadorTransferenciaDecorator --> ProcesadorTransferencia : envuelve
    ProcesadorConTrazabilidad --> LoggerInterface : registra
    ProcesadorEstandar --> TransferirFondos : ejecuta
```

## Vista integrada: cuatro patrones, un flujo

Las flechas muestran dependencias de la ruta principal, no llamadas obligatorias para cada variante del procesador. `PoliticaValidacionExterna` queda fuera del árbol Composite y usa puertos externos; el canal referencia el contrato de procesador, no una implementación fija.

```mermaid
classDiagram
    class CanalTransferencia {
        <<abstract>>
        #processor: ProcesadorTransferencia
        +transferir(TransferirFondosDTO) ResultadoOperacion
    }
    class CanalWeb
    class CanalSucursal
    class ProcesadorTransferencia {
        <<interface>>
        +ejecutar(TransferirFondosDTO) ResultadoOperacion
    }
    class ProcesadorConTrazabilidad
    class ProcesadorTransferenciaDecorator
    class ProcesadorEstandar
    class TransferirFondos
    class CadenaValidacionTransferencia
    class EspecificacionTransferencia {
        <<interface>>
    }
    class EspecificacionCompuestaTransferencia
    class MismaCuenta
    class CuentasExistentes
    class AlcanceDeCuentas
    class MonedaCoincidente
    class SaldoSuficiente
    class PoliticaTransferencia {
        <<interface>>
    }
    class PoliticaValidacionExterna
    class PuertoKycTransferencia {
        <<interface>>
    }
    class PuertoRiesgoTransferencia {
        <<interface>>
    }
    class PuertoLimiteTransferencia {
        <<interface>>
    }
    class KycSimulado
    class RiesgoSimulado
    class LimiteSimulado
    CanalTransferencia <|-- CanalWeb
    CanalTransferencia <|-- CanalSucursal
    CanalTransferencia --> ProcesadorTransferencia : Bridge
    ProcesadorTransferencia <|.. ProcesadorTransferenciaDecorator
    ProcesadorTransferenciaDecorator <|-- ProcesadorConTrazabilidad
    ProcesadorTransferenciaDecorator --> ProcesadorTransferencia : Decorator
    ProcesadorTransferencia <|.. ProcesadorEstandar
    ProcesadorEstandar --> TransferirFondos
    TransferirFondos ..> CadenaValidacionTransferencia : reglas internas
    CadenaValidacionTransferencia ..> EspecificacionCompuestaTransferencia
    EspecificacionTransferencia <|.. EspecificacionCompuestaTransferencia
    EspecificacionCompuestaTransferencia o--> EspecificacionTransferencia : Composite
    EspecificacionTransferencia <|.. MismaCuenta
    EspecificacionTransferencia <|.. CuentasExistentes
    EspecificacionTransferencia <|.. AlcanceDeCuentas
    EspecificacionTransferencia <|.. MonedaCoincidente
    EspecificacionTransferencia <|.. SaldoSuficiente
    TransferirFondos --> PoliticaTransferencia : controles externos
    PoliticaTransferencia <|.. PoliticaValidacionExterna
    PoliticaValidacionExterna --> PuertoKycTransferencia : Adapter
    PoliticaValidacionExterna --> PuertoRiesgoTransferencia : Adapter
    PoliticaValidacionExterna --> PuertoLimiteTransferencia : Adapter
    PuertoKycTransferencia <|.. KycSimulado
    PuertoRiesgoTransferencia <|.. RiesgoSimulado
    PuertoLimiteTransferencia <|.. LimiteSimulado
```

**Lectura del flujo:** canal → procesador decorado → procesador estándar → caso de uso → especificaciones internas → política externa → escritura transaccional si se aprueba. El compuesto corta en el primer fallo; el decorador no implementa reglas financieras. La variante `ProcesadorControlado` puede sumar una política adicional después de la externa.
