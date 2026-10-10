<?php
// api/storage.php — módulo de persistencia. Andamiaje sin lógica.
// Regla: D-03 (el padre se verifica en la MISMA escritura, todo o nada)
//        D-04 (tablas separadas) y D-06 (la fecha la asigna el servidor).

function guardarComentario(array $comentario): int {}   // fecha asignada por el servidor (D-06)
function guardarRespuesta(array $respuesta): int {}     // verifica el padre en la misma escritura (D-03)
