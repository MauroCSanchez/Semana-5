# Registro de configuración y despliegue — App de Comentarios

> Documento de la actividad de la Semana 3 (SDD I — Spec First).
> Fuentes: `Requirements/requirements.md` (v0.1-draft) y `design.md`.
> Alcance de este registro: cómo pasé del spec al andamiaje del proyecto usando OpenCode,
> dejando el razonamiento de cada decisión por escrito.

---

## 1. Herramienta y modo de trabajo

- **Herramienta:** OpenCode (TUI). Se ejecuta desde la raíz del proyecto con `opencode`.
- **Forma de trabajo:** *Spec First* / SDD. El orden de las etapas es fijo:
  1. `requirements.md` (el **qué**: features, reglas y alcance).
  2. `design.md` (el **cómo**: arquitectura y ADR).
  3. `AGENTS.md` **destilado** (las reglas durables, versionadas en el repo).
  4. **Plan Mode** para planificar el andamiaje sin tocar archivos.
  5. Revisión del plan y ejecución del andamiaje.
- **Agentes de OpenCode que usé:**
  - **Build** (por defecto): todos los tools habilitados, para ejecutar cambios.
  - **Plan**: agente restringido. `edit` y `bash` quedan en `ask`/`deny`, así que analiza y
    propone sin modificar el repo. Se cambia con la tecla **Tab** (cicla entre agentes).
- **Cómo creé el `AGENTS.md`:** lo escribí a mano en la raíz del repo (alternativa: el comando
  `/init` de la TUI, que lo genera guiado). Va commiteado, porque un `AGENTS.md` que vive solo en
  mi máquina no es una regla del proyecto. Nota: la carpeta todavía **no es un repo Git**, así que
  requiere `git init` antes de versionarlo.
- **Por qué este modo y no otro:** el sprint de la Semana 1 mostró que pedir código "sin contexto
  y sin especificación" produce resultados que parecen funcionar pero violan reglas que nunca se
  dijeron. Acá el spec existe **antes** de escribir código, y las reglas quedan en un archivo que
  el agente vuelve a leer en cada sesión.

---

## 2. AGENTS.md destilado (reglas + origen de cada una)

Recorrí el spec y extraje las decisiones que se sostienen en todo el proyecto. El archivo completo
quedó en la raíz como `AGENTS.md`. Las reglas, cada una con su origen:

### 2.1 Alcance (origen: requirements.md)

| # | Regla (imperativo, 2ª persona) | Origen |
| --- | --- | --- |
| 1 | Implementá solo tres capacidades: crear comentario, listar comentarios y responder. | requirements.md §1, §6 |
| 2 | No agregues autenticación, edición, borrado, moderación, likes, notificaciones, paginación, búsqueda, formato enriquecido, i18n ni anti-spam. | requirements.md §6 "FUERA del alcance" |
| 3 | No crees ni muestres atributos sin uso de negocio (`editado`, `likes`, `visto`, `ip`, `nivel`, `slug`). | requirements.md §3 |
| 4 | No agregues requisitos nuevos por tu cuenta. | requirements.md §7, design.md (encabezado) |

### 2.2 Reglas de negocio (origen: requirements.md)

| # | Regla | Origen |
| --- | --- | --- |
| 5 | Validá `contenido` entre 3 y 500 caracteres; si no, no guardes nada. | RN-001 |
| 6 | Rechazá contenido vacío o solo con espacios. | RN-002 |
| 7 | Exigí `autor` obligatorio, de 2 a 40 caracteres. | RN-003 |
| 8 | Permití un solo nivel de respuestas. | RN-004 |
| 9 | Verificá que el comentario padre exista; si no, no guardes. | RN-005 |
| 10 | Verificá que el artículo exista; si no, no guardes el comentario. | RN-006 |
| 11 | Ordená por `fechaCreacion` desc y, en empate, por `id` desc. | RN-007 |

### 2.3 Arquitectura (origen: design.md, ADR)

| # | Regla | Origen |
| --- | --- | --- |
| 12 | La validación vive en el servidor y es la única fuente de verdad. | D-01 |
| 13 | El orden se calcula en cada consulta; no guardes campos de orden. | D-02 |
| 14 | Verificá el padre dentro de la misma escritura (todo o nada). | D-03 |
| 15 | Comentarios y respuestas en tablas separadas; la de respuestas no se auto-referencia. | D-04 |
| 16 | Armá la conversación con una cantidad fija de lecturas. | D-05 |
| 17 | La fecha la asigna el servidor, no el navegador. | D-06 |

### 2.4 Límites entre módulos (origen: design.md §3)

| # | Regla | Origen |
| --- | --- | --- |
| 18 | Presentación: no valida ni escribe; solo formula, muestra listado y mensajes. | design.md §3 |
| 19 | Comentarios: valida RN-001/002/003/005 y arma la entidad; si falla, no escribe. | design.md §3, D-01 |
| 20 | Persistencia: único módulo que conoce el almacenamiento; opera todo o nada. | design.md §3, D-03 |
| 21 | Consulta y orden: solo lee, asocia y ordena; no valida. | design.md §3, D-02, D-05 |

### 2.5 Reglas acotadas (globs de archivo)

| # | Regla | Glob | Origen |
| --- | --- | --- | --- |
| 22 | Toda la validación de servidor y sentencias preparadas. | `api/**/*.php` | D-01 |
| 23 | Dos tablas, FK al comentario, sin auto-referencia. | `sql/schema.sql` | D-04 |
| 24 | No poner la validación como fuente de verdad; solo mensajes. | `js/app.js`, `index.html` | D-01, §3 |
| 25 | Orden resuelto en la consulta, no en el cliente. | `api/**/*.php` | RN-007, D-02 |

> **Pendientes que NO convertí en regla** (el spec los deja abiertos, no los inventé): escapado
> de HTML/XSS, conteo de los 500 caracteres, doble envío, origen del `idArticulo` y tope sin
> paginación. Origen: requirements.md §7 y design.md §5.

---

## 3. Prompt de Plan Mode (la encargo, con referencias explícitas)

Para esto cambié a **Plan Mode** con **Tab** (así el agente planea sin escribir archivos). El
prompt que usé, con referencias `@` explícitas a los archivos que el plan debe respetar:

```text
@AGENTS.md @Requirements/requirements.md @design.md @README.md
@index.html @js/app.js @api/comments.php @sql/schema.sql @config.php

Actuá en modo Plan. NO escribas ni modifiques archivos todavía: quiero un plan de andamiaje.

Objetivo: dejar el esqueleto de la v1 de la "App de Comentarios" (stack actual: HTML/CSS/JS
vanilla + PHP 8 + MySQL 8) SIN lógica de negocio, solo estructura.

Restricciones (leelas desde @AGENTS.md y @design.md y no las contradigas):
- Alcance v1: crear comentario, listar comentarios y responder (1 nivel). Nada más.
- La validación vive en el servidor (D-01) y el orden se calcula en la consulta (D-02).
- El padre se verifica dentro de la misma escritura (D-03).
- Comentarios y respuestas en tablas separadas (D-04).
- La conversación se arma con una sola lectura (D-05) y la fecha la pone el servidor (D-06).

Entregame:
1. Archivos/módulos finales y responsabilidad de cada uno, mapeados a los módulos de design.md §3.
2. Las firmas (funciones/columnas) de cada uno, con cuerpos vacíos o TODO.
3. El `sql/schema.sql` con las dos tablas (solo estructura).
4. Qué queda FUERA del andamiaje (cualquier cosa fuera de alcance: DELETE, paginación, etc.).
5. Las decisiones donde dudes y por qué, para que yo las revise.
```

---

## 4. Revisión del plan (mis correcciones y por qué)

Leí el plan que devolvió Plan Mode. Lo tomé como borrador y lo corregí contra el spec:

| # | Lo que proponía el plan | Mi corrección | Por qué |
| --- | --- | --- | --- |
| 1 | Reutilizar la tabla única `comments` con `parent_id` (lo que ya existía del sprint 1). | Dos tablas separadas: comentarios y respuestas. | D-04: una sola tabla autorelacionada permite anidar respuestas, justo lo que RN-004 prohíbe. La forma de los datos debe impedirlo, no el código. |
| 2 | Poner la validación principal en el formulario (JS/HTML). | El servidor es la única fuente de verdad; el cliente solo muestra mensajes. | D-01: `required`/`maxlength` se saltean; el único punto que garantiza "no se guarda nada" es el servidor. |
| 3 | Ordenar con `ORDER BY created_at ASC` (copia del código viejo). | `ORDER BY fechaCreacion DESC, id DESC`. | RN-007: el orden exigido es del más nuevo al más viejo, con desempate por id. |
| 4 | Verificar el padre con una lectura y después el insert. | Verificación dentro de la misma escritura, todo o nada. | D-03: dos operaciones dejan una ventana donde el padre puede dejar de existir. |
| 5 | Asignar la fecha con `DEFAULT CURRENT_TIMESTAMP` en la base. | El servidor asigna la fecha y la entidad sale completa del módulo de comentarios. | D-06: el código necesita la fecha para ordenar y mostrar; no debe quedar invisible para la app. |
| 6 | Conservar el endpoint `DELETE` del código anterior. | Eliminarlo del andamiaje. | Borrado está fuera del alcance v1 (requirements.md §6). |
| 7 | No incluir `idArticulo` ni el filtrado por artículo. | Agregar el artículo a la entidad y el filtrado por artículo en la consulta. | RN-006 y F-COM-002 CA-4: un comentario pertenece a un artículo y el listado es por artículo. |
| 8 | Mezclar lectura y validación en un mismo módulo. | Separar en cuatro módulos (presentación, comentarios, persistencia, consulta y orden). | design.md §3: cada módulo con una sola responsabilidad evita que la validación se filtre a la lectura. |

Criterio general de la revisión: cada vez que el plan contradecía un **RN** (regla de negocio) o
un **D** (decisión de arquitectura), ganaba el spec, no el código que ya existía.

---

## 5. Ejecución del andamiaje (qué archivos/módulos quedaron, sin lógica)

Ejecuté el plan ya corregido en **Build Mode**. Quedó el esqueleto, **sin lógica de negocio**:
los cuerpos de las funciones son TODO y el SQL solo define estructura.

```text
app-comentarios/
├── AGENTS.md              → reglas destiladas del proyecto (sección 2)
├── index.html             → MÓDULO PRESENTACIÓN: formulario + contenedor del listado
├── config.php             → credenciales de la base
├── css/
│   └── style.css          → estilos nativos (sin cambios de diseño)
├── js/
│   └── app.js             → MÓDULO PRESENTACIÓN: render y fetch (sin reglas de validación)
├── api/
│   ├── comments.php       → MÓDULO COMENTARIOS: routing GET/POST (sin DELETE)
│   ├── validation.php     → MÓDULO COMENTARIOS: firmas de RN-001, RN-002, RN-003, RN-005
│   ├── storage.php        → MÓDULO PERSISTENCIA: alta atómica (D-03) y D-04
│   └── queries.php        → MÓDULO CONSULTA Y ORDEN: una lectura (D-05), orden (D-02)
└── sql/
    └── schema.sql         → DOS tablas: comentarios y respuestas (D-04), solo estructura
```

Mapa módulo → ADR que lo justifica:

- **Presentación** (`index.html`, `js/app.js`): no valida ni escribe (design.md §3, D-01).
- **Comentarios** (`api/comments.php`, `api/validation.php`): recibe, valida y arma la entidad;
  si una regla falla no escribe (D-01, RN-001/002/003/005).
- **Persistencia** (`api/storage.php`): único que conoce el almacenamiento; operación todo o nada
  (D-03, D-04).
- **Consulta y orden** (`api/queries.php`): solo lee, asocia y ordena (D-02, D-05).

Lo que **no** dejé en el andamiaje, a propósito: validación cliente como fuente de verdad, `DELETE`,
paginación, búsqueda, likes, y cualquier lógica de negocio dentro de los cuerpos de función.

---

## 6. Contraste con el sprint de la Semana 1

En la Semana 1 le pedí a OpenCode una feature **sin spec ni contexto** ("agregá responder a un
comentario"), con el objetivo de que fallara. El código resultante quedó en el repo y **viola el
spec de hoy** en varios puntos concretos y comprobables:

| Aspecto | Semana 1 (vibe coding) | Semana 3 (spec + verificación) | Regla que se violaba en S1 |
| --- | --- | --- | --- |
| Modelo de datos | Una tabla `comments` con `parent_id` autorreferente (`sql/schema.sql`). | Dos tablas separadas. | D-04 / RN-004 |
| Orden del listado | `ORDER BY created_at ASC` (`api/comments.php`). | `fechaCreacion DESC, id DESC`. | RN-007 / D-02 |
| Límites | autor > 100 y contenido > 1000; sin mínimo. | autor 2–40 y contenido 3–500. | RN-001 / RN-003 |
| Contenido solo espacios | Se guarda (se recorta a vacío y no se rechaza). | Se rechaza. | RN-002 |
| Verificación del padre | Lectura previa y luego insert (dos pasos). | Dentro de la misma escritura. | D-03 / RN-005 |
| Borrado | Endpoint `DELETE` presente. | No existe. | Fuera de alcance (§6) |
| Artículo | El concepto de artículo no aparece. | `idArticulo` presente y filtrado. | RN-006 / F-COM-002 CA-4 |
| Orden | Parte del orden se resuelve en el cliente (`buildCommentTree`). | Orden resuelto en la consulta. | D-02 / D-05 |

**Lectura del contraste:** el problema del sprint 1 no fue que el código "no anduviera" —de hecho
la funcionalidad de responder aparecía— sino que **inventó estructura y reglas** donde el spec no
había hablado, y **rompió** reglas que sí existían (orden y límites). Con el spec y el `AGENTS.md`
por delante, esas mismas decisiones pasaron a ser explícitas y verificables.

---

## 7. Conclusión: qué cambia en mi próxima tarea

- **Empiezo por el spec, no por el código.** Antes de pedir nada escribo/confirmo el `requirements.md`
  y el `design.md`; sin RN y sin ADR no arranco.
- **Destilo el `AGENTS.md` antes de planificar**, y lo dejo versionado en el repo para que cada
  sesión del agente parta de las mismas reglas.
- **Planifico en Plan Mode y reviso el plan contra el spec** antes de ejecutar. La revisión es la
  parte donde más se gana: es donde detecto que el plan copia vicios del código viejo (tabla única,
  orden ASC, `DELETE`).
- **Verifico cada decisión contra un origen.** Si una regla no sale de un RN o de un D, no entra:
  pedido abierto = se anota como pendiente, no se inventa.
- **Uso globs para reglas acotadas** en el `AGENTS.md`, en lugar de una instrucción general que
  aplique a todo el proyecto.
- **Contrasto siempre con el resultado del vibe coding.** El sprint 1 es la línea base de "lo que
  no quiero": código que aparenta andar pero contradice el alcance y las reglas.
