<?php

namespace App\Services\Pddikti;

use RuntimeException;

/** Pesan pada exception ini SELALU sudah aman ditampilkan (tanpa API key / URL berparameter). */
class PddiktiApiException extends RuntimeException
{
}