@extends('layouts.app')

@section('title', 'Tempat Sampah — JosBook Admin')

@section('content')
<h2>Tempat Sampah</h2>
<p class="sub">Data siswa dan guru yang dihapus tersimpan di sini. Anda dapat memulihkan data sewaktu-waktu atau menghapusnya secara permanen.</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

@if(session('error'))
<div class="note err">{{ session('error') }}</div>
@endif

<div class="card">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Jenis</th>
                    <th>Waktu Dihapus</th>
                    <th style="width:190px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @php $adaData = false; @endphp

                {{-- Siswa terhapus --}}
                @foreach($siswaTerhapus ?? [] as $s)
                @php $adaData = true; @endphp
                <tr>
                    <td>
                        <strong>{{ $s->nama_lengkap }}</strong>
                        <br><small style="color:var(--mu)">NISN: {{ $s->nisn }} &middot; Nomor Induk: {{ $s->nomor_induk ?: '-' }}</small>
                    </td>
                    <td><span class="bd">Siswa</span></td>
                    <td>{{ $s->deleted_at ? $s->deleted_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.sampah.pulihkan') }}" style="display:inline">
                            @csrf
                            <input type="hidden" name="tipe" value="siswa">
                            <input type="hidden" name="id" value="{{ $s->id }}">
                            <button type="submit" class="btn a" style="padding:4px 8px;font-size:12px">Pulihkan</button>
                        </form>
                        <form method="POST" action="{{ route('admin.sampah.hapus-permanen') }}" style="display:inline" onsubmit="return confirm('Hapus permanen siswa {{ $s->nama_lengkap }}? Tindakan ini tidak dapat dibatalkan.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="tipe" value="siswa">
                            <input type="hidden" name="id" value="{{ $s->id }}">
                            <button type="submit" class="btn x" style="padding:4px 8px;font-size:12px">Hapus Permanen</button>
                        </form>
                    </td>
                </tr>
                @endforeach

                {{-- Guru terhapus --}}
                @foreach($guruTerhapus ?? [] as $g)
                @php $adaData = true; @endphp
                <tr>
                    <td>
                        <strong>{{ $g->nama }}</strong>
                        <br><small style="color:var(--mu)">NIP: {{ $g->nip ?: '-' }} &middot; NUPTK: {{ $g->nuptk ?: '-' }}</small>
                    </td>
                    <td><span class="bd ok">Guru</span></td>
                    <td>{{ $g->deleted_at ? $g->deleted_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        <form method="POST" action="{{ route('admin.sampah.pulihkan') }}" style="display:inline">
                            @csrf
                            <input type="hidden" name="tipe" value="guru">
                            <input type="hidden" name="id" value="{{ $g->id }}">
                            <button type="submit" class="btn a" style="padding:4px 8px;font-size:12px">Pulihkan</button>
                        </form>
                        <form method="POST" action="{{ route('admin.sampah.hapus-permanen') }}" style="display:inline" onsubmit="return confirm('Hapus permanen guru {{ $g->nama }}? Tindakan ini tidak dapat dibatalkan.')">
                            @csrf
                            @method('DELETE')
                            <input type="hidden" name="tipe" value="guru">
                            <input type="hidden" name="id" value="{{ $g->id }}">
                            <button type="submit" class="btn x" style="padding:4px 8px;font-size:12px">Hapus Permanen</button>
                        </form>
                    </td>
                </tr>
                @endforeach

                @if(!$adaData)
                <tr>
                    <td colspan="4" style="text-align:center;color:var(--mu);padding:30px">
                        Tempat sampah kosong. Tidak ada data yang dihapus.
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>
</div>
@endsection

