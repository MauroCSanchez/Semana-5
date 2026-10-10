<?php
// api/comments.php — endpoints (router). Andamiaje: sin lógica todavía.
// Regla: AGENTS.md §5 (glob api/**/*.php = capa de reglas) y §4 (módulo de comentarios).
// GET  listar(?idArticulo)  |  POST crear comentario  |  POST crear respuesta
// Fuera del andamiaje: DELETE (fuera de alcance, requirements.md §6).

function listarComentarios(int $idArticulo) {}   // delega en queries.php
function crearComentario(array $input) {}        // delega en validation.php + storage.php
function crearRespuesta(array $input) {}         // delega en validation.php + storage.php
