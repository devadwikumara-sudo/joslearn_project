<div class="card" style="margin-top:14px">
    <h3>{{ $type === 'nilai' ? 'Riwayat Import Nilai Saya' : ($type === 'ekskul' ? 'Riwayat Import Ekskul' : 'Riwayat Import') }}</h3>
    <p style="color:var(--mu);font-size:12px;text-align:center;padding:20px">
        {{ $type === 'nilai' ? 'Belum ada riwayat import.' : 'Belum ada riwayat.' }}
    </p>
</div>
