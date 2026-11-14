@include('admin.import._form')

<div class="card" style="margin-top:14px">
    <h3>Data hasil impor {{ $taAktif ?: 'Tahun ajaran belum diatur' }}</h3>
    <div class="tw">
        <table>
            <thead>
                <tr>
                    <th>Kelas</th>
                    <th>Tahun Ajaran</th>
                    <th>Jumlah Siswa</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($riwayatImport as $row)
                    <tr>
                        <td><strong>{{ $row->kelas }}</strong></td>
                        <td>{{ $taAktif ?: 'Belum diatur' }}</td>
                        <td><b>{{ $row->baris }}</b> baris</td>
                        <td>
                            <a href="{{ route('admin.data-siswa', ['kelas' => $row->kelas]) }}" class="btn o" style="padding:3px 8px;font-size:12px">Lihat di Data Siswa</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" style="text-align:center;padding:24px;color:var(--mu)">
                            Belum ada data siswa terdaftar pada tahun ajaran ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
