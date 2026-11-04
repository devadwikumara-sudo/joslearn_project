@extends('layouts.app')

@section('title', 'Pelacakan Alumni — JosBook Admin')

@section('content')
<h2>Pelacakan Alumni</h2>
<p class="sub">Catat kelanjutan setelah lulus. Boleh lebih dari satu riwayat; yang terbaru mengisi kolom “Melanjutkan studi / bekerja” di Kontrol Induk.</p>

@if(session('success'))
<div class="note ok">{{ session('success') }}</div>
@endif

<div class="g g4">
    <div class="card st">
        <span>Alumni</span>
        <b>{{ $tot }}</b>
    </div>
    <div class="card st">
        <span>Sudah dilacak</span>
        <b>{{ $tr }} ({{ $tot ? round(($tr * 100) / $tot) : 0 }}%)</b>
    </div>
    <div class="card st">
        <span>Kuliah</span>
        <b>{{ $cntKuliah }}</b>
    </div>
    <div class="card st">
        <span>Bekerja</span>
        <b>{{ $cntBekerja }}</b>
    </div>
</div>

<form method="GET" action="{{ route('admin.pelacakan-alumni') }}">
    <div class="bar" style="margin-top:12px">
        <select name="lcy" onchange="this.form.submit()">
            <option value="">Semua tahun lulus</option>
            @foreach($ys as $val)
            <option value="{{ $val }}" {{ (string)$y === (string)$val ? 'selected' : '' }}>{{ $val }}</option>
            @endforeach
        </select>
    </div>
</form>

<div class="card" style="margin-top:12px">
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Alumni</th>
                    <th>Lulus</th>
                    <th>Terakhir diketahui</th>
                    <th>Riwayat</th>
                    <th style="width:100px;text-align:right"></th>
                </tr>
            </thead>
            <tbody>
                @forelse($paginatedAlumni as $s)
                @php
                    $last = $s->pelacakan->last();
                    $thnLulus = $s->tahun_lulus ?: ($s->tanggal_keluar ? substr($s->tanggal_keluar, 0, 4) : '-');
                    $historyJson = htmlspecialchars(json_encode($s->pelacakan->map(fn($p) => [
                        'id' => $p->id,
                        'jenis' => $p->jenis,
                        'instansi' => $p->nama_tempat,
                        'tahun' => $p->tahun,
                        'keterangan' => $p->keterangan
                    ])), ENT_QUOTES, 'UTF-8');
                @endphp
                <tr>
                    <td>
                        <b>{{ $s->nama_lengkap }}</b><br>
                        <small style="color:var(--mu)">{{ $s->nisn }}</small>
                    </td>
                    <td>{{ $thnLulus }}</td>
                    <td>
                        @if($last)
                            <span class="bd ok">{{ $last->jenis }}</span>
                            {{ $last->nama_tempat }} <small style="color:var(--mu)">({{ $last->tahun }})</small>
                        @else
                            <span class="bd w">Belum dilacak</span>
                        @endif
                    </td>
                    <td>{{ $s->pelacakan->count() }}</td>
                    <td style="text-align:right">
                        <button type="button" class="btn"
                            onclick="openLacakModal('{{ $s->id }}', '{{ addslashes($s->nama_lengkap) }}', '{{ $thnLulus }}', {{ $historyJson }})">
                            + Catat
                        </button>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" style="text-align:center;padding:24px;color:var(--mu)">
                        Belum ada data alumni untuk tahun ini.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($paginatedAlumni->hasPages())
    <div style="margin-top:14px;display:flex;justify-content:center">
        {{ $paginatedAlumni->links() }}
    </div>
    @endif
</div>

{{-- Modal Catat Pelacakan Alumni --}}
<div id="modal-lacak" style="display:none;position:fixed;inset:0;background:rgba(11,34,64,0.6);z-index:9999;place-items:center;padding:16px">
    <div class="mbox" style="max-width:520px;width:100%;max-height:90vh;overflow-y:auto">
        <h3 id="ml-nama" style="margin:0 0 4px">Nama Alumni</h3>
        <p class="sub" id="ml-lulus" style="margin:0 0 14px">Lulus 2024</p>

        <div style="font-weight:600;font-size:12px;margin-bottom:6px">Riwayat Tercatat:</div>
        <div id="ml-history" style="display:flex;flex-direction:column;gap:6px;margin-bottom:14px"></div>

        <div style="border-top:1px solid var(--ln);padding-top:12px">
            <h4 style="margin:0 0 10px;font-size:13px;color:var(--p)">+ Tambah Riwayat Baru</h4>
            <form method="POST" action="{{ route('admin.pelacakan-alumni.store') }}">
                @csrf
                <input type="hidden" name="siswa_id" id="ml-siswa-id">

                <div class="fl" style="grid-template-columns:1fr 1fr;gap:10px">
                    <div>
                        <label>Kegiatan</label>
                        <select name="jenis" required style="width:100%">
                            <option value="Kuliah">Kuliah</option>
                            <option value="Bekerja">Bekerja</option>
                            <option value="Wirausaha">Wirausaha</option>
                            <option value="Mencari kerja">Mencari kerja</option>
                            <option value="Lainnya">Lainnya</option>
                        </select>
                    </div>
                    <div>
                        <label>Tahun</label>
                        <input type="number" name="tahun" value="{{ date('Y') }}" required style="width:100%">
                    </div>
                </div>

                <div style="margin-top:10px">
                    <label>Kampus / instansi / perusahaan</label>
                    <input type="text" name="nama_tempat" placeholder="mis. Universitas Brawijaya / PT Telkom" required style="width:100%">
                </div>

                <div style="margin-top:10px">
                    <label>Keterangan Tambahan (opsional)</label>
                    <input type="text" name="keterangan" placeholder="Jurusan / Jabatan / Lokasi" style="width:100%">
                </div>

                <div class="bar" style="margin:16px 0 0;justify-content:flex-end">
                    <button type="button" class="btn" onclick="closeLacakModal()">Tutup</button>
                    <button type="submit" class="btn a">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Hidden Form for Deleting a History Record --}}
<form id="form-delete-lacak" method="POST" action="" style="display:none">
    @csrf
    @method('DELETE')
</form>

<script>
function openLacakModal(id, nama, thnLulus, history) {
    document.getElementById('ml-siswa-id').value = id;
    document.getElementById('ml-nama').innerText = nama;
    document.getElementById('ml-lulus').innerText = 'Lulus ' + thnLulus;

    const histBox = document.getElementById('ml-history');
    histBox.innerHTML = '';

    if (!history || history.length === 0) {
        histBox.innerHTML = '<p class="sub" style="margin:0;font-style:italic">Belum ada riwayat tercatat.</p>';
    } else {
        history.forEach(function(h) {
            const row = document.createElement('div');
            row.className = 'bar';
            row.style.background = '#f8fafc';
            row.style.padding = '6px 10px';
            row.style.borderRadius = '6px';
            row.style.border = '1px solid var(--ln)';
            row.innerHTML = `
                <span style="flex:1;font-size:12px">
                    <b>${h.jenis} · ${h.instansi}</b> <small style="color:var(--mu)">(${h.tahun})</small>
                </span>
                <button type="button" class="btn x" style="padding:2px 8px;font-size:11px" onclick="deleteLacakRecord('${h.id}')">🗑</button>
            `;
            histBox.appendChild(row);
        });
    }

    const modal = document.getElementById('modal-lacak');
    modal.style.display = 'grid';
}

function closeLacakModal() {
    document.getElementById('modal-lacak').style.display = 'none';
}

function deleteLacakRecord(id) {
    if (confirm('Hapus riwayat pelacakan ini?')) {
        const form = document.getElementById('form-delete-lacak');
        form.action = '/admin/pelacakan-alumni/' + id;
        form.submit();
    }
}
</script>
@endsection
