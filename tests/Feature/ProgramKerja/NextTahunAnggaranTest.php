<?php

use App\Http\Controllers\Concerns\GatesUsulanProgramKerja;

it('melaporkan ketersediaan tahun anggaran berikutnya berdasarkan data yang ada', function () {
    $tester = new class {
        use GatesUsulanProgramKerja;

        public function check(int $id): bool
        {
            return $this->nextTahunAnggaranExists($id);
        }
    };

    $tahunIni = makeTahunAnggaran(2026);

    expect($tester->check($tahunIni->id))->toBeFalse();

    makeTahunAnggaran(2027);

    expect($tester->check($tahunIni->id))->toBeTrue();
});