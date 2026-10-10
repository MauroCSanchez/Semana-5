# design.md — App de Comentarios

_Derivado de `requirements.md` v0.1-draft (Semana 3). Las decisiones de esta semana contestan los
requisitos de esa versión; no agregan requisitos nuevos._

## 1. Arquitectura general

El sistema se organiza en tres capas separadas por responsabilidad: una capa de **entrada** que
recibe lo que escribió el lector, una capa de **reglas** que decide si ese texto se puede guardar o
no, y una capa de **almacenamiento** que lo escribe y que, cuando se la consulta, devuelve la
conversación ya completa y ya ordenada. La validación y el orden se resuelven en el servidor, nunca
en el navegador: el cliente solo muestra el formulario y los mensajes.

_Evaluación de nombres:_ el párrafo no menciona ninguna tecnología (ni lenguaje, ni framework, ni
motor de base de datos) y sigue siendo exacto. Ningún nombre es imprescindible para decidir esto: la
arquitectura descrita es la misma con cualquier pila, así que los nombres concretos aparecen recién
en la sección de alternativas de cada ADR, que es donde tiene sentido compararlos.

## 2. Decisiones técnicas (ADR)

Cada decisión de esta sección tiene las mismas cinco patas, siempre en el mismo orden y siempre con
contenido en la misma línea que la etiqueta: **Contexto**, **Decisión**, **Alternativas consideradas**,
**Justificación** y **Requisito relacionado**.

| Decisión | Contexto | Decisión | Alternativas consideradas | Justificación | Requisito relacionado |
| --- | --- | --- | --- | --- | --- |
| D-01 Validación en el servidor | Sí | Sí | Sí | Sí | RN-001, RN-002, RN-003 / F-COM-001 CA-2 a CA-4 |
| D-02 Orden calculado en la consulta | Sí | Sí | Sí | Sí | RN-007 / F-COM-002 CA-1, CA-3 |
| D-03 Padre verificado en la misma escritura | Sí | Sí | Sí | Sí | RN-004, RN-005 / F-RES-001 CA-4 |
| D-04 Comentarios y respuestas en tablas separadas | Sí | Sí | Sí | Sí | RN-004 / F-RES-001 CA-3 |
| D-05 Una sola lectura para armar la conversación | Sí | Sí | Sí | Sí | RN-007 / F-COM-002 CA-1, CA-3 |
| D-06 La fecha la asigna el servidor | Sí | Sí | Sí | Sí | RN-007 / F-COM-001 CA-1 |

### D-01 — La validación de contenido y autor vive en el servidor

- Contexto: RN-001 (3 a 500 caracteres), RN-002 (no vacío ni solo espacios) y RN-003 (autor
  obligatorio) son reglas que el cliente no puede decidir por sí solo: el texto llega al servidor
  desde el formulario, pero también podría llegar desde otro formulario o desde una URL escrita a
  mano. Los criterios CA-2, CA-3 y CA-4 de F-COM-001 no dicen solo "mostrar un aviso", dicen
  "no se guarda nada".
- Decisión: la validación ocurre íntegramente en la capa de reglas del servidor, y es la única fuente
  de verdad. El navegador puede avisar antes de enviar para ahorrar el viaje, pero si esa validación
  no existe, el resultado es el mismo: no se guarda nada.
- Alternativas consideradas: (a) solo en el navegador, con `required` y `maxlength`, descartado
  porque se puede saltear —inspeccionar la página o invocar el envío directamente— y entonces un
  texto vacío o de 5000 caracteres sí se guardaría, incumpliendo RN-001 y RN-002; (b) la misma regla
  escrita en los dos lugares, descartado porque si las dos versiones se desincronizan RN-001 deja de
  ser una regla y pasa a ser dos, así que el aviso del navegador se acepta solo como comodidad, con
  el mismo mensaje que el servidor.
- Justificación: una regla que se puede incumplir sin que nadie se entere no es una regla. El único
  punto donde se puede garantizar "no se guarda nada" es donde se escribe en el almacenamiento.
- Requisito relacionado: RN-001, RN-002, RN-003 · F-COM-001 CA-2, CA-3, CA-4 · F-RES-001 CA-2

### D-02 — El orden del listado se calcula en la consulta

- Contexto: RN-007 exige comentarios del más nuevo al más viejo, con desempate por identificador, y
  que las respuestas de un mismo comentario sigan el mismo criterio entre ellas. F-COM-002 CA-1 y
  CA-3 necesitan ese orden ya resuelto al mostrar la conversación.
- Decisión: el orden se calcula en cada consulta, ordenando por `fechaCreacion` descendente y, en
  empate, por `id` descendente. No se guarda ningún campo de posición ni de orden precalculado.
- Alternativas consideradas: (a) un campo `posicion` o `orden` precalculado y actualizado en cada
  alta, descartado porque introduce un dato que hay que sincronizar a mano en cada escritura y que
  queda inconsistente el día que exista el borrado, con un hueco en la numeración; hoy no aporta nada
  que el orden por dos columnas no dé; (b) confiar en el orden natural de inserción, descartado
  porque no es una garantía —depende del motor, del plan de ejecución y de que no haya borrados— y
  porque RN-007 exige un desempate explícito, no "lo que devuelva la base".
- Justificación: el criterio de orden se define en función de datos que ya existen en la entidad
  (`fechaCreacion`, `id`). No hay nada que precomputar y el volumen de la v1 no justifica complejidad
  adicional.
- Requisito relacionado: RN-007 · F-COM-002 CA-1, CA-3

### D-03 — La existencia del comentario padre se verifica dentro de la misma escritura

- Contexto: RN-005 exige que toda respuesta apunte a un comentario que exista realmente y, si no,
  que no se guarde; F-RES-001 CA-4 describe el caso del comentario que "ya no está disponible". A su
  vez, F-COM-002 CA-3 exige que toda respuesta se vea debajo de su comentario padre, así que una
  respuesta guardada sin padre produce un listado inconsistente.
- Decisión: la verificación del padre forma parte de la misma operación que escribe la respuesta. La
  operación es de todo o nada: o se escribe la respuesta con su padre verificado, o no se escribe
  nada. No hay un paso previo separado de "comprobar y después guardar".
- Alternativas consideradas: (a) comprobar el padre en un paso previo, con una lectura y después un
  alta, descartado porque son dos operaciones y entre una y otra el estado puede cambiar: el día que
  se agregue el borrado de comentarios —fuera de alcance hoy, pero ya anticipado— aparecería una
  ventana con respuestas apuntando a un comentario que ya no está; (b) confiar en que el formulario
  solo ofrece padres válidos, descartado porque el identificador del padre se puede mandar a mano: el
  formulario es una ayuda, no una garantía.
- Justificación: RN-005 y F-COM-002 CA-3 piden lo mismo desde dos lados (no guardar respuestas
  huérfanas y no mostrar respuestas sueltas). Resolverlo dentro de la escritura garantiza las dos
  cosas sin depender del orden en que llegan las peticiones.
- Requisito relacionado: RN-004, RN-005 · F-RES-001 CA-4 · F-COM-002 CA-3

### D-04 — Comentarios y respuestas en tablas separadas

- Contexto: RN-004 fija que las respuestas tienen un solo nivel. La regla es de estructura, no de
  comportamiento: lo que se quiere es que una respuesta no pueda tener respuestas, y eso se puede
  lograr de dos maneras distintas, por la forma de los datos o por código que lo verifique.
- Decisión: dos tablas separadas. La tabla de respuestas tiene una columna que apunta al comentario y
  no tiene ninguna columna que apunte a otra respuesta. Con esta estructura RN-004 queda garantizada
  por los datos: es imposible que exista una respuesta anidada, no hay que comprobarlo.
- Alternativas consideradas: (a) una sola tabla con un tipo (`comentario` o `respuesta`) y un único
  identificador de padre que puede apuntar a cualquiera de las dos, descartado porque el padre puede
  ser una respuesta y entonces cada lectura y cada escritura necesita una condición para distinguir
  los dos casos, y un error en esa condición rompe RN-004 sin que se note; (b) una sola tabla con dos
  columnas de padre, una para comentarios y otra para respuestas, descartado porque deja media tabla
  vacía y permite filas con las dos columnas llenas, que es justo el dato inválido que se quiere
  evitar.
- Justificación: cuando una regla dice "no puede existir X", conviene que la forma de los datos no
  permita X. La verificación por código es una promesa; la restricción de la tabla es una garantía.
- Requisito relacionado: RN-004 · F-RES-001 CA-3 · F-COM-002 CA-3

### D-05 — La conversación se arma con una sola lectura

- Contexto: F-COM-002 CA-1 pide ver los comentarios de un artículo y CA-3 pide que cada respuesta
  aparezca debajo de su comentario y nunca mezclada con las de otros. Son dos niveles de datos que
  hay que traer y asociar, y RN-007 define el orden dentro de cada nivel.
- Decisión: el módulo de consulta y orden resuelve la conversación con una sola lectura: trae los
  comentarios del artículo y sus respuestas, y las ordena antes de entregarlas, de modo que cada
  respuesta quede pegada a su comentario. La cantidad de lecturas es fija, no depende de cuántos
  comentarios hay.
- Alternativas consideradas: (a) una lectura por comentario y otra por cada respuesta, descartado
  porque con 20 comentarios son más de 40 lecturas: el tiempo de respuesta dependería de la cantidad
  de comentarios en vez de quedar acotado; (b) traer todo y filtrar en memoria, descartado porque
  mezcla artículos y no permite acotar el resultado.
- Justificación: CA-3 exige una asociación explícita entre cada respuesta y su padre. Hacer esa
  asociación en un solo lugar, antes de mostrar, es lo que garantiza que las respuestas no se mezclen.
- Requisito relacionado: RN-007 · F-COM-002 CA-1, CA-3

### D-06 — La fecha de creación la asigna el servidor

- Contexto: RN-007 ordena la conversación por `fechaCreacion`, y F-COM-002 CA-1 muestra esa fecha al
  lector. El texto con su fecha puede llegar juntos desde el formulario, y el navegador es parte del
  lado que no controlamos.
- Decisión: la fecha la asigna el servidor en el momento de escribir; el navegador no manda la fecha.
  El desempate por `id` de RN-007 cubre el caso de dos comentarios escritos en el mismo instante.
- Alternativas consideradas: (a) que el navegador mande la fecha, descartado porque un lector puede
  mandar cualquier fecha y con ella alterar el orden de toda la conversación, violando RN-007; (b) que
  la fecha la ponga el almacenamiento con un valor por defecto, equivalente en resultado pero deja el
  valor invisible para el código, que lo necesita para ordenar y para mostrar, así que se prefiere que
  la entidad quede completa al salir del módulo de comentarios.
- Justificación: RN-007 depende de que la fecha sea confiable, y el único reloj del sistema es el del
  servidor.
- Requisito relacionado: RN-007 · F-COM-001 CA-1 · F-COM-002 CA-1

## 3. Estructura de módulos

- **Módulo de presentación** — arma el formulario (autor y texto), muestra el listado ya armado y
  muestra los mensajes de rechazo que le entrega el módulo de reglas. No decide nada: no valida por
  su cuenta ni escribe en el almacenamiento.
- **Módulo de comentarios (recepción y validación)** — recibe el envío de un comentario o de una
  respuesta, normaliza el texto, aplica RN-001, RN-002 y RN-003 y arma la entidad completa: artículo,
  autor, contenido y fecha (ver D-06). Si una regla falla, devuelve el mensaje correspondiente y no
  llega a escribir nada (ver D-01). Para las respuestas, además exige que el comentario padre exista
  (RN-005).
- **Módulo de persistencia** — escribe comentarios y respuestas contra el almacenamiento y confirma
  o rechaza la operación como una sola unidad: nunca deja una respuesta guardada sin su comentario
  padre (ver D-03). Es el único módulo que conoce cómo están guardadas las cosas.
- **Módulo de consulta y orden** — lee la conversación completa del artículo en una sola operación
  (ver D-05) y la ordena según RN-007 antes de entregarla (ver D-02). No trae comentarios de otros
  artículos ni respuestas de otros comentarios, y no aplica reglas de validación: solo lee, asocia y
  ordena.

## 4. Fuera de alcance técnico

- **Cachear el listado de comentarios** (guardar la conversación ya armada en memoria para no
  releerla) — se descartó porque el caché hay que invalidarlo en cada alta de comentario y en cada
  respuesta, y el problema que efectivamente lo justificaría (muchos comentarios en un artículo y
  consultas lentas) no está en el alcance de la v1. La complejidad del invalidado es mayor que el
  beneficio.
- **Guardar el orden precalculado en un campo** — se descartó porque es el dato que nadie va a
  mantener y el primero que queda mal cuando exista el borrado (ver D-02).
- **Poner las reglas de negocio dentro del almacenamiento** (procedimientos almacenados o
  disparadores) — se descartó porque RN-001 a RN-005 son reglas de negocio y conviene que estén en un
  solo lugar legible desde el código; una misma regla repartida entre el motor y la aplicación se
  contradice en cuanto una de las dos versiones queda desactualizada.
- **Framework completo con ORM y gestión de usuarios integrada** — se descartó porque el alcance de
  la v1 excluye autenticación y el modelo es de dos entidades simples; el framework aportaría
  estructura para cosas que no están pedidas.
- **Envío sin recargar la página** (el comentario nuevo aparece en el listado al instante) — se
  descartó porque ningún requisito lo pide: F-COM-001 CA-1 se cumple con que el comentario aparezca en
  el listado tras enviar, y recargar la página ya lo garantiza. Agregar estado en el navegador es
  complejidad sin requisito que la justifique.
- **Una lectura por comentario para mostrar la conversación** — se descartó porque la cantidad de
  lecturas crece con la cantidad de comentarios (ver D-05).

## 5. Dudas técnicas pendientes

- **Dos envíos en el mismo instante:** si un lector presiona "Enviar" dos veces muy rápido, ¿se guardan
  dos comentarios o uno? Hoy no hay requisito que lo diga (RN-001 a RN-007 no lo cubren) y el desempate
  por `id` de RN-007 no lo resuelve. ¿Hace falta un requisito anti-doble-envío, o alcanza con
  deshabilitar el botón en el navegador?
- **Dos comentarios con la misma fecha:** D-06 deja la fecha en manos del servidor, pero falta
  confirmar que el `id` sea correlativo y crezca con la inserción. Si algún día el identificador no
  garantice orden temporal, el desempate de RN-007 deja de ser confiable.
- **Qué caracteres se permiten escribir:** no hay requisito que diga si se puede escribir HTML, qué
  pasa con los saltos de línea ni cuántos caracteres cuentan. No se puede decidir desde la
  arquitectura porque falta la regla: hay que agregar el requisito correspondiente (qué se escapa y qué
  no, y si los saltos de línea cuentan para el límite de 500 caracteres) antes de elegir la solución
  técnica.
- **De dónde sale el artículo:** `idArticulo` aparece en la entidad y en RN-006, pero en la v1 no está
  definido si el artículo vive en una tabla, si se pasa por la dirección web o si es fijo. Sin eso,
  RN-006 no es verificable.
- **Crecer sin paginación:** la paginación está fuera de alcance, pero RN-007 implica que la consulta
  trae todos los comentarios del artículo. ¿Hace falta poner un tope ("se muestran los últimos N")
  para que la v1 no se caiga cuando un artículo se llena de comentarios?