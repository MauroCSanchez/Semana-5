# AGENTS.md — App de Comentarios

Reglas destiladas de `Requirements/requirements.md` y `design.md` para la v1
(Semana 3, SDD I). Cada regla está en imperativo y segunda persona, y lleva su origen.
Origen `requirements.md` = límite de alcance. Origen `design.md` = decisión de arquitectura.
Regla acotada = glob de archivos en lugar de una instrucción general.

---

## 1. Alcance (origen: requirements.md)

- Implementá solo tres capacidades: **crear un comentario**, **listar los comentarios de un
  artículo** y **responder a un comentario**. Origen: requirements.md §1 y §6 (DENTRO del alcance).
- No agregues nada fuera del alcance v1: autenticación, edición, borrado, moderación, likes,
  notificaciones, paginación, búsqueda, formato enriquecido, i18n ni anti-spam. Origen:
  requirements.md §6 (FUERA del alcance) y secciones "Fuera de alcance" de F-COM-001/002 y F-RES-001.
- No crees ni muestres atributos sin uso de negocio (`editado`, `likes`, `visto`, `ip`, `nivel`,
  `slug`). Origen: requirements.md §3 ("Atributos que NO se listan").
- No agregues requisitos nuevos por tu cuenta: la arquitectura contesta los requisitos, no los
  amplía. Origen: requirements.md §7 y design.md (encabezado).

## 2. Reglas de negocio (origen: requirements.md)

- Validá que `contenido` tenga entre **3 y 500 caracteres**; si no, no guardes nada. Origen: RN-001.
- Rechazá el contenido **vacío o solo con espacios**; no guardes nada. Origen: RN-002.
- Exigí `autor` **obligatorio, de 2 a 40 caracteres**; si falta o es inválido, no guardes nada.
  Origen: RN-003.
- Permití **un solo nivel** de respuestas: una respuesta nunca cuelga de otra respuesta. Origen:
  RN-004.
- Verificá que el comentario padre **exista realmente**; si no existe, no guardes la respuesta.
  Origen: RN-005.
- Verificá que el artículo **exista**; si no existe, no guardes el comentario. Origen: RN-006.
- Ordená comentarios y respuestas por `fechaCreacion` **descendente** y, en empate, por `id`
  **descendente**; no confíes en el orden natural de la base. Origen: RN-007.

## 3. Arquitectura (origen: design.md, ADR)

- Hacé que la validación viva en el **servidor** y sea la **única fuente de verdad**; el navegador
  solo puede avisar como comodidad. Origen: design.md D-01.
- Calculá el **orden en cada consulta**; no guardes campos de posición u orden precalculados.
  Origen: design.md D-02.
- Verificá la existencia del padre **dentro de la misma escritura**, en una operación de todo o nada.
  Origen: design.md D-03.
- Guardá comentarios y respuestas en **tablas separadas**; la tabla de respuestas apunta al
  comentario y **no se referencia a sí misma**. Origen: design.md D-04.
- Armá la conversación del artículo con una **cantidad fija de lecturas** y asociá cada respuesta a
  su comentario padre antes de entregarla. Origen: design.md D-05.
- Asigná `fechaCreacion` en el **servidor**; el navegador no manda la fecha. Origen: design.md D-06.

## 4. Límites entre módulos (origen: design.md §3)

- En el **módulo de presentación** no valides ni escribas: solo armá el formulario, mostrá el
  listado ya armado y los mensajes. Origen: design.md §3.
- En el **módulo de comentarios** aplicá RN-001, RN-002, RN-003 y RN-005, armá la entidad completa
  y, si una regla falla, devolvé el mensaje sin escribir nada. Origen: design.md §3 y D-01.
- En el **módulo de persistencia** concentrá todo el conocimiento del almacenamiento y confirmá o
  rechazá la operación como una sola unidad. Origen: design.md §3 y D-03.
- En el **módulo de consulta y orden** solo leé, asociá y ordená; no apliques validaciones. Origen:
  design.md §3, D-02 y D-05.

## 5. Reglas acotadas (globs)

- En `api/**/**.php` (capa de reglas): implementá toda la validación de RN-001, RN-002, RN-003 y
  RN-005 del lado del servidor; usá sentencias preparadas. Origen: design.md D-01.
- En `sql/schema.sql`: definí dos tablas (comentarios y respuestas); la de respuestas con clave
  foránea al comentario y sin auto-referencia. Origen: design.md D-04.
- En `js/app.js` e `index.html`: no implementes la validación como fuente de verdad; solo mostrá
  mensajes con el mismo texto que el servidor. Origen: design.md D-01 y §3.
- En la capa de consulta (`api/**`): resolvé el orden por `fechaCreacion DESC, id DESC` en la
  consulta, no en el cliente. Origen: RN-007 y design.md D-02.

## 6. Preguntas abiertas (NO son reglas todavía)

Estos puntos están sin resolver en el spec. No los decidas solo: pedí la regla antes de implementar.
Origen: requirements.md §7 y design.md §5 (Dudas técnicas pendientes).

- Escapado de HTML / XSS (`htmlspecialchars` vs HTMLPurifier).
- Conteo de los 500 caracteres (con o sin saltos de línea y espacios).
- Doble envío del formulario.
- De dónde sale el `idArticulo` en la v1.
- Tope de resultados mientras no hay paginación.
