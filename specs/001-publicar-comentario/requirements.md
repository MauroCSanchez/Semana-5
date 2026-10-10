# requirements.md — App de Comentarios

- **Nombre del proyecto:** App de Comentarios
- **Versión del documento:** 0.1-draft
- **Estado:** EN CONSTRUCCION
- **Semana:** 3 SDD I (Spec First)

---

## 1. Contexto y objetivos

_Es una página web donde cualquier persona que lee un artículo puede escribir su opinión, y esa
opinión queda publicada debajo del artículo para que otros lectores la lean y le respondan. No hay
que crear una cuenta ni saber nada de tecnología: solo escribir un nombre, escribir el texto,
enviar, y ver lo que se publicó._

_Existe porque en las publicaciones reales (periódicos, blogs, revistas) la conversación entre los
lectores aporta tanto como el propio artículo: los lectores discuten, confirman, corrigen un dato y
agregan información que el autor no escribió. Esta app es una versión mínima y local de esa función:
recibe los comentarios, los guarda y los muestra, sin más pretensiones._

_No es una red social ni una aplicación de chat: no hay perfiles, fotos, mensajes privados ni
notificaciones. Es un tablón de opiniones públicas debajo de cada texto._

Consiste en tres cosas: escribir un comentario, ver la lista de comentarios de un artículo y
responder a un comentario existente.

---

## 2. Glosario

- **Comentario:** texto que escribe un lector sobre un artículo. Es la conversación principal.
- **Respuesta:** texto que un lector escribe **debajo de un comentario**, para contestar a lo que
  dijo esa persona. Nunca es una conversación nueva: siempre cuelga de un comentario.
- **Autor:** la persona que escribe un comentario o una respuesta. En esta app el autor es solo un
  nombre que se escribe a mano (no hay que registrarse ni tener cuenta).
- **Moderador:** persona con permiso para borrar o editar comentarios ajenos. **No entra en el
  alcance de la v1.**
- **Artículo:** el texto principal que se está leyendo. En esta app es una página que ya existe en
  el sitio; se identifica por un número (`idArticulo`) y la app no los administra.

---

## 3. Entidades del dominio

### Comentario

- `id` — identificador único. Permite referenciar el comentario (al responderlo, al ordenarlo) y
  evitar duplicados.
- `idArticulo` — artículo al que pertenece. Define en qué conversación se ve.
- `autor` — nombre que muestra la persona. Se muestra junto al texto.
- `contenido` — el texto del comentario (entre 3 y 500 caracteres, según RN-001 y RN-002).
- `fechaCreacion` — cuándo se publicó. Define el orden en que se listan (F-COM-002).

### Respuesta

- `id` — identificador único de la respuesta.
- `idComentarioPadre` — comentario al que responde. Define dónde se muestra y garantiza el único
  nivel de anidamiento (RN-004).
- `autor` — nombre que muestra la persona.
- `contenido` — el texto de la respuesta (entre 3 y 500 caracteres, según RN-001 y RN-002).
- `fechaCreacion` — cuándo se publicó. Define el orden entre las respuestas de un mismo comentario.

**Atributos que NO se listan:** `editado`, `likes`, `visto`, `ip`, `nivel`, `slug`. No se listan
porque en la v1 no tienen uso de negocio: nadie los muestra, ordena ni filtra por ellos. `nivel`, por
ejemplo, es redundante: se deduce de que exista o no un `idComentarioPadre`.

### Relaciones

- Un **Comentario** pertenece a exactamente un **Artículo**.
- Un **Comentario** puede tener **cero o más Respuestas** (puede no tener ninguna).
- Una **Respuesta** pertenece a **exactamente un Comentario**.
- Una **Respuesta** **no puede tener Respuestas** (solo 1 nivel de profundidad).

---

## 4. Reglas de negocio (RN-xxx)

- **RN-001 — Límite.** El `contenido` de un comentario o de una respuesta tiene entre **3 y 500
  caracteres**. Si el texto tiene menos de 3 o más de 500 caracteres, no se guarda.
  *(Gobierna F-COM-001 y F-RES-001.)*
- **RN-002 — Validez.** No se guarda ningún comentario ni respuesta que esté **vacío o compuesto
  solo por espacios**. *(Gobierna F-COM-001 y F-RES-001.)*
- **RN-003 — Validez.** El `autor` es **obligatorio** en comentarios y respuestas, y va de **2 a 40
  caracteres**. Si falta o es inválido, no se guarda nada. *(Gobierna F-COM-001 y F-RES-001.)*
- **RN-004 — Estructura.** Las respuestas tienen **un solo nivel**: una respuesta no puede tener
  respuestas propias. Para contestar a una respuesta hay que hacerlo contra el comentario
  principal. *(Gobierna F-RES-001 y F-COM-002.)*
- **RN-005 — Integridad referencial.** Toda respuesta debe apuntar a un comentario que **exista
  realmente**. Si el comentario padre no existe, la respuesta **no se guarda**. *(Gobierna
  F-RES-001.)*
- **RN-006 — Integridad referencial.** Un comentario siempre pertenece a un **artículo existente**.
  Si el artículo no existe, el comentario no se guarda. *(Gobierna F-COM-001.)*
- **RN-007 — Orden.** Los comentarios de un artículo se listan **del más nuevo al más viejo**
  (`fechaCreacion` descendente); en empate de fecha, el de `id` mayor primero. Las respuestas de un
  comentario siguen la misma regla entre ellas. *(Gobierna F-COM-002.)*

---

## 5. Features (F-xxx-000)

### F-COM-001: Crear un comentario

Como lector, quiero escribir un comentario en un artículo, para compartir mi opinión.

Criterios de aceptación:

- **CA-1.** Dado que estoy en un artículo y escribo un autor válido (2 a 40 caracteres) y un texto de
  3 a 500 caracteres, cuando envío el formulario, entonces el comentario se guarda y aparece en el
  listado mostrando autor, texto y fecha de creación.
- **CA-2.** Dado que el texto tiene más de 500 caracteres, cuando envío el formulario, entonces se
  muestra el mensaje "El comentario no puede superar los 500 caracteres" y **no se guarda nada**.
- **CA-3.** Dado que el texto tiene menos de 3 caracteres, o está vacío o solo tiene espacios,
  cuando envío el formulario, entonces se informa que el comentario no puede estar vacío y **no se
  guarda nada**.
- **CA-4.** Dado que no escribí un autor, cuando envío el formulario, entonces se informa que el
  nombre es obligatorio y **no se guarda nada**.
- **CA-5.** Dado que el comentario se guardó correctamente, cuando vuelve a mostrar el formulario,
  entonces el campo de texto aparece vacío para escribir el siguiente.

Fuera de alcance de esta feature: edición y borrado de comentarios, autenticación de usuarios,
likes/reacciones y moderación (se tratan en versiones futuras).

---

### F-COM-002: Listar comentarios

Como lector, quiero ver los comentarios de un artículo, para enterarme de lo que dicen los demás.

Criterios de aceptación:

- **CA-1.** Dado que un artículo tiene comentarios, cuando abro el artículo, entonces se muestran
  todos sus comentarios ordenados del más nuevo al más viejo, cada uno con su autor, su texto y su
  fecha.
- **CA-2.** Dado que un artículo no tiene comentarios, cuando abro el artículo, entonces se muestra
  el mensaje "Aún no hay comentarios" y no se muestra ningún formulario de listado vacío.
- **CA-3.** Dado que un comentario tiene respuestas, cuando se muestra el listado, entonces sus
  respuestas aparecen **debajo de ese mismo comentario**, ordenadas de la más nueva a la más vieja,
  y nunca mezcladas con las respuestas de otros comentarios.
- **CA-4.** Dado que existen comentarios en otros artículos, cuando abro este artículo, entonces
  solo veo los comentarios del artículo abierto.

Fuera de alcance de esta feature: paginación, búsqueda y filtrado de comentarios, y el resaltado de
comentarios más vistos o respondidos.

---

### F-RES-001: Responder a un comentario

Como lector, quiero responder a un comentario existente, para aportar sobre lo que dijo esa persona.

Criterios de aceptación:

- **CA-1.** Dado que existe un comentario visible y escribo un autor válido y un texto de 3 a 500
  caracteres, cuando envío el formulario de respuesta, entonces la respuesta se guarda y aparece
  debajo de su comentario padre.
- **CA-2.** Dado que el texto supera los 500 caracteres o está vacío, cuando envío el formulario,
  entonces se rechaza con el mensaje correspondiente y **no se guarda nada**.
- **CA-3.** Dado que una respuesta ya tiene respuestas, cuando el lector intenta responderla, entonces
  el sistema **no ofrece la opción**: para aportar sobre esa respuesta hay que usar el formulario del
  comentario principal.
- **CA-4.** Dado que el comentario al que se intenta responder no existe, cuando envío el formulario,
  entonces la respuesta **no se guarda** y se informa que el comentario ya no está disponible.

Fuera de alcance de esta feature: responder respuestas (más de 1 nivel), menciones con `@`,
notificaciones al autor y edición o borrado de respuestas.

---

## 6. Alcance del proyecto

### DENTRO del alcance (v1)

- Creación de comentarios sobre un artículo (F-COM-001).
- Listado de comentarios del artículo, con sus respuestas anidadas (F-COM-002).
- Respuestas de un solo nivel (F-RES-001).
- Validación de contenido y autor según RN-001, RN-002 y RN-003.
- Orden del listado según RN-007.
- Un artículo fijo (o una lista mínima ya existente en el sitio) contra el que se comenta.

### FUERA del alcance (futuras versiones)

- **Autenticación de usuarios** (login, registro, contraseñas): el autor es solo un nombre escrito a
  mano en la v1.
- **Edición y borrado** de comentarios y respuestas, incluso por el propio autor.
- **Moderación y roles**: ningún usuario puede eliminar lo que escribió otro; no hay panel de
  moderación ni reportes.
- **Likes, reacciones y votos**, y contador de respuestas.
- **Notificaciones** al autor cuando alguien le responde.
- **Paginación y búsqueda** de comentarios.
- **Formato enriquecido**: negritas, imágenes, enlaces, emojis extendidos.
- **Internacionalización** y soporte multi-idioma.
- **Anti-spam y rate limiting** (salvo el límite de longitud ya definido).

---

## 7. Pendientes para Semana 4 (architect.md)

_Anotar aquí — sin resolverlos — los conflictos de alcance que despierten "¿cómo lo implemento?".
Son preguntas de arquitectura, no de requisitos._

- **Doble validación de RN-001/RN-002:** ¿la regla se aplica solo en el formulario (PHP) o también en
  JavaScript del navegador? ¿Quién es la fuente de verdad?
- **Modelo de datos:** ¿una sola tabla con tipo (`comentario` / `respuesta`) o dos tablas separadas?
  ¿Se usa llave foránea real en MySQL o se valida en el código?
- **Artículo:** ¿de dónde sale el `idArticulo`? Si no hay tabla de artículos en la v1, ¿se pasa por
  URL o se deja fija en un archivo de configuración?
- **Escapado de HTML / XSS:** ¿cómo se escapan autor y contenido para que un lector no inyecte HTML o
  scripts? ¿HTMLPurifier o `htmlspecialchars`?
- **Doble envío:** ¿qué pasa si el lector presiona "Enviar" dos veces seguidas? ¿Se deshabilita el
  botón o se valida con un tiempo mínimo?
- **Corte de líneas:** los 500 caracteres se cuentan con saltos de línea o sin ellos? ¿Se cuentan
  espacios iniciales y finales?
- **Fecha y hora:** ¿`fechaCreacion` la genera el servidor o el cliente? ¿Qué pasa con dos
  comentarios creados en el mismo segundo (RN-007)?
- **Orden de las respuestas:** ¿de más nueva a más vieja (igual que los comentarios) o en orden
  cronológico de conversación?