<section id="riwayat" class="card" style="margin-top:16px">
    <div class="bar" style="margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Riwayat Kenaikan Kelas</h3>
        </div>
    </div>

    @if($riwayatKenaikanTerakhir)
        <div class="note bar" style="margin:0 0 16px;background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:12px 16px;flex-wrap:wrap;gap:10px">
            <div style="color:#991b1b;font-size:13px">
                <b>📌 Riwayat Kenaikan Kelas Terakhir:</b>
                Kelas <strong>{{ $riwayatKenaikanTerakhir->kelasAsal?->nama ?? 'Kelas' }}</strong>
                ({{ $riwayatKenaikanTerakhir->tahunAjaranAsal?->nama ?? $taAktif }}
                &rarr; {{ $riwayatKenaikanTerakhir->tahunAjaranTujuan?->nama ?? $taBerikutnya }}) &middot;
                <b>{{ $riwayatKenaikanTerakhir->jumlah_siswa }}</b> siswa tercatat pada {{ $riwayatKenaikanTerakhir->created_at }}.
            </div>
            <span class="sp"></span>
            @if($riwayatKenaikanTerakhirBolehDibatalkan)
                <form action="{{ route($routePrefix . '.kenaikan-kelas.batalkan', $riwayatKenaikanTerakhir->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan kenaikan kelas ini? Data riwayat siswa akan dikembalikan ke kondisi sebelum diproses.')">
                    @csrf
                    <button type="submit" class="btn x" style="font-size:12px;padding:5px 12px">↩ Batalkan Kenaikan Terakhir</button>
                </form>
            @endif
        </div>
    @endif

    @if($riwayatKenaikanSiswa->isNotEmpty())
        <h4 style="margin:0 0 10px;color:var(--admin-text)">Rincian Riwayat Kenaikan Kelas</h4>
        <div class="tw" style="margin-bottom:16px">
            <table>
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th style="width:120px">Kelas Asal</th>
                        <th style="width:140px">Keputusan</th>
                        <th style="width:140px">Kelas Tujuan</th>
                        <th style="width:130px">Tahun Tujuan</th>
                        <th style="width:150px">Tanggal</th>
                        <th style="width:90px;text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($riwayatKenaikanSiswa as $riwayat)
                        <tr>
                            <td>
                                <b style="color:var(--admin-text)">{{ $riwayat['nama_siswa'] }}</b>
                                @if($riwayat['nisn'])
                                    <br><small style="color:var(--mu)">NISN: {{ $riwayat['nisn'] }}</small>
                                @endif
                            </td>
                            <td><span class="bd">{{ $riwayat['kelas_asal'] }}</span></td>
                            <td><span class="bd {{ $riwayat['keputusan'] === 'Naik' ? 'ok' : 'w' }}">{{ $riwayat['keputusan'] }}</span></td>
                            <td>
                                @if($riwayat['kelas_tujuan'] !== null && $riwayat['tahun_tujuan_id'] !== null)
                                    <a href="{{ route($routePrefix . '.data-siswa', [
                                        'tahun_ajaran' => $riwayat['tahun_tujuan_id'],
                                        'kelas' => $riwayat['kelas_tujuan'],
                                    ]) }}">{{ $riwayat['kelas_tujuan'] }}</a>
                                @else
                                    {{ $riwayat['kelas_tujuan'] ?? '—' }}
                                @endif
                            </td>
                            <td>{{ $riwayat['tahun_tujuan'] ?? '—' }}</td>
                            <td>{{ $riwayat['tanggal'] }}</td>
                            <td style="text-align:right">
                                @if($riwayat['boleh_hapus'])
                                    <form action="{{ route($routePrefix . '.kenaikan-kelas.siswa.batalkan', [$riwayat['batch_id'], $riwayat['siswa_id']]) }}" method="POST" style="display:inline" onsubmit="return confirm('Hapus riwayat siswa ini dan batalkan kenaikannya? Siswa akan dikembalikan ke kelas asal.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn x" style="padding:4px 8px;font-size:11.5px">Hapus</button>
                                    </form>
                                @else
                                    <span class="sub">—</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="sub" style="margin:8px 0 0">Belum ada riwayat kenaikan kelas pada tahun ajaran {{ $taAktif }}{{ $kelasAsal === '*' ? '' : ' untuk kelas '.$kelasAsal }}.</p>
    @endif
</section>
