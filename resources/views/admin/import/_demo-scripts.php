@if($type === 'nilai')
    @push('scripts')
    <script>
    function loadDemoNilai() {
        var dummy = [
            { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: '2', mtk: 85, ind: 88, big: 82, pnc: 90, pjk: 80 },
            { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: '2', mtk: 90, ind: 92, big: 85, pnc: 88, pjk: 84 },
            { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: '2', mtk: 78, ind: 85, big: 80, pnc: 82, pjk: 86 }
        ];
        var ws = XLSX.utils.json_to_sheet(dummy);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Nilai');
        var blob = new Blob([XLSX.write(wb, { bookType: 'xlsx', type: 'array' })], { type: 'application/octet-stream' });
        smartFile({ target: { files: [new File([blob], 'demo_nilai.xlsx')] } }, 'nilai');
    }
    </script>
    @endpush
@elseif($type === 'kehadiran')
    @push('scripts')
    <script>
    function loadDemoHadir() {
        var dummy = [
            { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: '2', sakit: 1, izin: 0, alpa: 0, kokur: 'Menunjukkan komitmen tinggi dalam proyek penguatan profil pelajar pancasila.' },
            { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: '2', sakit: 0, izin: 2, alpa: 0, kokur: 'Mampu berkolaborasi aktif dan menyelesaikan tugas kelompok dengan tuntas.' },
            { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: '2', sakit: 2, izin: 1, alpa: 0, kokur: 'Menunjukkan inisiatif kepemimpinan dalam kegiatan kokurikuler.' }
        ];
        var ws = XLSX.utils.json_to_sheet(dummy);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Kehadiran');
        var blob = new Blob([XLSX.write(wb, { bookType: 'xlsx', type: 'array' })], { type: 'application/octet-stream' });
        smartFile({ target: { files: [new File([blob], 'demo_kehadiran.xlsx')] } }, 'hadir');
    }
    </script>
    @endpush
@elseif($type === 'ekskul')
    @push('scripts')
    <script>
    function loadDemoEkskul() {
        var dummy = [
            { nisn: '0081234561', nama: 'Ahmad Faiz', kelas: 'X-1', sem: '2', ekskul: 'Pramuka Wajib', predikat: 'Sangat Baik' },
            { nisn: '0081234562', nama: 'Bunga Citra', kelas: 'X-1', sem: '2', ekskul: 'PMR', predikat: 'Baik' },
            { nisn: '0081234563', nama: 'Dimas Anggara', kelas: 'X-2', sem: '2', ekskul: 'Futsal', predikat: 'Sangat Baik' }
        ];
        var ws = XLSX.utils.json_to_sheet(dummy);
        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Ekskul');
        var blob = new Blob([XLSX.write(wb, { bookType: 'xlsx', type: 'array' })], { type: 'application/octet-stream' });
        smartFile({ target: { files: [new File([blob], 'demo_ekskul.xlsx')] } }, 'ekskul');
    }
    </script>
    @endpush
@endif
