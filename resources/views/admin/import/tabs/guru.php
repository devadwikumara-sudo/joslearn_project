@include('admin.import._form')

<div class="card" style="margin-top:14px">
    <h3>Guru terdaftar di sistem</h3>
    <p class="sub">Total <strong>{{ $totalGuru }}</strong> guru terdaftar pada tahun ajaran <strong>{{ $taAktif ?: '-' }}</strong>.</p>
    <div class="bar" style="margin-top:10px">
        <a href="{{ route('admin.guru-master', ['tab' => 'guru']) }}" class="btn o">Lihat di Data Guru</a>
    </div>
</div>
