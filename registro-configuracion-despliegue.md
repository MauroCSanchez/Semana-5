# Registro de configuración y despliegue — App de Comentarios

**Alumno:** Sanchez Mauro

## 1. Herramienta y modo de trabajo

- Herramienta: OpenCode (app de escritorio), sobre el repo de la App de Comentarios.
- Modo para planificar: Plan Mode (agente `plan`) — analiza y propone sin modificar archivos.
- Modo para ejecutar: Build Mode (agente `build`) — con permisos para escribir y ejecutar.
- En qué momento cambiaste de modo y por qué: pasé a Plan Mode antes de encargar el andamiaje,
  para que no tocara nada hasta tener el plan revisado; volví a Build Mode recién después de
  aprobar y corregir el plan, solo para ejecutarlo.

## 2. AGENTS.md destilado

### Reglas

- Validá que `contenido` tenga entre 3 y 500 caracteres; si no, no guardes nada (origen: RN-001).
- Rechazá contenido vacío o compuesto solo por espacios (origen: RN-002).
- Exigí `autor` obligatorio de 2 a 40 caracteres (origen: RN-003).
- Permití un solo nivel de respuestas (origen: RN-004).
- Verificá que el comentario padre exista; si no existe, no guardes la respuesta (origen: RN-005).
- Verificá que el artículo exista (origen: RN-006).
- Ordená por `fechaCreacion` descendente y, en empate, por `id` descendente (origen: RN-007).
- Hacé que la validación viva en el servidor y sea la única fuente de verdad (origen: D-01).
- Calculá el orden en cada consulta; no guardes campos de orden (origen: D-02).
- Verificá el padre dentro de la misma escritura (origen: D-03).
- Usá tablas separadas para comentarios y respuestas (origen: D-04).
- Armá la conversación con una sola lectura (origen: D-05).
- Asigná `fechaCreacion` en el servidor (origen: D-06).
- Mantené la separación de módulos: presentación no valida ni escribe; persistencia opera todo o
  nada; consulta solo lee, asocia y ordena (origen: design.md §3).

### Regla que acota alcance

Declaré que una regla aplica solo a una parte del proyecto usando globs en lugar de una
instrucción general:

- `api/**/*.php`: implementá la validación de RN-001, RN-002, RN-003 y RN-005 del lado del
  servidor, con sentencias preparadas (origen: D-01).
- `sql/schema.sql`: definí dos tablas; la de respuestas con clave foránea al comentario y sin
  auto-referencia (origen: D-04).
- `js/app.js` e `index.html`: no implementes la validación como fuente de verdad; solo mostrá
  mensajes con el mismo texto que el servidor (origen: D-01 y design.md §3).

### Lo que le pediste al agente que NO haga

- No agregar autenticación, edición, borrado, moderación, likes, notificaciones, paginación,
  búsqueda, formato enriquecido, i18n ni anti-spam (origen: requirements.md §6).
- No crear ni mostrar atributos sin uso de negocio (`editado`, `likes`, `visto`, `ip`, `nivel`,
  `slug`) (origen: requirements.md §3).
- No implementar la lógica de negocio en esta etapa: los endpoints van vacíos y el SQL solo DDL.
- No decidir los pendientes del spec: escapado/XSS, conteo de los 500 caracteres, doble envío,
  origen del `idArticulo` y tope sin paginación (requirements.md §7 y design.md §5).

## 3. Prompt de Plan Mode

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

## 4. Revisión del plan

### Corrección 1

- Pedí cambiar: no heredar el modelo de datos de la Semana 1 y eliminar el endpoint `DELETE`.
- Motivo: la lógica existente violaba el spec (tabla única autorreferente, orden ASC, límites
  inventados de 100/1000 caracteres, borrado fuera de alcance).
- Resultado: `api/comments.php` quedó solo con los endpoints vacíos (listar, crear, responder) y
  sin `DELETE`.

### Corrección 2

- Pedí cambiar: dos tablas en español (`comentarios`/`respuestas`) y `idArticulo` como columna
  simple, sin tabla de artículos.
- Motivo: D-04 (respuestas sin auto-referencia) y que en la v1 la app no administra artículos
  (RN-006 no es verificable).
- Resultado: `sql/schema.sql` define ambas tablas con FK de respuestas → comentarios y `idArticulo
  INT` sin FK.

### Corrección 3

- Pedí cambiar: aplicar los ADR completos (D-01 a D-06) y no solo los del diseño propio (D-01 a
  D-04).
- Motivo: el `AGENTS.md` de la raíz exige D-05 (una sola lectura) y D-06 (fecha asignada por el
  servidor), ausentes en `specs/001-publicar-comentario/design.md`.
- Resultado: `api/queries.php` prevé obtener la conversación con una sola lectura y `api/storage.php`
  recibe la entidad con fecha puesta por el servidor.

## 5. Ejecución del andamiaje

- `index.html` — módulo de presentación: formulario y contenedor del listado (sin lógica).
- `css/style.css` — estilos nativos (estructura visual).
- `js/app.js` — módulo de presentación: stubs de render y envío; no ordena ni valida.
- `api/comments.php` — endpoints vacíos: `listarComentarios`, `crearComentario`, `crearRespuesta`.
- `api/validation.php` — firmas de las reglas: `normalizarTexto`, `validarComentario`,
  `validarRespuesta`.
- `api/storage.php` — firmas de persistencia: `guardarComentario`, `guardarRespuesta`.
- `api/queries.php` — firma de consulta y orden: `obtenerConversacion`.
- `sql/schema.sql` — DDL de las dos tablas (solo estructura).
- `config.php` — credenciales de la base (soporte).

### Desvíos entre el plan revisado y lo que se generó

- Sin desvíos de estructura. Único pendiente: la verificación de sintaxis con `php -l` no pudo
  correrse porque el CLI de PHP no está disponible en el equipo.

## 6. Contraste con el sprint de la Semana 1

### Diferencia 1: orden del listado

- Problema que pasó en la Semana 1: los comentarios se mostraban del más viejo al más nuevo
  (`ORDER BY created_at ASC`) y el árbol se reordenaba a mano en el navegador.
- Regla que lo habría evitado: RN-007 y D-02 (orden en la consulta: `fechaCreacion DESC, id DESC`).
- Evidencia de que hoy funciona: `api/queries.php` expone `obtenerConversacion` con el orden
  resuelto en la consulta y `js/app.js` no reordena nada.

### Diferencia 2: modelo de datos

- Problema que pasó en la Semana 1: una sola tabla `comments` autorreferenciada (`parent_id`)
  permitía respuestas de respuestas.
- Regla que lo habría evitado: D-04 (tablas separadas) y RN-004 (un solo nivel).
- Evidencia de que hoy funciona: `sql/schema.sql` define `comentarios` y `respuestas`, y la FK solo
  apunta al comentario.

### Diferencia 3: validación de límites

- Problema que pasó en la Semana 1: se validaban límites inventados (autor >100, contenido >1000)
  y un contenido de solo espacios se guardaba.
- Regla que lo habría evitado: RN-001, RN-002 y RN-003 (3 a 500, no vacío, autor 2 a 40).
- Evidencia de que hoy funciona: `api/validation.php` declara esas reglas y las columnas son
  `VARCHAR(40)` y `VARCHAR(500)`.

### Diferencia 4: verificación del comentario padre

- Problema que pasó en la Semana 1: se comprobaba el padre con una lectura y se insertaba después,
  en dos operaciones separadas.
- Regla que lo habría evitado: D-03 (padre verificado dentro de la misma escritura, todo o nada).
- Evidencia de que hoy funciona: `api/storage.php` prevé `guardarRespuesta` con la verificación del
  padre en la misma operación.

### Diferencia 5: alcance

- Problema que pasó en la Semana 1: existía un endpoint `DELETE` para borrar comentarios, sin que
  nadie lo pidiera.
- Regla que lo habría evitado: requirements.md §6 (borrado fuera de alcance de la v1).
- Evidencia de que hoy funciona: `api/comments.php` solo tiene listar, crear y responder; no hay
  `DELETE`.

## 7. Conclusión

En mi próxima tarea no le pido código al agente sin definir antes el requirements y el design, y
sin dejar las reglas destiladas en el `AGENTS.md` versionado. Planifico en Plan Mode, corrijo el
plan contra las RN y los D, y recién después ejecuto en Build Mode. La única deuda técnica que
arrastro es la verificación de sintaxis PHP con `php -l`, que debe correrse cuando se implemente la
lógica.