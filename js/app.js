// js/app.js — módulo de presentación. Andamiaje: sin lógica todavía.
// Regla: AGENTS.md §5 (js/app.js e index.html: la validación NO es fuente de verdad)
//        y design.md §3 (solo arma el formulario, muestra el listado y los mensajes).
// El orden NO se resuelve acá: viene resuelto por la consulta (D-02 / RN-007).

const API_URL = 'api/comments.php';

function loadComments() {}                 // pide la conversación y la muestra
function submitComment(payload, form) {}   // envía comentario o respuesta (sin validar como fuente de verdad)
function renderComments(comments) {}        // render del listado ya armado
function showMessage(text, type) {}         // muestra el mensaje que devuelve el servidor
