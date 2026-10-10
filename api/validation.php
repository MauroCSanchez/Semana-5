<?php
// api/validation.php — módulo de comentarios: reglas de negocio. Andamiaje sin lógica.
// Regla: D-01 (validación en el servidor = única fuente de verdad)
//        RN-001 (3 a 500), RN-002 (no vacío ni solo espacios), RN-003 (autor 2 a 40), RN-005 (padre existe).

function normalizarTexto(string $texto): string {}   // RN-002
function validarComentario(array $input): array {}   // RN-001, RN-002, RN-003
function validarRespuesta(array $input): array {}    // RN-001, RN-002, RN-003, RN-005
