<section class="card">
    <h3>Riwayat perubahan</h3>

    @if($riwayatKenaikanTerakhir)
        <div class="note bar" style="margin:0 0 12px">
            <div>
                <b>Riwayat kenaikan kelas terakhir:</b>
                Kelas <strong>{{ $riwayatKenaikanTerakhir->kelasAsal?->nama ?? 'Kelas' }}</strong>
                ({{ $riwayatKenaikanTerakhir->tahunAjaranAsal?->nama ?? $taAktif }}
                &rarr; {{ $riwayatKenaikanTerakhir->tahunAjaranTujuan?->nama ?? $taBerikutnya }}) &middot;
                {{ $riwayatKenaikanTerakhir->jumlah_siswa }} siswa diproses pada {{ $riwayatKenaikanTerakhir->created_at }}.
            </div>
            <span class="sp"></span>
            <form action="{{ route($routePrefix . '.kenaikan-kelas.batalkan', $riwayatKenaikanTerakhir->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan kenaikan kelas ini? Data riwayat siswa akan dikembalikan ke kondisi sebelum diproses.')">
                @csrf
                <button type="submit" class="btn x">Batalkan kenaikan terakhir</button>
            </form>
        </div>
    @endif

    @if($isAdmin)
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Kelas</th>
                        <th>Jenis</th>
                        <th>Tujuan / asal</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayatMutasi as $m)
                    @php
                        $rk = $m->riwayatKelas->first();
                        $kName = $rk?->kelas?->nama ?? ($m->kelas_keluar ?? '-');
                        $isMasuk = ($rk && $rk->status === 'Aktif') && ($m->jenis_registrasi === 'Pindahan');
                        $labelJenis = $isMasuk ? 'Pindahan masuk' : ($rk ? $rk->status : 'Pindah keluar');
                        $tujuanAsal = $isMasuk ? ($m->pindahan_dari_sekolah ?? $m->sekolah_asal ?? '-') : ($m->sekolah_tujuan ?? '-');
                    @endphp
                    <tr>
                        <td><b>{{ $m->nama_lengkap }}</b></td>
                        <td>{{ $kName }}</td>
                        <td><span class="bd {{ $isMasuk ? 'ok' : 'w' }}">{{ $labelJenis }}</span></td>
                        <td>{{ $tujuanAsal }}</td>
                        <td>
                            <a href="{{ route('admin.data-siswa.pdf', $m->id) }}" class="btn o" style="padding:3px 8px;font-size:12px">⬇ PDF</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:24px;color:var(--mu)">
                            Belum ada riwayat siswa pindah atau keluar pada tahun ajaran {{ $taAktif }}{{ $kelasAsal === '*' ? '' : ' di kelas '.$kelasAsal }}.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif(! $riwayatKenaikanTerakhir)
        <p class="sub">Belum ada riwayat perubahan kenaikan kelas pada tahun ajaran {{ $taAktif }}.</p>
    @endif
</section>