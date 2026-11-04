@extends('layouts.app')

@section('title', 'Pelacakan Alumni — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Pelacakan Alumni (Tracer Study)</h2>
        <p class="sub">Pelacakan aktivitas kelanjutan studi, karir, dan wirausaha lulusan</p>
    </div>
</div>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

{{-- Form Tambah Pelacakan --}}
<div class="card">
    <h3 style="margin-bottom:12px;color:var(--p)">Tambah Data Pelacakan Alumni</h3>
    <form method="POST" action="{{ route('admin.pelacakan-alumni.store') }}">
        @csrf
        <div class="fl">
            <div>
                <label>Pilih Alumni</label>
                <select name="alumni_id" required>
                    <option value="">-- Pilih Alumni --</option>
                    <option value="1">Agung Sanggar Widinata (NISN: 0076665147)</option>
                </select>
            </div>
            <div>
                <label>Jenis Kegiatan</label>
                <select name="jenis" required>
                    <option value="Kuliah">Kuliah (Perguruan Tinggi)</option>
                    <option value="Bekerja">Bekerja</option>
                    <option value="Wirausaha">Wirausaha</option>
                    <option value="Mencari kerja">Mencari Kerja</option>
                    <option value="Lainnya">Lainnya</option>
                </select>
            </div>
            <div>
                <label>Nama Instansi / Kampus / Perusahaan</label>
                <input type="text" name="instansi" placeholder="Mis. Universitas Brawijaya / PT Telkom" required>
            </div>
        </div>
        <div class="fl" style="margin-top:10px">
            <div>
                <label>Tahun Mulai</label>
                <input type="number" name="tahun" value="{{ date('Y') }}" min="2000" max="2099" required>
            </div>
            <div>
                <label>Keterangan Tambahan</label>
                <input type="text" name="keterangan" placeholder="Jurusan / Jabatan / Bidang usaha">
            </div>
        </div>
        <div class="bar" style="margin-top:14px">
            <button type="submit" class="btn a">💾 Simpan Data Pelacakan</button>
        </div>
    </form>
</div>

{{-- Tabel Data Pelacakan --}}
<div class="card wide" style="margin-top:14px">
    <h3>Daftar Riwayat Pelacakan Alumni</h3>
    <table class="tw">
        <thead>
            <tr>
                <th>No</th>
                <th>NISN</th>
                <th>Nama Alumni</th>
                <th>Status / Jenis</th>
                <th>Instansi / Tempat</th>
                <th>Tahun</th>
                <th>Aksi</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1</td>
                <td>0076665147</td>
                <td><strong>Agung Sanggar Widinata</strong></td>
                <td><span class="bd ok">Kuliah</span></td>
                <td>Universitas Brawijaya</td>
                <td>2026</td>
                <td>
                    <button class="btn x" style="padding:3px 8px;font-size:11px">Hapus</button>
                </td>
            </tr>
        </tbody>
    </table>
</div>
@endsection

