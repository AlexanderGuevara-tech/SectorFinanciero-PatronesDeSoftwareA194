# Documentación semanas 10–11 — Flyweight y Proxy

## Objetivo

Completar los entregables Markdown de los dos patrones más recientes, reflejando el código actual sin inventar reglas financieras ni de autorización.

## Alcance

- Semana 10: explicar Flyweight, su catálogo de productos de préstamo y cómo interactúa con la persistencia/desembolso sin atribuir al patrón las invariantes financieras.
- Semana 11: explicar el Protection Proxy de consulta, la asociación explícita usuario-cliente, `view-loans`, `AlcanceClientes` y la respuesta 404 que oculta préstamos inexistentes/no autorizados.
- Crear un documento principal y un Markdown UML editable por semana, siguiendo semanas 7–9.
- Mantener diagramas en Mermaid dentro del Markdown; producir PNG solo si existe un renderizador local instalado, sin agregar dependencias.

## Fuera de alcance

- Editar o abrir archivos `.docx`, en particular `DOCUMENTACIÓN - ENTREGABLES/~$mana 6 - Parcial.docx`.
- Modificar código, reglas financieras, datos de autorización o dependencias.
- Reemplazar Singleton, Adapter, Bridge, Composite, Decorator, Facade o Flyweight.

## Tareas

- [x] 1. Documentar Flyweight (Semana 10), roles, flujo, límites y diagrama UML.
- [x] 2. Documentar Proxy de protección (Semana 11), matriz de acceso, flujo, límites y diagrama UML.
- [x] 3. Verificar ambas documentaciones contra el código, estilo/nombres/rutas y diagramas renderizables; registrar evidencia.

## Entregables previstos

- `DOCUMENTACIÓN - ENTREGABLES/Semana 10 - Flyweight.md`
- `DOCUMENTACIÓN - ENTREGABLES/Semana 10 - Flyweight UML.md`
- `DOCUMENTACIÓN - ENTREGABLES/Semana 11 - Proxy.md`
- `DOCUMENTACIÓN - ENTREGABLES/Semana 11 - Proxy UML.md`

## Evidencia

- Semana 10: `Semana 10 - Flyweight.md` y `Semana 10 - Flyweight UML.md` creados en español con Mermaid editable. Roles, términos históricos y separación del desembolso fueron contrastados con el código.
- Semana 11: `Semana 11 - Proxy.md` y `Semana 11 - Proxy UML.md` creados en español con matriz de acceso y Mermaid editable; clase, ruta, permiso, alcance y comportamiento null/404 contrastados con el código.
- El contenedor no tiene Mermaid CLI (`mmdc`); no se agregó dependencia ni se generaron PNG. Los diagramas permanecen como fuente Mermaid editable; la sintaxis se revisó visualmente, no se renderizó.
- La revisión independiente detectó y corrigió una afirmación no respaldada sobre comparar `product_id` en la hidratación Flyweight. La segunda revisión no encontró hallazgos bloqueantes en los cuatro entregables.
- Los cuatro `git diff --no-index --check /dev/null <archivo>` no reportaron errores de whitespace; salida 1 esperada por tratarse de archivos nuevos.

## Estado

Tareas 1–3 completadas. Las cuatro entregas Markdown y Mermaid editable quedaron verificadas; no se generaron PNG porque no hay renderizador local. El usuario autorizó commits locales de documentación; no se hará push.
