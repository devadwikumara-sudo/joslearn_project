@extends('layouts.app')

@section('title', 'Input Nilai Mapel — JosBook Guru')

@section('content')
<div class="bar" style="margin-bottom:16px;flex-wrap:wrap">
    <div>
        <h2 style="margin:0">Input Nilai Mapel</h2>
        <p class="sub" style="margin:4px 0 0">
            @if($mapelTerpilih && $kelasTerpilih)
                Kelas <b style="color:var(--admin-primary)">{{ $kelasTerpilih->nama }}</b> &middot; Mapel <b style="color:var(--admin-primary)">{{ $mapelTerpilih->nama }}</b> &middot; Semester {{ $semester }}
            @else
                Pilih kelas dan mata pelajaran yang Anda ajar untuk mengisi nilai
            @endif
        </p>
    </div>
    <span class="sp"></span>
    @if($kelasTerpilih && $mapelTerpilih && $siswa->isNotEmpty())
        <div style="display:inline-flex;align-items:center;gap:8px;padding:6px 14px;background:#eff6ff;border:1px solid #bfdbfe;border-radius:20px;font-size:13px;font-weight:700;color:var(--admin-primary)">
            <span>👥 {{ $siswa->count() }} Siswa Aktif</span>
        </div>
    @endif
</div>

{{-- Filter Card --}}
<div class="card" style="margin-bottom:18px">
    <form method="GET" action="{{ route('guru.input-nilai-mapel') }}" class="bar" style="flex-wrap:wrap;gap:12px">
        <div style="display:flex;flex-direction:column;gap:4px">
            <span style="font-size:11px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:0.04em">Kelas</span>
            <select name="kelas" style="min-width:180px" onchange="this.form.submit()" aria-label="Pilih Kelas">
                <option value="">— Pilih Kelas —</option>
                @foreach($kelasList as $kelas)
                    <option value="{{ $kelas->nama }}" {{ $kelasTerpilih?->id === $kelas->id ? 'selected' : '' }}>
                        Kelas {{ $kelas->nama }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($kelasTerpilih)
            <div style="display:flex;flex-direction:column;gap:4px">
                <span style="font-size:11px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:0.04em">Mata Pelajaran</span>
                <select name="mapel_id" style="min-width:220px" onchange="this.form.submit()" aria-label="Pilih Mapel">
                    <option value="">— Pilih Mapel —</option>
                    @foreach($mapelList as $mapel)
                        <option value="{{ $mapel->id }}" {{ $mapelTerpilih?->id === $mapel->id ? 'selected' : '' }}>
                            {{ $mapel->nama }} ({{ strtoupper($mapel->kode) }})
                        </option>
                    @endforeach
                </select>
            </div>
        @endif

        <div style="display:flex;flex-direction:column;gap:4px">
            <span style="font-size:11px;font-weight:700;color:var(--mu);text-transform:uppercase;letter-spacing:0.04em">Semester</span>
            <select name="semester" style="min-width:160px" onchange="this.form.submit()" aria-label="Pilih Semester">
                <option value="Ganjil" {{ $semester === 'Ganjil' ? 'selected' : '' }}>Semester Ganjil</option>
                <option value="Genap" {{ $semester === 'Genap' ? 'selected' : '' }}>Semester Genap</option>
            </select>
        </div>
        <noscript><button type="submit" class="btn o" style="margin-top:auto">Terapkan</button></noscript>
    </form>
</div>

{{-- Content Area --}}
@if($kelasTerpilih && $mapelTerpilih)
    <div class="card">
        <form method="POST" action="{{ route('guru.input-nilai-mapel.store') }}">
            @csrf
            <input type="hidden" name="kelas" value="{{ $kelasTerpilih->nama }}">
            <input type="hidden" name="mapel_id" value="{{ $mapelTerpilih->id }}">
            <input type="hidden" name="semester" value="{{ $semester }}">

            <div class="bar" style="margin-bottom:12px">
                <div style="display:flex;align-items:center;gap:8px">
                    <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    <b>Daftar Nilai Siswa</b>
                </div>
                <span class="sp"></span>
                @if($siswa->isNotEmpty())
                    <button type="submit" class="btn a">💾 Simpan Nilai</button>
                @endif
            </div>

            <div class="tw">
                <table id="tblNilai">
                    <thead>
                        <tr>
                            <th style="width:50px;text-align:center">No</th>
                            <th>Nama Siswa</th>
                            <th style="width:160px">NISN</th>
                            <th style="width:180px;text-align:center">Nilai Angka (0-100)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($siswa as $idx => $riwayat)
                            @php($nilaiTersimpan = $riwayat->nilai->first()?->nilai_angka)
                            <tr>
                                <td style="text-align:center;color:var(--mu);font-size:12px">{{ $idx + 1 }}</td>
                                <td>
                                    <b style="color:var(--admin-text);font-size:13.5px">{{ $riwayat->siswa->nama_lengkap }}</b>
                                    @if($riwayat->siswa->nomor_induk)
                                        <br><small style="color:var(--mu);font-size:11px">NIS: {{ $riwayat->siswa->nomor_induk }}</small>
                                    @endif
                                </td>
                                <td><code>{{ $riwayat->siswa->nisn ?? '-' }}</code></td>
                                <td style="text-align:center">
                                    <input
                                        type="number"
                                        name="nilai[{{ $riwayat->id }}]"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                        value="{{ old('nilai.'.$riwayat->id, $nilaiTersimpan) }}"
                                        placeholder="0"
                                        style="width:110px;text-align:center;font-weight:700;font-size:14px;padding:7px 10px;border-radius:8px;border:1.5px solid var(--admin-border);transition:all 0.15s ease"
                                    >
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" style="text-align:center;color:var(--mu);padding:32px">
                                    Tidak ada siswa aktif pada kelas ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($siswa->isNotEmpty())
                <div class="bar" style="margin-top:16px;border-top:1px solid #eef2f6;padding-top:14px">
                    <span class="sub">Pastikan data nilai sudah sesuai sebelum menyimpan ke basis data.</span>
                    <span class="sp"></span>
                    <button type="submit" class="btn a">💾 Simpan Nilai</button>
                </div>
            @endif
        </form>
    </div>
@elseif($kelasTerpilih && $mapelList->isEmpty())
    <div class="card">
        <div class="note" style="margin:0">
            ℹ Tidak ada mata pelajaran yang Anda ajar di kelas <b>{{ $kelasTerpilih->nama }}</b>. Silakan hubungi admin jika terdapat penugasan mengajar yang belum diset.
        </div>
    </div>
@else
    <div class="card" style="text-align:center;padding:48px 24px;background:#f8fafc;border:2px dashed #cbd5e1">
        <div style="font-size:36px;margin-bottom:10px">📝</div>
        <h4 style="margin:0 0 6px;color:var(--admin-text);font-size:16px">Silakan Pilih Kelas dan Mata Pelajaran</h4>
        <p class="sub" style="margin:0;max-width:460px;margin-inline:auto">Gunakan filter di atas untuk memilih rombongan belajar dan mata pelajaran yang ingin Anda inputkan nilainya.</p>
    </div>
@endif
@endsection
