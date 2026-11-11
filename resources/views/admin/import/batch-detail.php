@extends('layouts.app')

@section('title', 'Detail Batch Import — JosBook Admin')

@section('content')
<h2>Detail batch import #{{ $batch->id }}</h2>
<p class="sub">
    {{ ucfirst($batch->jenis) }} · {{ $tahunAjaran?->nama ?? 'Tahun ajaran tidak tercatat' }} ·
    {{ $kelas?->nama ?? 'Kelas tercatat per baris' }} · {{ $batch->nama_file }}
</p>

<div class="bar" style="margin-bottom:14px">
    <a class="btn o" href="{{ route('admin.import.'.$batch->jenis, ['tahun_ajaran_id' => $batch->tahun_ajaran_id]) }}">Kembali</a>
    @if($records->isNotEmpty())
        @if($editing)
            <a class="btn o" href="{{ route('admin.import.batch.show', $batch) }}">Selesai edit</a>
        @else
            <a class="btn o" href="{{ route('admin.import.batch.show', ['batch' => $batch->id, 'edit' => 1]) }}">Edit data batch</a>
        @endif
    @endif
</div>

@if(session('success'))
    <p class="success" role="status">{{ session('success') }}</p>
@endif

<div class="card">
    <h3>Baris file yang diimpor</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Baris Excel</th>
                    <th>Status</th>
                    <th>Data</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td>{{ $row->nomor_baris_excel }}</td>
                        <td>{{ ucfirst($row->status) }}</td>
                        <td><pre style="white-space:pre-wrap">{{ json_encode($row->data_bersih, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></td>
                        <td><pre style="white-space:pre-wrap">{{ json_encode($row->pesan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></td>
                    </tr>
                @empty
                    <tr><td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">Batch ini tidak mencatat baris file.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <h3>Data hasil tersimpan</h3>
    @if($records->isEmpty())
        <p class="sub">Tidak ada record yang dapat dipetakan secara aman ke batch ini. Batch W13 lama tetap dapat dilihat, tetapi datanya tidak bisa diedit atau dihapus lewat aksi batch.</p>
    @else
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Siswa</th>
                        <th>Kelas</th>
                        <th>Semester</th>
                        @if($batch->jenis === 'nilai')
                            <th>Mapel</th>
                            <th>Nilai</th>
                        @elseif($batch->jenis === 'kehadiran')
                            <th>Sakit</th>
                            <th>Izin</th>
                            <th>Alpa</th>
                            <th>Kokurikuler</th>
                        @else
                            <th>Kegiatan</th>
                            <th>Predikat</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($records as $record)
                        <tr>
                            <td>{{ $record->riwayatKelas?->siswa?->nama_lengkap ?? 'Siswa tidak tersedia' }}</td>
                            <td>{{ $record->riwayatKelas?->kelas?->nama ?? '—' }}</td>
                            <td>{{ $record->semester }}</td>
                            @if($batch->jenis === 'nilai')
                                <td>{{ $record->mapel?->nama ?? '—' }}</td>
                                <td>
                                    @if($editing)
                                        <form method="POST" action="{{ route('admin.import.batch.records.update', [$batch, $record->id]) }}" class="bar">
                                            @csrf
                                            @method('PUT')
                                            <input type="number" name="nilai_angka" min="0" max="100" step="0.01" value="{{ $record->nilai_angka }}" required>
                                            <button type="submit" class="btn">Simpan</button>
                                        </form>
                                    @else
                                        {{ $record->nilai_angka }}
                                    @endif
                                </td>
                            @elseif($batch->jenis === 'kehadiran')
                                @foreach(['sakit', 'izin', 'alpa'] as $field)
                                    <td>
                                        @if($editing)
                                            <form method="POST" action="{{ route('admin.import.batch.records.update', [$batch, $record->id]) }}" class="bar">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="sakit" value="{{ $record->sakit }}">
                                                <input type="hidden" name="izin" value="{{ $record->izin }}">
                                                <input type="hidden" name="alpa" value="{{ $record->alpa }}">
                                                <input type="hidden" name="kokurikuler" value="{{ $record->kokurikuler }}">
                                                <input type="number" name="{{ $field }}" min="0" max="65535" value="{{ $record->{$field} }}" required>
                                                <button type="submit" class="btn">Simpan</button>
                                            </form>
                                        @else
                                            {{ $record->{$field} }}
                                        @endif
                                    </td>
                                @endforeach
                                <td>
                                    @if($editing)
                                        <form method="POST" action="{{ route('admin.import.batch.records.update', [$batch, $record->id]) }}" class="bar">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="sakit" value="{{ $record->sakit }}">
                                            <input type="hidden" name="izin" value="{{ $record->izin }}">
                                            <input type="hidden" name="alpa" value="{{ $record->alpa }}">
                                            <textarea name="kokurikuler" maxlength="4000">{{ $record->kokurikuler }}</textarea>
                                            <button type="submit" class="btn">Simpan</button>
                                        </form>
                                    @else
                                        {{ $record->kokurikuler }}
                                    @endif
                                </td>
                            @else
                                <td>
                                    @if($editing)
                                        <form method="POST" action="{{ route('admin.import.batch.records.update', [$batch, $record->id]) }}" class="bar">
                                            @csrf
                                            @method('PUT')
                                            <input type="text" name="nama_kegiatan" maxlength="100" value="{{ $record->nama_kegiatan }}" required>
                                            <input type="text" name="predikat" maxlength="30" value="{{ $record->predikat }}">
                                            <button type="submit" class="btn">Simpan</button>
                                        </form>
                                    @else
                                        {{ $record->nama_kegiatan }}
                                    @endif
                                </td>
                                <td>{{ $record->predikat }}</td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
