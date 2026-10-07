<?php

return [
    // Activer après validation du HTTPS et des éventuels proxys de confiance.
    'hsts_max_age' => (int) env('SECURITE_HSTS_MAX_AGE', 0),
];
