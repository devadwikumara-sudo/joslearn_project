@php
    $tahunTerpilih = $tahunAjaranList->firstWhere('id', $tahunAjaranTerpilihId);
    $jenisLabel = [
        'nilai' => 'Nilai',
        'kehadiran' => 'Kehadiran',
        'ekskul' => 'Ekskul',
    ][$type];
    $hapusSemuaLabel = "HAPUS SEMUA ".mb_strtoupper($jenisLabel)." ".($tahunTerpilih?->nama ?? '');
@endphp

<div class="card" style="margin-top:14px">
    <h3>Data hasil impor {{ $tahunTerpilih?->nama ?? '' }}</h3>
    <div class="bar" style="margin-bottom:12px">
        <form method="GET" action="{{ route($indexRoute) }}" class="bar">
            <label for="tahun-ajaran-impor">Tahun ajaran</label>
            <select id="tahun-ajaran-impor" name="tahun_ajaran_id" onchange="this.form.submit()">
                @foreach($tahunAjaranList as $tahunAjaran)
                    <option value="{{ $tahunAjaran->id }}" @selected((int) $tahunAjaran->id === (int) $tahunAjaranTerpilihId)>
                        {{ $tahunAjaran->nama }}
                    </option>
                @endforeach
            </select>
        </form>

        @if($tahunTerpilih)
            <form method="POST" action="{{ route('admin.import.batch.destroy-all', ['jenis' => $type]) }}" class="batch-delete-all-form">
                @csrf
                @method('DELETE')
                <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunTerpilih->id }}">
                <label for="konfirmasi-hapus-semua">Ketik <strong>{{ $hapusSemuaLabel }}</strong> untuk menghapus seluruh {{ mb_strtolower($jenisLabel) }} tahun ini.</label>
                <input id="konfirmasi-hapus-semua" name="konfirmasi" type="text" required autocomplete="off">
                <button type="submit" class="btn danger">Hapus semua {{ $jenisLabel }} tahun ini</button>
            </form>
        @endif
    </div>

    @if(session('success'))
        <p class="success" role="status">{{ session('success') }}</p>
    @endif
    @if(session('error'))
        <p class="error" role="alert">{{ session('error') }}</p>
    @endif

    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Semester</th>
                    <th>Baris</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    @php
                        $batchCanBeEdited = $batch->status === 'selesai' && $batch->data_tercatat > 0;
                        $batchCanBeDeleted = $batch->status === 'selesai' && $batch->data_dibuat > 0;
                    @endphp
                    <tr>
                        <td>{{ $batch->kelas_label ?: 'Beragam / dari file' }}</td>
                        <td>{{ $batch->semester_label ?? ($batch->semester ?: 'Sesuai data baris') }}</td>
                        <td>
                            <strong>{{ $batch->data_tercatat > 0 ? $batch->data_tercatat : $batch->jumlah_tersimpan }}</strong>
                            @if($batch->data_tercatat === 0)
                                <span class="sub" title="Catatan batch lama tidak memiliki tautan yang aman ke data hasil.">Belum terlacak</span>
                            @endif
                        </td>
                        <td>
                            <div class="bar">
                                <a class="btn o" href="{{ route('admin.import.batch.show', $batch) }}">Lihat</a>
                                @if($batchCanBeEdited)
                                    <a class="btn o" href="{{ route('admin.import.batch.show', ['batch' => $batch->id, 'edit' => 1]) }}">Edit</a>
                                @else
                                    <button type="button" class="btn o" disabled title="Data batch ini belum memiliki jejak record yang aman untuk diedit.">Edit</button>
                                @endif
                                @if($batchCanBeDeleted)
                                    <form method="POST" action="{{ route('admin.import.batch.destroy', $batch) }}" class="batch-delete-form" data-batch-id="{{ $batch->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <input type="hidden" name="tahun_ajaran_id" value="{{ $tahunTerpilih->id }}">
                                        <input type="hidden" name="konfirmasi" value="">
                                        <button type="submit" class="btn danger">Hapus batch</button>
                                    </form>
                                @else
                                    <button type="button" class="btn danger" disabled title="Batch ini tidak membuat record baru yang aman untuk dihapus.">Hapus batch</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                            Belum ada batch {{ mb_strtolower($jenisLabel) }} pada tahun ajaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.batch-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (event) {
        var batchId = form.dataset.batchId;
        var confirmation = window.prompt('Ketik HAPUS BATCH ' + batchId + ' untuk menghapus data milik batch ini.');
        if (confirmation !== 'HAPUS BATCH ' + batchId) {
            event.preventDefault();
            return;
        }
        form.querySelector('[name="konfirmasi"]').value = confirmation;
    });
});
</script>
@endpush
