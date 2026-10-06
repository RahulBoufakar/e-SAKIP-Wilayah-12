<?php

/**
 * RAB Generator (docs/rab-generator, 08-rencana-implementasi.md).
 *
 * Flag untuk membangun fitur berdampingan dengan unduhan template `rab_excel`
 * lama. Selama `false`, alur lama tetap satu-satunya yang tampil.
 */
return [
    'enabled' => env('RAB_GENERATOR_ENABLED', false),
];
