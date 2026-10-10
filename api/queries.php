<?php
// api/queries.php — módulo de consulta y orden. Andamiaje sin lógica.
// Regla: D-05 (una sola lectura para armar la conversación)
//        D-02 + RN-007 (fechaCreacion DESC, id DESC; orden en la consulta, no en el cliente).

function obtenerConversacion(int $idArticulo): array {}   // una lectura; fechaCreacion DESC, id DESC
