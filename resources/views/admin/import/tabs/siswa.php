@include('admin.import._form')

<div class="card" style="margin-top:14px">
    <h3>Data hasil impor {{ $taAktif ?: 'Tahun ajaran belum diatur' }}</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Tahun Ajaran</th>
                    <th>Status</th>
                    <th>Jumlah Siswa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport as $row)
                    <tr>
                        <td><strong>{{ $row->kelas }}</strong></td>
                        <td>{{ $taAktif ?: 'Belum diatur' }}</td>
                        <td>{{ $row->status ?: 'Aktif' }}</td>
                        <td><b>{{ $row->baris }}</b> baris</td>
                        <td>
                            @if($row->status === 'Lulus')
                                <a href="{{ route('admin.alumni', ['aj' => 'Alumni (Lulus)', 'kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Alumni</a>
                            @elseif(in_array($row->status, ['Pindah keluar', 'Keluar'], true))
                                <a href="{{ route('admin.alumni', ['aj' => 'Pindah / Keluar', 'kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Alumni</a>
                            @else
                                <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:var(--mu)">
                            Belum ada data siswa terdaftar pada tahun ajaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
