# Diseño de arquitectura y selección de patrones

## 1. Propósito

Diseñar una arquitectura para el Sistema Bancario CORE que permita implementar las capacidades del proyecto de forma segura y consistente, mientras cada etapa del desarrollo demuestra patrones de software aplicados a problemas reales del dominio bancario.

El uso de patrones tiene un propósito educativo: cada patrón debe resolver una necesidad identificable y quedar respaldado por código, pruebas y una explicación de su trade-off.

## 2. Decisión arquitectónica general

Se propone un **monolito modular con arquitectura hexagonal y separación por capas**.

Esta decisión es adecuada para un proyecto educativo de 16 semanas porque permite:

- Mantener un único sistema fácil de ejecutar y demostrar.
- Separar los módulos del negocio sin introducir prematuramente microservicios.
- Mostrar claramente patrones creacionales, estructurales y de comportamiento.
- Proteger las reglas financieras de Laravel, HTTP y la base de datos.
- Evolucionar posteriormente hacia servicios independientes si fuera necesario.

## 3. Capas

```text
Interfaces / Canales
        |
Aplicación (casos de uso, comandos, DTOs)
        |
Dominio (entidades, reglas, puertos, eventos)
        |
Infraestructura (Eloquent, MySQL, colas, APIs externas)
```

### Interfaces / Canales

Incluye controladores HTTP, autenticación, validación de solicitudes y presentación de respuestas. Los canales web, móvil, ATM y sucursal consumen los mismos casos de uso.

### Aplicación

Orquesta casos de uso como `TransferFunds`, `OpenAccount`, `DisburseLoan` y `CreateInvestment`. No contiene detalles de Eloquent ni reglas propias del transporte HTTP.

### Dominio

Contiene cuentas, transacciones, préstamos, inversiones, reglas de fraude, estados, servicios de dominio, puertos y eventos. Esta capa no depende de Laravel.

### Infraestructura

Implementa repositorios, persistencia, adapters de proveedores externos, bus de eventos, colas y mecanismos técnicos de auditoría.

## 4. Módulos del sistema

```text
app/
├── Domain/
│   ├── Customer/
│   ├── Account/
│   ├── Transaction/
│   ├── Loan/
│   ├── Investment/
│   ├── Fraud/
│   ├── Compliance/
│   └── Shared/
├── Application/
│   ├── Customer/
│   ├── Account/
│   ├── Transaction/
│   ├── Loan/
│   ├── Investment/
│   ├── Fraud/
│   └── Compliance/
├── Infrastructure/
│   ├── Persistence/
│   ├── Fraud/
│   ├── Compliance/
│   └── Channels/
└── Interfaces/
    └── Http/
```

Los nombres son una guía de organización. La estructura final puede adaptarse al esqueleto Laravel existente sin mezclar reglas financieras en controladores o modelos Eloquent.

## 5. Patrones seleccionados

### 5.1 Strategy

**Problema:** distintos productos calculan intereses y distintos canales o reglas evalúan operaciones de manera diferente.

**Aplicación:** `InterestCalculationStrategy` para interés simple, compuesto o de préstamo; `FraudRuleStrategy` para reglas individuales de riesgo.

**Evidencia educativa:** interfaz, varias implementaciones, selector y pruebas comparativas.

### 5.2 Factory Method / Abstract Factory

**Problema:** crear productos financieros según su tipo sin acoplar el caso de uso a clases concretas.

**Aplicación:** fábrica de cuentas, préstamos e inversiones.

**Evidencia educativa:** creación de `SavingsAccount`, `CheckingAccount` o productos de inversión mediante una fábrica.

### 5.3 Repository

**Problema:** el dominio y los casos de uso no deben depender directamente de Eloquent.

**Aplicación:** `AccountRepository`, `TransactionRepository` y `LoanRepository` como puertos del dominio, con implementaciones MySQL/Eloquent en infraestructura.

**Evidencia educativa:** implementación real y fake repository para pruebas.

### 5.4 Adapter

**Problema:** cada proveedor externo de KYC, listas AML o notificaciones puede tener una API diferente.

**Aplicación:** adapters para un proveedor KYC simulado, una lista AML simulada y un proveedor de notificaciones.

**Evidencia educativa:** el caso de uso depende de una interfaz estable, no del proveedor.

### 5.5 State

**Problema:** cuentas, préstamos, inversiones y alertas tienen comportamientos diferentes según su estado.

**Aplicación:** estados de cuenta (`Active`, `Blocked`, `Closed`), préstamo (`Requested`, `Approved`, `Defaulted`) y alerta (`Open`, `UnderReview`, `Resolved`).

**Evidencia educativa:** cada estado permite o rechaza operaciones de forma diferente.

### 5.6 Command

**Problema:** las operaciones financieras deben representar una intención explícita y ser auditables.

**Aplicación:** comandos como `CreditAccount`, `DebitAccount`, `TransferFunds` y `ReverseTransaction`.

**Evidencia educativa:** comandos separados, handler de aplicación y registro de resultado.

### 5.7 Specification

**Problema:** las reglas de elegibilidad, límites y riesgo pueden combinarse y reutilizarse.

**Aplicación:** `SufficientBalanceSpecification`, `KycApprovedSpecification`, `TransferLimitSpecification` y `HighRiskCountrySpecification`.

**Evidencia educativa:** composición de especificaciones mediante AND/OR/NOT.

### 5.8 Chain of Responsibility

**Problema:** una operación debe pasar por varias validaciones antes de confirmarse.

**Aplicación:** cadena de validación de transferencias: autenticación, permisos, KYC, saldo, límites, fraude y auditoría.

**Evidencia educativa:** handlers independientes y una cadena configurable.

### 5.9 Observer / Domain Events

**Problema:** una operación confirmada puede producir auditoría, notificaciones y evaluación posterior sin acoplar todos los servicios.

**Aplicación:** eventos `FundsTransferred`, `LoanDisbursed` y `SuspiciousOperationDetected`.

**Evidencia educativa:** listeners separados para auditoría, alertas y notificaciones.

### 5.10 Template Method

**Problema:** préstamos e inversiones comparten pasos generales, pero calculan rendimientos de forma diferente.

**Aplicación:** flujo base para calcular, registrar y liquidar productos financieros, con pasos especializados por producto.

**Evidencia educativa:** clase abstracta con algoritmo común y métodos variables.

### 5.11 Facade

**Problema:** un canal no debería conocer todos los servicios necesarios para ejecutar una operación.

**Aplicación:** `BankingFacade` o un servicio de aplicación que simplifique operaciones para los controladores de canal.

**Evidencia educativa:** controlador delgado que delega en una única entrada de aplicación.

### 5.12 Outbox

**Problema:** un evento no debe perderse si la operación financiera se confirma pero falla la publicación del evento.

**Aplicación:** registro transaccional de eventos pendientes para fraude, AML, auditoría y notificaciones.

**Evidencia educativa:** tabla outbox, procesador y prueba de reintento.

## 6. Ruta educativa de 16 semanas

| Semana | Tema funcional | Patrón o concepto demostrado | Evidencia en código |
|---|---|---|---|
| 1 | Requisitos y dominio | Entidades, Value Objects y separación de responsabilidades | Modelo inicial y pruebas de reglas |
| 2 | Arquitectura | Hexagonal, capas y Dependency Inversion | Puertos, adapters y diagrama |
| 3 | Fundación técnica | Repository | Repositorio de cuentas con fake para pruebas |
| 4 | Clientes y permisos | Facade | Caso de uso único para los canales |
| 5 | Cuentas | Factory Method y State | Fábrica de cuentas y estados de cuenta |
| 6 | Ledger y movimientos | Command y Unit of Work transaccional | Comandos financieros y transacción ACID |
| 7 | Transferencias | Specification y Chain of Responsibility | Validaciones componibles y cadena |
| 8 | Estabilización | Observer y Domain Events | Auditoría y notificaciones desacopladas |
| 9 | Solicitudes de préstamo | Strategy | Estrategias de evaluación y tasas |
| 10 | Pagos y mora | Template Method | Flujo de amortización extensible |
| 11 | Inversiones | Strategy y Factory | Productos y cálculo de rendimientos |
| 12 | API omnicanal | Adapter | Adaptadores de web, móvil, ATM y sucursal |
| 13 | Integración de canales | Proxy / autorización | Control de acceso por canal y rol |
| 14 | Fraude | Chain of Responsibility y Strategy | Motor de reglas en tiempo real |
| 15 | KYC y AML | Adapter, Observer y Outbox | Proveedores simulados, alertas y reintentos |
| 16 | Integración final | Revisión de patrones y principios SOLID | Demo, pruebas, informe y defensa |

## 7. Reglas para demostrar patrones correctamente

1. Cada patrón debe estar asociado a un problema real del dominio.
2. Cada semana debe incluir código funcional, una prueba y una breve explicación.
3. No se debe agregar un patrón únicamente para aumentar la cantidad de patrones.
4. Las operaciones monetarias deben conservar transacciones, idempotencia, bloqueo, reversos y auditoría aunque se esté demostrando otro patrón.
5. Los patrones no reemplazan principios básicos: cohesión, bajo acoplamiento, separación de responsabilidades y dependencia de abstracciones.
6. Las integraciones KYC/AML serán simuladas y deben documentarse como tales.

## 8. Formato de entrega semanal

Cada entrega semanal debe contener:

- Problema que se resolvió.
- Patrón aplicado y motivo de selección.
- Diagrama pequeño del patrón.
- Código funcional.
- Pruebas automatizadas.
- Riesgos o limitaciones.
- Relación con los objetivos del sistema.

## 9. Criterio de diseño principal

El sistema debe ser suficientemente realista para demostrar consistencia financiera y suficientemente pequeño para ser terminado en 16 semanas. Por esa razón, se implementará un **MVP modular**, con canales y proveedores externos simulados, pero con reglas financieras, auditoría e idempotencia reales dentro del alcance educativo.
