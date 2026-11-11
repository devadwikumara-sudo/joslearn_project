<?php

namespace Tests\Feature;

use App\Models\Auth\Pengguna;
use App\Models\SekolahKelas;
use App\Models\SekolahTahunAjaran;
use App\Models\SiswaBiodata;
use App\Models\SiswaRiwayatKelas;
use Tests\TestCase;

class PdfOutputTest extends TestCase
{
    public function test_single_pdf_output_renders_successfully()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql.database' => 'josbook',
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');

        $admin = Pengguna::where('peran', 'admin')->first();
        if (!$admin) {
            $this->markTestSkipped('Tidak ada akun admin di mysql');
        }

        $siswa = SiswaBiodata::first();
        if (!$siswa) {
            $this->markTestSkipped('Tidak ada siswa di database');
        }

        // 1. Test single PDF
        $response = $this->actingAs($admin)->get(route('admin.data-siswa.pdf', $siswa->id));
        $response->assertStatus(200);
        $response->assertSee('I. BUKU INDUK REGISTER PESERTA DIDIK');
        $response->assertSee($siswa->nama_lengkap);

        // 2. Test batch PDF
        $responseBatch = $this->actingAs($admin)->get(route('admin.pdf.batch', ['ids' => $siswa->id]));
        $responseBatch->assertStatus(200);
        $responseBatch->assertSee($siswa->nama_lengkap);
    }
}
