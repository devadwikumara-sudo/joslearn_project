<section class="card" style="margin-top:16px">
    <div class="bar" style="margin-bottom:12px">
        <div style="display:flex;align-items:center;gap:8px">
            <svg width="18" height="18" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3 style="margin:0;font-size:16px;color:var(--admin-text)">Riwayat Perubahan &amp; Mutasi</h3>
        </div>
    </div>

    @if($riwayatKenaikanTerakhir)
        <div class="note bar" style="margin:0 0 16px;background:#fef2f2;border:1.5px solid #fecaca;border-radius:12px;padding:12px 16px;flex-wrap:wrap;gap:10px">
            <div style="color:#991b1b;font-size:13px">
                <b>📌 Sesi Kenaikan Kelas Terakhir:</b>
                Kelas <strong>{{ $riwayatKenaikanTerakhir->kelasAsal?->nama ?? 'Kelas' }}</strong>
                ({{ $riwayatKenaikanTerakhir->tahunAjaranAsal?->nama ?? $taAktif }}
                &rarr; {{ $riwayatKenaikanTerakhir->tahunAjaranTujuan?->nama ?? $taBerikutnya }}) &middot;
                <b>{{ $riwayatKenaikanTerakhir->jumlah_siswa }}</b> siswa diproses pada {{ $riwayatKenaikanTerakhir->created_at }}.
            </div>
            <span class="sp"></span>
            <form action="{{ route($routePrefix . '.kenaikan-kelas.batalkan', $riwayatKenaikanTerakhir->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin membatalkan kenaikan kelas ini? Data riwayat siswa akan dikembalikan ke kondisi sebelum diproses.')">
                @csrf
                <button type="submit" class="btn x" style="font-size:12px;padding:5px 12px">↩ Batalkan Kenaikan Terakhir</button>
            </form>
        </div>
    @endif

    @if($isAdmin)
        <div class="tw">
            <table>
                <thead>
                    <tr>
                        <th>Nama Siswa</th>
                        <th style="width:120px">Kelas</th>
                        <th style="width:150px">Jenis Mutasi</th>
                        <th>Tujuan / Asal Sekolah</th>
                        <th style="width:90px;text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($riwayatMutasi as $m)
                    @php
                        $rk = $m->riwayatKelas->first();
                        $kName = $rk?->kelas?->nama ?? ($m->kelas_keluar ?? '-');
                        $isMasuk = ($rk && $rk->status === 'Aktif') && ($m->jenis_registrasi === 'Pindahan');
                        $labelJenis = $isMasuk ? 'Pindahan Masuk' : ($rk ? $rk->status : 'Pindah Keluar');
                        $tujuanAsal = $isMasuk ? ($m->pindahan_dari_sekolah ?? $m->sekolah_asal ?? '-') : ($m->sekolah_tujuan ?? '-');
                    @endphp
                    <tr>
                        <td>
                            <b style="color:var(--admin-text);font-size:13.5px">{{ $m->nama_lengkap }}</b>
                            @if($m->nisn)
                                <br><small style="color:var(--mu);font-size:11px">NISN: {{ $m->nisn }}</small>
                            @endif
                        </td>
                        <td><span class="bd">{{ $kName }}</span></td>
                        <td><span class="bd {{ $isMasuk ? 'ok' : 'w' }}">{{ $labelJenis }}</span></td>
                        <td style="font-size:13px">{{ $tujuanAsal }}</td>
                        <td style="text-align:right">
                            <a href="{{ route('admin.data-siswa.pdf', $m->id) }}" target="_blank" class="btn o" style="padding:4px 8px;font-size:11.5px">⬇ PDF</a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="text-align:center;padding:32px;color:var(--mu)">
                            <div style="font-size:28px;margin-bottom:6px">📋</div>
                            <div style="font-size:13.5px;font-weight:600">Belum ada riwayat siswa pindah atau keluar pada tahun ajaran {{ $taAktif }}{{ $kelasAsal === '*' ? '' : ' di kelas '.$kelasAsal }}.</div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @elseif(! $riwayatKenaikanTerakhir)
        <p class="sub" style="margin:8px 0 0">Belum ada riwayat perubahan kenaikan kelas pada tahun ajaran {{ $taAktif }}.</p>
    @endif
</section>
