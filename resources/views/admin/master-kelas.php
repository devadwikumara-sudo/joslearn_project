@extends('layouts.app')

@section('title', 'Master Kelas & Mapel — JosBook Admin')

@section('content')
<div class="hero2">
    <div>
        <h2>Master Kelas &amp; Mata Pelajaran</h2>
        <p class="sub">Kelola daftar rombongan belajar dan kurikulum mata pelajaran sekolah</p>
    </div>
</div>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

<div class="g g2">
    {{-- Card Kiri: Master Kelas --}}
    <div class="card">
        <h3>Master Kelas / Rombel</h3>
        <p class="sub">Tambah atau nonaktifkan kelas</p>

        <form method="POST" action="{{ route('admin.master-kelas.store') }}" style="margin-bottom:14px">
            @csrf
            <input type="hidden" name="type" value="kelas">
            <div class="bar">
                <input type="text" name="nama_kelas" placeholder="Format: X-1, XI-2..." required style="flex:1">
                <button type="submit" class="btn a">+ Tambah Kelas</button>
            </div>
            <small style="color:var(--mu);display:block">Standar penamaan: Tingkat (X / XI / XII) diikuti nomor urut.</small>
        </form>

        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Kelas</th>
                        <th>Tingkat</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(['X-1', 'XI-1', 'XII-1'] as $idx => $k)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td><strong>{{ $k }}</strong></td>
                        <td>{{ explode('-', $k)[0] }}</td>
                        <td>
                            <button class="btn x" style="padding:2px 8px;font-size:11px" onclick="return confirm('Hapus kelas?')">Hapus</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Card Kanan: Master Mapel --}}
    <div class="card">
        <h3>Master Mata Pelajaran</h3>
        <p class="sub">Kelola kode dan nama mata pelajaran</p>

        <form method="POST" action="{{ route('admin.master-kelas.store') }}" style="margin-bottom:14px">
            @csrf
            <input type="hidden" name="type" value="mapel">
            <div class="fl" style="margin-bottom:8px">
                <div>
                    <label>Kode (3 huruf)</label>
                    <input type="text" name="kode" placeholder="mtk" maxlength="3" required style="width:100%">
                </div>
                <div>
                    <label>Nama Mata Pelajaran</label>
                    <input type="text" name="nama" placeholder="Matematika" required style="width:100%">
                </div>
                <div>
                    <label>Tingkat</label>
                    <select name="tingkat" style="width:100%">
                        <option value="Semua">Semua Tingkat</option>
                        <option value="X">Kelas X</option>
                        <option value="XI">Kelas XI</option>
                        <option value="XII">Kelas XII</option>
                    </select>
                </div>
            </div>
            <button type="submit" class="btn a">+ Tambah Mapel</button>
        </form>

        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Mapel</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                    $sampleMapel = [
                        ['agm', 'Pendidikan Agama dan Budi Pekerti'],
                        ['pnc', 'Pendidikan Pancasila'],
                        ['ind', 'Bahasa Indonesia'],
                        ['mtk', 'Matematika'],
                        ['ipa', 'Ilmu Pengetahuan Alam (IPA)'],
                        ['ips', 'Ilmu Pengetahuan Sosial (IPS)'],
                        ['big', 'Bahasa Inggris'],
                        ['pjk', 'PJOK'],
                        ['inf', 'Informatika'],
                    ];
                    @endphp
                    @foreach($sampleMapel as $m)
                    <tr>
                        <td><code>{{ $m[0] }}</code></td>
                        <td>{{ $m[1] }}</td>
                        <td>
                            <button class="btn x" style="padding:2px 8px;font-size:11px" onclick="return confirm('Hapus mapel?')">Hapus</button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

