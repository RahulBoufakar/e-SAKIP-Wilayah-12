<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;

trait HandlesRestrictedDeletes
{
    /**
     * Jalankan delete; kalau masih diblok FK RESTRICT (masih ada data anak),
     * redirect back dengan feedback error alih-alih exception mentah (D-4/D-5).
     */
    protected function deleteOrBlock(callable $delete, string $blockedMessage): RedirectResponse
    {
        try {
            $delete();

            return back()->with('feedback', ['type' => 'success', 'message' => 'Data berhasil dihapus.']);
        } catch (QueryException $e) {
            if ((int) $e->getCode() === 23000) {
                return back()->with('feedback', ['type' => 'error', 'message' => $blockedMessage]);
            }

            throw $e;
        }
    }
}
