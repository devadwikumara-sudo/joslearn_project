<?php

namespace App\Console\Commands;

use App\Services\CadanganDataSekolah;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('josbook:backup')]
#[Description('Buat cadangan data sekolah dalam format JSON.')]
class BuatCadanganDataSekolah extends Command
{
    public function handle(CadanganDataSekolah $cadangan): int
    {
        $backup = $cadangan->buatCadangan();

        $this->info("Cadangan {$backup->nama_file} berhasil dibuat.");

        return self::SUCCESS;
    }
}
