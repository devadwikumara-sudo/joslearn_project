/**
 * JosBook Flexible Excel Import Engine v3
 * 100% Mengikuti Spesifikasi & Prototipe JosBook
 * Fitur:
 * - Pembaca file fleksibel (.xlsx, .xls, .csv via SheetJS)
 * - Pemetaan biodata dari sheet PesertaDidik (DATABASE_SISWA.xlsx)
 * - Deteksi otomatis baris judul (header), kelas, semester, dan jenis data
 * - Tidak pernah beralih jenis data sembarangan (tipe terkunci per halaman)
 * - Panel interaktif "Lihat / ubah pemetaan kolom" (cmap)
 * - Validasi ketat per baris (Error vs Peringatan) & unduh laporan CSV
 * - Pratinjau interaktif per kelas
 * - Simpan batch ke server (/admin/import/save-batch) & output langsung ke Data Siswa
 */
(function(window) {
    'use strict';

    // ─── Indeks Kolom Excel (0-based) ───────────────────────────────────────────
    const CI = a => [...a].reduce((n, c) => n * 26 + c.charCodeAt(0) - 64, 0) - 1;

    // ─── Pemetaan Kolom DATABASE_SISWA.xlsx (PesertaDidik) ──────────────────────
    const RMAP = {
        C:'nis', D:'nama', E:'panggilan', F:'nisn', G:'jk', H:'tmp', I:'tgl', J:'agama',
        K:'kewarganegaraan', L:'nik', M:'anak_ke', N:'jml_saudara', O:'yatim', P:'bahasa',
        Q:'alamat', R:'rt_rw', S:'kelurahan', T:'kecamatan', U:'kab', V:'prov', W:'kodepos',
        X:'hp', Y:'tinggal', Z:'jarak', AA:'gol_darah', AB:'penyakit', AC:'kelainan',
        AD:'tinggi', AE:'berat', AF:'asal', AG:'tgl_ijazah_smp', AH:'no_ijazah_smp',
        AI:'lama', AJ:'masuk', AK:'sekolah_pindah', AL:'alasan_pindah', AM:'kelas_masuk',
        AN:'rombel_masuk', AO:'tgl_masuk', AP:'ayah', AQ:'agama_ayah', AR:'wn_ayah',
        AS:'pend_ayah', AT:'kerja_ayah', AU:'gaji_ayah', AV:'hp_ayah', AW:'ket_ayah',
        AX:'ibu', AY:'agama_ibu', AZ:'wn_ibu', BA:'pend_ibu', BB:'kerja_ibu', BC:'gaji_ibu',
        BD:'hp_ibu', BE:'ket_ibu', BF:'al_jalan', BG:'al_rt', BH:'al_desa', BI:'al_kec',
        BJ:'al_kab', BK:'al_prov', BL:'al_pos', BM:'wali', BN:'agama_wali', BO:'wn_wali',
        BP:'pend_wali', BQ:'kerja_wali', BR:'gaji_wali', BS:'hp_wali',
        BU:'wali_jalan', BV:'wali_rt', BW:'wali_desa', BX:'wali_kec', BY:'wali_kab',
        BZ:'wali_prov', CA:'wali_pos', CD:'nopes'
    };

    // ─── Pemetaan Kolom Kontrol Induk (DATABASE_SISWA.xlsx) ────────────────────
    const KMAP = {
        I: 'alasan_keluar',
        J: 'tgl_keluar',
        K: 'kelas_keluar',
        L: 'tujuan',
        M: 'nopes',
        N: 'no_skl',
        O: 'no_ijazah_sma',
        P: 'tgl_ijazah_sma',
        Q: 'no_transkrip'
    };

    const BLN = ['Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

    const iso = value => {
        if (typeof value !== 'number' || value <= 0 || value >= 80000) return value;
        const serial = value < 60 ? value + 1 : value;
        const date = new Date(Date.UTC(1899, 11, 30) + Math.round(serial * 864e5));
        return Number.isNaN(date.getTime()) ? value : date.toISOString().slice(0, 10);
    };

    const cl = value => {
        if (value == null) return null;
        if (typeof value !== 'string') return value;
        const text = value.trim().replace(/\s+/g, ' ');
        return !text || ['-', 'x', 'n/a'].includes(text.toLowerCase()) ? null : text;
    };
    const RM = {'10':'X', '11':'XI', '12':'XII'};

    const normK = v => {
        v = String(v ?? '').trim().toUpperCase().replace(/^KELAS\s*/, '');
        if (!v || /^-+$/.test(v)) return '';
        const m = /^(XII|XI|X|12|11|10)(?![A-Z])[\s\-._\/]*(.*)$/.exec(v);
        if (!m) return v;
        const t = RM[m[1]] || m[1];
        const r = m[2].replace(/\s+/g,' ').replace(/[\s\-._]+$/, '').trim();
        return r ? t + '-' + r : t + '-1';
    };

    const normAcademicYear = v => {
        const match = /(?:^|\D)(20\d{2})\s*[/-]\s*(\d{2}|20\d{2})(?:\D|$)/.exec(String(v ?? '').trim());
        if (!match) return '';
        const start = Number(match[1]);
        const end = match[2].length === 2 ? Number('20' + match[2]) : Number(match[2]);
        return end === start + 1 ? `${start}/${end}` : '';
    };

    const yearBeforeHeader = (rows, headerIndex, sheetName = '') => {
        const metadata = [sheetName, ...(rows || []).slice(0, headerIndex).map(row => (row || []).join(' '))].join(' ');
        const labeledMatch = /(?:tahun\s*(?:ajaran|pelajaran)|academic\s*year|ta)\s*[:=-]?\s*((?:19|20)\d{2}\s*[/-]\s*(?:\d{2}|(?:19|20)\d{2}))/i.exec(metadata);
        const match = labeledMatch || /((?:19|20)\d{2}\s*[/-]\s*(?:\d{2}|(?:19|20)\d{2}))/.exec(metadata);
        return match ? normAcademicYear(match[1]) : '';
    };

    const normSem = v => {
        const value = String(v ?? '').trim();
        if (/genap/i.test(value)) return 'Genap';
        if (/ganjil/i.test(value)) return 'Ganjil';
        const match = /^(?:semester|smt|sem)?\s*\.?\s*([1-6])$/i.exec(value);
        return match ? match[1] : '';
    };

    const normN = v => {
        let value = numStr(v).trim();
        if (/^[+-]?\d+(?:\.\d+)?e[+-]?\d+$/i.test(value)) {
            const number = Number(value);
            value = Number.isFinite(number) ? number.toLocaleString('fullwide', {useGrouping:false, maximumFractionDigits:0}) : value;
        }
        value = value.replace(/\.0+$/, '').replace(/\D/g, '');
        if (value.length >= 6 && value.length <= 9) return value.padStart(10, '0');
        return value;
    };

    const numStr = value => {
        if (typeof value === 'number') {
            return value.toLocaleString('fullwide', {useGrouping:false, maximumFractionDigits:20});
        }
        const text = String(value ?? '').trim();
        if (/^[+-]?\d+(?:\.\d+)?e[+-]?\d+$/i.test(text)) {
            const number = Number(text);
            return Number.isFinite(number)
                ? number.toLocaleString('fullwide', {useGrouping:false, maximumFractionDigits:0})
                : text;
        }
        return text;
    };

    const toIso = v => {
        if (typeof v === 'number' && v > 0 && v < 80000) return iso(v);
        const t = String(v ?? '').trim().replace(/\s+/g, ' ');
        const valid = (year, month, day) => {
            const date = new Date(Date.UTC(year, month - 1, day));
            return date.getUTCFullYear() === year && date.getUTCMonth() === month - 1 && date.getUTCDate() === day;
        };
        let m = /^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/.exec(t);
        if (m && valid(Number(m[3]), Number(m[2]), Number(m[1]))) {
            return m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0');
        }
        m = /^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/.exec(t);
        const monthNames = [...BLN, 'Mei'];
        const bi = m ? monthNames.findIndex(b => b.toLowerCase() === m[2].toLowerCase()) : -1;
        if (bi >= 0 && valid(Number(m[3]), (bi % 12) + 1, Number(m[1]))) {
            return m[3] + '-' + String((bi % 12) + 1).padStart(2, '0') + '-' + m[1].padStart(2, '0');
        }
        return t;
    };

    const nz = s => String(s ?? '').toLowerCase().replace(/\([^)]*\)/g, ' ').replace(/[^a-z0-9]+/g, ' ').trim();

    function readRows(sheet) {
        const rows = XLSX.utils.sheet_to_json(sheet, {header: 1, defval: ''});
        const merges = sheet?.['!merges'] || [];
        merges.forEach(range => {
            const {s, e} = range;
            if (!s || !e || s.r >= 30) return;
            const title = rows[s.r]?.[s.c];
            if (title == null || String(title).trim() === '') return;
            for (let rowIndex = s.r + 1; rowIndex <= Math.min(e.r, 29); rowIndex++) {
                rows[rowIndex] ||= [];
                if (rows[rowIndex][s.c] == null || String(rows[rowIndex][s.c]).trim() === '') {
                    rows[rowIndex][s.c] = title;
                }
            }
            if (e.c > s.c && rows[s.r + 1]) {
                for (let columnIndex = s.c; columnIndex <= e.c; columnIndex++) {
                    const child = rows[s.r + 1][columnIndex];
                    if (child != null && String(child).trim() !== '' && nz(child) !== nz(title)) {
                        rows[s.r + 1][columnIndex] = `${String(title).trim()} | ${String(child).trim()}`;
                    }
                }
            }
        });

        return rows;
    }

    // ─── Kamus Alias Header (Mendukung Format Apapun) ─────────────────────────
    const ALX = {
        nisn: 'nisn|nomor induk siswa nasional',
        nis: 'nis|nomor induk|no induk|nomor induk siswa|no induk siswa|nipd',
        nama: 'nama|nama lengkap|nama siswa|nama peserta didik|nama murid|peserta didik|siswa',
        panggilan: 'nama panggilan|panggilan',
        jk: 'jk|l p|jenis kelamin|gender|kelamin',
        tmp: 'tempat lahir|tmp lahir|kota lahir',
        tgl: 'tanggal lahir|tgl lahir|tgl lhr',
        kelas: 'kelas|kelas saat ini|kelas sekarang|rombel|rombongan belajar|nama rombel|kls',
        tahun_ajaran: 'tahun ajaran|tahunajaran|tahun pelajaran|academic year|ta',
        alasan_keluar: 'alasan keluar|alasan meninggalkan sekolah|keluar karena',
        tgl_keluar: 'tgl keluar|tanggal keluar|tanggal lulus|tgl lulus',
        kelas_keluar: 'kelas keluar|kelas terakhir',
        tujuan: 'tujuan|sekolah tujuan',
        nopes: 'nomor peserta psaj|nomor peserta|no peserta|nopes',
        no_skl: 'nomor skl|no skl',
        no_ijazah_sma: 'nomor ijazah sma|nomor ijazah nasional|no ijazah sma',
        tgl_ijazah_sma: 'tanggal ijazah sma|tgl ijazah sma|tanggal pengesahan ijazah',
        no_transkrip: 'nomor transkrip|no transkrip',
        tahun_lulus: 'tahun lulus|thn lulus|tahun kelulusan',
        lanjut: 'melanjutkan studi|melanjutkan studi bekerja|kegiatan setelah lulus|lanjut',
        agama: 'agama',
        kewarganegaraan: 'kewarganegaraan|warga negara|wn',
        nik: 'nik|no nik|nomor induk kependudukan',
        lama: 'lama belajar|lama belajar tahun',
        masuk: 'jenis registrasi|jenis masuk|registrasi',
        sekolah_pindah: 'sekolah pindahan|pindahan dari sekolah|sekolah asal pindahan',
        alasan_pindah: 'alasan pindah|alasan mutasi',
        kelas_masuk: 'kelas diterima|kelas masuk',
        rombel_masuk: 'rombel diterima|rombel masuk',
        tgl_masuk: 'tanggal diterima|tanggal masuk|tgl masuk',
        anak_ke: 'anak ke|anak',
        jml_saudara: 'jml saudara|jumlah saudara|saudara',
        yatim: 'yatim|status yatim|keadaan jasmani',
        bahasa: 'bahasa|bahasa sehari hari',
        alamat: 'alamat|jalan|dusun|alamat jalan|jalan dusun|tempat tinggal',
        rt_rw: 'rt rw|rt|rtrw|rt rw jalan',
        kelurahan: 'desa|kelurahan|desa kelurahan',
        kecamatan: 'kecamatan|kec',
        kab: 'kabupaten|kota|kabupaten kota|kab',
        prov: 'provinsi|prov',
        kodepos: 'kode pos|kodepos',
        hp: 'hp|no hp|nomor hp|telepon|no telepon|nomor telepon|no telp|telp',
        tinggal: 'tinggal dengan|jenis tinggal',
        jarak: 'jarak|jarak ke sekolah',
        gol_darah: 'gol darah|golongan darah|darah',
        penyakit: 'penyakit|riwayat penyakit',
        kelainan: 'kelainan|kelainan fisik',
        tinggi: 'tinggi|tinggi badan|tb',
        berat: 'berat|berat badan|bb',
        asal: 'asal|sekolah asal|asal sekolah|smp asal',
        tgl_ijazah_smp: 'tgl ijazah smp|tanggal ijazah smp',
        no_ijazah_smp: 'no ijazah smp|nomor ijazah smp',
        ayah: 'nama ayah|ayah|nama ayah kandung|nama bapak',
        agama_ayah: 'agama ayah',
        wn_ayah: 'kewarganegaraan ayah|wn ayah',
        pend_ayah: 'pendidikan ayah|pend ayah',
        kerja_ayah: 'pekerjaan ayah|kerja ayah',
        gaji_ayah: 'penghasilan ayah|gaji ayah',
        hp_ayah: 'hp ayah|no hp ayah',
        ket_ayah: 'keterangan ayah|keadaan ayah',
        ibu: 'nama ibu|ibu|nama ibu kandung',
        agama_ibu: 'agama ibu',
        wn_ibu: 'kewarganegaraan ibu|wn ibu',
        pend_ibu: 'pendidikan ibu|pend ibu',
        kerja_ibu: 'pekerjaan ibu|kerja ibu',
        gaji_ibu: 'penghasilan ibu|gaji ibu',
        hp_ibu: 'hp ibu|no hp ibu',
        ket_ibu: 'keterangan ibu|keadaan ibu',
        wali: 'nama wali|wali',
        agama_wali: 'agama wali',
        wn_wali: 'kewarganegaraan wali|wn wali',
        pend_wali: 'pendidikan wali',
        kerja_wali: 'pekerjaan wali',
        gaji_wali: 'penghasilan wali|gaji wali',
        hp_wali: 'hp wali|no hp wali',
        wali_jalan: 'alamat jalan wali|jalan wali|alamat wali',
        wali_rt: 'rt rw wali|rt wali',
        wali_desa: 'desa wali|kelurahan wali',
        wali_kec: 'kecamatan wali',
        wali_kab: 'kabupaten wali',
        wali_prov: 'provinsi wali',
        wali_pos: 'kode pos wali',
        al_jalan: 'alamat jalan orang tua|jalan orang tua|alamat orang tua',
        al_rt: 'rt rw orang tua|rt orang tua',
        al_desa: 'desa orang tua|kelurahan orang tua',
        al_kec: 'kecamatan orang tua',
        al_kab: 'kabupaten orang tua',
        al_prov: 'provinsi orang tua',
        al_pos: 'kode pos orang tua',
        catatan: 'catatan',
        pend: 'pendidikan',
        kerja: 'pekerjaan|kerja',
        gaji: 'penghasilan|gaji',
        ket: 'keterangan',
        sakit: 'sakit',
        izin: 'izin|ijin',
        alpa: 'alpa|alpha|alfa|tanpa keterangan',
        kokur: 'kokurikuler|deskripsi kokurikuler|catatan kokurikuler|deskripsi nilai kokurikuler',
        ekskul: 'ekskul|ekstrakurikuler|ekstra kurikuler|nama ekskul|kegiatan ekstrakurikuler',
        predikat: 'predikat|nilai ekskul',
        sem: 'semester|smt',
        mapelcol: 'mata pelajaran|mapel|nama mapel',
        nilaicol: 'nilai|nilai akhir|skor|nilai rapor',
        nip: 'nip|nomor induk pegawai',
        nuptk: 'nuptk',
        mulai_bertugas: 'mulai bertugas|tahun masuk|sejak tahun',
        status: 'status|status kepegawaian|status siswa|status peserta didik'
    };

    const MEX = {
        agm: 'pai|pabp|pendidikan agama|agama dan budi pekerti|pendidikan agama islam|agama islam',
        pnc: 'pkn|ppkn|pancasila|pendidikan kewarganegaraan',
        ind: 'b indonesia|b ind|bahasa indonesia',
        mtk: 'mat|matematika umum|matematika wajib|matematika',
        big: 'b inggris|b ing|bahasa inggris',
        pjk: 'pjok|pjkr|penjasorkes|pendidikan jasmani',
        sbd: 'seni budaya|seni dan budaya|sbk|seni budaya dan prakarya',
        inf: 'tik|informatika',
        jaw: 'b jawa|bahasa jawa|muatan lokal',
        mtl: 'matematika lanjut|matematika tingkat lanjut',
        pkw: 'prakarya|pkwu|kewirausahaan',
        sej: 'sejarah indonesia|sejarah',
        ipa: 'ilmu pengetahuan alam',
        ips: 'ilmu pengetahuan sosial'
    };

    const SUB = [
        ['agm','Pendidikan Agama dan Budi Pekerti'],['pnc','Pendidikan Pancasila'],
        ['ind','Bahasa Indonesia'],['mtk','Matematika'],['ipa','Ilmu Pengetahuan Alam'],
        ['ips','Ilmu Pengetahuan Sosial'],['big','Bahasa Inggris'],['pjk','PJOK'],
        ['sej','Sejarah'],['sbd','Seni Budaya'],['inf','Informatika'],
        ['mtl','Matematika Tingkat Lanjut'],['fis','Fisika'],['kim','Kimia'],
        ['bio','Biologi'],['sos','Sosiologi'],['eko','Ekonomi'],['geo','Geografi'],
        ['sjl','Sejarah Tingkat Lanjut'],['bil','Bahasa Inggris Tingkat Lanjut'],
        ['pkw','Prakarya dan Kewirausahaan'],['jaw','Bahasa Jawa']
    ];
    const SC = SUB.map(x=>x[0]);
    const SN = Object.fromEntries(SUB);

    const FD = [
        ['A',['nama','panggilan','nisn','nis','jk','tmp','tgl','agama','kewarganegaraan','nik','anak_ke','jml_saudara','yatim','bahasa','kelas','tahun_ajaran']],
        ['B',['alamat','rt_rw','kelurahan','kecamatan','kab','prov','kodepos','hp','tinggal','jarak']],
        ['C',['gol_darah','penyakit','kelainan','tinggi','berat']],
        ['D',['asal','tgl_ijazah_smp','no_ijazah_smp','lama','masuk','sekolah_pindah','alasan_pindah','kelas_masuk','rombel_masuk','tgl_masuk']],
        ['E_ayah',['ayah','agama_ayah','wn_ayah','pend_ayah','kerja_ayah','gaji_ayah','hp_ayah','ket_ayah']],
        ['E_ibu',['ibu','agama_ibu','wn_ibu','pend_ibu','kerja_ibu','gaji_ibu','hp_ibu','ket_ibu']],
        ['E_al',['al_jalan','al_rt','al_desa','al_kec','al_kab','al_prov','al_pos']],
        ['F',['wali','agama_wali','wn_wali','pend_wali','kerja_wali','gaji_wali','hp_wali','wali_jalan','wali_rt','wali_desa','wali_kec','wali_kab','wali_prov','wali_pos']],
        ['G',['status','tgl_keluar','kelas_keluar','alasan_keluar','tujuan','nopes','no_skl','no_ijazah_sma','tgl_ijazah_sma','no_transkrip','tahun_lulus','lanjut','catatan']]
    ];
    const FK = FD.flatMap(x=>x[1]);

    const FL = {
        nama:'Nama Lengkap', nisn:'NISN', nis:'Nomor Induk (NIS)', jk:'Jenis Kelamin',
        tmp:'Tempat Lahir', tgl:'Tanggal Lahir', kelas:'Kelas', agama:'Agama',
        nik:'NIK', alamat:'Alamat Jalan', ayah:'Nama Ayah', ibu:'Nama Ibu',
        wali:'Nama Wali', hp:'Nomor HP', asal:'Sekolah Asal',
        sakit:'Sakit (hari)', izin:'Izin (hari)', alpa:'Alpa (hari)', kokur:'Kokurikuler',
        ekskul:'Ekstrakurikuler', predikat:'Predikat', sem:'Semester', tahun_ajaran:'Tahun Ajaran'
    };

    let _am = null;
    const AMAP = () => {
        if (_am) return _am;
        _am = {};
        FK.forEach(k => { _am[k] = k; });
        Object.entries(ALX).forEach(([k,a]) => a.split('|').forEach(x => { _am[x] = k; }));
        return _am;
    };

    const fieldOf = h => {
        const parts = String(h ?? '').split('|').map(part => nz(part)).filter(Boolean);
        const aliases = S?.imp?.dictionaries?.columns || {};
        for (const part of parts.reverse()) {
            if (aliases[part]) return aliases[part];
        }
        return AMAP()[nz(h)] || parts.map(part => AMAP()[part]).find(Boolean) || '';
    };
    const mapelOf = h => {
        const parts = String(h ?? '').split('|').map(part => nz(part)).filter(Boolean).reverse();
        const aliases = S?.imp?.dictionaries?.subjects || {};
        for (const part of parts) {
            if (aliases[part]) return aliases[part];
        }
        const x = parts[0] || nz(h);
        if (!x) return '';
        const r = SUB.find(m => nz(m[1]) === x || m[0] === x);
        if (r) return r[0];
        for (const [c,a] of Object.entries(MEX)) {
            if (a.split('|').includes(x)) return c;
        }
        return '';
    };

    const FAMILY_HEADERS = {
        agama: {ayah: 'agama_ayah', ibu: 'agama_ibu', wali: 'agama_wali'},
        kewarganegaraan: {ayah: 'wn_ayah', ibu: 'wn_ibu', wali: 'wn_wali'},
        pend: {ayah: 'pend_ayah', ibu: 'pend_ibu', wali: 'pend_wali'},
        kerja: {ayah: 'kerja_ayah', ibu: 'kerja_ibu', wali: 'kerja_wali'},
        gaji: {ayah: 'gaji_ayah', ibu: 'gaji_ibu', wali: 'gaji_wali'},
        hp: {ayah: 'hp_ayah', ibu: 'hp_ibu', wali: 'hp_wali'},
        ket: {ayah: 'ket_ayah', ibu: 'ket_ibu', wali: ''}
    };
    const FAMILY_PARTS = ['ayah', 'ibu', 'wali'];
    const FAMILY_GROUP_FIELDS = new Set(Object.keys(FAMILY_HEADERS));
    const STUDENT_REQUIRED_FIELDS = ['nisn', 'nama'];

    function headerStats(row, studentOnly = false) {
        let score = 0;
        let identityScore = 0;
        const fields = [];

        (row || []).forEach(header => {
            const text = String(header ?? '').trim();
            if (!text || /^\d+$/.test(text)) {
                fields.push('');
                return;
            }

            const base = hsplit(text).b;
            const field = studentOnly ? fieldOf(text) : (mapelOf(base) ? 'mapel' : (fx(base) || fx(text) || ''));
            fields.push(field);
            if (field) {
                score++;
                if (['nama', 'nisn', 'nis', 'kelas', 'nip', 'nuptk', 'ekskul', 'sakit'].includes(field)) {
                    identityScore++;
                }
            }
        });

        return {score, identityScore, fields};
    }

    function findHeader(aoa, studentOnly = false) {
        let best = null;
        for (let rowIndex = 0; rowIndex < Math.min((aoa || []).length, 30); rowIndex++) {
            const stats = headerStats(aoa[rowIndex], studentOnly);
            if ((stats.identityScore >= 1 || stats.score >= 2)
                && (!best || stats.score > best.score
                    || (stats.score === best.score && stats.identityScore > best.identityScore))) {
                best = {rowIndex, ...stats};
            }
        }

        return best;
    }

    function studentHeaderMapping(headers, overrides = {}) {
        const rawFields = (headers || []).map(fieldOf);
        const rawCounts = rawFields.reduce((counts, field) => {
            if (field) counts[field] = (counts[field] || 0) + 1;
            return counts;
        }, {});
        const assigned = {};
        const recognized = [];
        const uncertain = [];
        const unknown = [];
        const groupOccurrences = {};
        let activeFamily = '';

        headers.forEach((header, index) => {
            const text = String(header ?? '').trim();
            if (!text) return;

            const field = Object.prototype.hasOwnProperty.call(overrides, index)
                ? (overrides[index] || '')
                : rawFields[index];
            const normalized = nz(text);
            const familyMatch = /(^| )(ayah|bapak|father|ibu|mother|wali|guardian)( |$)/.exec(normalized);
            if (familyMatch) {
                const person = familyMatch[2];
                activeFamily = ['bapak', 'father'].includes(person) ? 'ayah'
                    : ['mother'].includes(person) ? 'ibu'
                        : ['guardian'].includes(person) ? 'wali' : person;
            } else if (['ayah', 'ibu', 'wali'].includes(field)) {
                activeFamily = field;
            } else if (!FAMILY_GROUP_FIELDS.has(field)) {
                activeFamily = '';
            }

            if (!field) {
                unknown.push({index, header: text});
                return;
            }

            let mappedField = field;
            if (FAMILY_GROUP_FIELDS.has(field)) {
                const familyFields = FAMILY_HEADERS[field];
                if (activeFamily && familyFields[activeFamily]) {
                    mappedField = familyFields[activeFamily];
                } else if (rawCounts[field] > 1) {
                    const occurrence = groupOccurrences[field] || 0;
                    groupOccurrences[field] = occurrence + 1;
                    const hasBaseField = Object.values(RMAP).includes(field);
                    const groupOffset = hasBaseField && rawCounts[field] >= 4 ? 1 : 0;
                    if (groupOffset && occurrence === 0) {
                        mappedField = field;
                    } else {
                        const family = FAMILY_PARTS[occurrence - groupOffset];
                        mappedField = family ? familyFields[family] : '';
                    }
                } else if (!Object.values(RMAP).includes(field)) {
                    mappedField = '';
                }
            }

            if (!mappedField) {
                uncertain.push({index, header: text, field, reason: 'Kelompok orang tua/wali tidak dapat dipastikan dari judul ini.'});
                return;
            }

            if (assigned[mappedField] !== undefined || (rawCounts[field] > 1 && !FAMILY_GROUP_FIELDS.has(field))) {
                uncertain.push({index, header: text, field: mappedField, reason: 'Judul kolom ganda; pemetaan tidak dapat dipastikan.'});
                if (assigned[mappedField] !== undefined) {
                    const previous = assigned[mappedField];
                    delete assigned[mappedField];
                    const previousEntryIndex = recognized.findIndex(entry => entry.index === previous);
                    if (previousEntryIndex >= 0) {
                        const [previousEntry] = recognized.splice(previousEntryIndex, 1);
                        uncertain.push({...previousEntry, reason: 'Judul kolom ganda; pemetaan tidak dapat dipastikan.'});
                    }
                }
                return;
            }

            assigned[mappedField] = index;
            recognized.push({index, header: text, field: mappedField});
        });

        const unresolved = uncertain.map(column => column.index);
        return {
            fields: Object.fromEntries(Object.entries(assigned).filter(([, index]) => !unresolved.includes(index))),
            recognized,
            uncertain,
            unknown
        };
    }

    function bestControlSheet(w, excludedSheet) {
        const exitFields = ['alasan_keluar', 'tgl_keluar', 'kelas_keluar', 'tujuan', 'no_skl', 'no_ijazah_sma', 'tahun_lulus', 'status'];
        let best = null;
        for (const name of w.SheetNames) {
            if (name === excludedSheet) continue;
            const rows = readRows(w.Sheets[name]);
            for (let rowIndex = 0; rowIndex < Math.min(rows.length, 30); rowIndex++) {
                const fields = (rows[rowIndex] || []).map(fieldOf);
                const exitScore = fields.filter(field => exitFields.includes(field)).length;
                if (!fields.includes('nis') || exitScore < 1) continue;
                if (!best || exitScore > best.exitScore) {
                    best = {name, rows, rowIndex, fields, exitScore, detected: true};
                }
            }
        }

        if (best) return best;
        const legacyName = w.SheetNames.find(name => /kontrol\s*induk/i.test(name));
        if (!legacyName) return null;

        return {
            name: legacyName,
            rows: XLSX.utils.sheet_to_json(w.Sheets[legacyName], {header: 1, defval: ''}),
            rowIndex: -1,
            fields: [],
            detected: false
        };
    }

    // ─── Pembaca biodata berdasarkan judul kolom ──────────────────────────────
    function realRows(w, columnOverrides = {}) {
<<<<<<< HEAD
        const sheetRows = w.SheetNames.filter(name => !/^(?:kontrol\s*induk|petunjuk)$/i.test(String(name).trim())).map(name => ({
=======
        const sheetRows = w.SheetNames.map(name => ({
>>>>>>> 497b07e27e50c1c03a318e4e1e5f3c83921f699c
            name,
            rows: readRows(w.Sheets[name])
        }));
        let best = null;
        for (const sheet of sheetRows) {
            const header = findHeader(sheet.rows, true);
            if (header && (!best || header.score > best.header.score
                || (header.score === best.header.score && header.identityScore > best.header.identityScore))) {
                best = {...sheet, header};
            }
        }

        const legacySheet = best ? null : (sheetRows.find(sheet => /peserta\s*didik/i.test(sheet.name))
            || sheetRows.find(sheet => !/kontrol\s*induk/i.test(sheet.name))
            || sheetRows[0]);
        const selected = best || legacySheet;
        if (!selected) {
            return {rows: [], detection: null, error: 'Tidak ada sheet Excel yang dapat dibaca.'};
        }

        const useHeaders = Boolean(best);
        const headerRowIndex = useHeaders ? selected.header.rowIndex : 4;
        const headers = useHeaders ? (selected.rows[headerRowIndex] || []) : [];
        const mapping = useHeaders ? studentHeaderMapping(headers, columnOverrides) : {fields: {}, recognized: [], uncertain: [], unknown: []};
        const detection = {
            sheet: selected.name,
            sheetCount: w.SheetNames.length,
            headerRow: headerRowIndex + 1,
            headerDetected: useHeaders,
            headers: headers.map(header => String(header ?? '').trim()),
            mapping: mapping.fields,
            legacyFallback: !useHeaders,
            recognized: mapping.recognized,
            uncertain: mapping.uncertain,
            unknown: mapping.unknown,
            columns: headers.map((header, index) => {
                const recognized = mapping.recognized.find(column => column.index === index);
                const uncertain = mapping.uncertain.find(column => column.index === index);
                const unknown = mapping.unknown.find(column => column.index === index);
                return {
                    index,
                    header: String(header ?? ''),
                    field: recognized?.field || uncertain?.field || '',
                    status: recognized ? 'recognized' : (uncertain ? 'uncertain' : (unknown ? 'unknown' : '')),
                    reason: uncertain?.reason || ''
                };
            }).filter(column => column.header !== '')
        };
        detection.missingRequired = useHeaders
            ? STUDENT_REQUIRED_FIELDS.filter(field => mapping.fields[field] === undefined)
            : [];
        detection.requiredMissing = detection.missingRequired.length > 0;
        const columnFor = (field, fallback = -1) => useHeaders ? (mapping.fields[field] ?? -1) : fallback;
        const legacyColumns = {nisn: CI('F'), nama: CI('D')};
        const missing = STUDENT_REQUIRED_FIELDS.filter(field => columnFor(field, legacyColumns[field]) < 0);
        if (missing.length) {
            return {
                rows: [],
                detection,
                error: `Kolom wajib tidak ditemukan: ${missing.map(field => field === 'nisn' ? 'NISN' : 'Nama').join(' dan ')}. Periksa judul kolom pada sheet "${selected.name}".`
            };
        }

        const control = bestControlSheet(w, selected.name);
        const controlRows = control?.rows || [];
        const controlHeaderFields = control?.fields || [];
        const controlHeaderCounts = controlHeaderFields.reduce((counts, field) => {
            if (field) counts[field] = (counts[field] || 0) + 1;
            return counts;
        }, {});
        const controlColumn = (field, fallback = -1) => control?.detected
            ? (controlHeaderCounts[field] === 1 ? controlHeaderFields.indexOf(field) : -1)
            : fallback;
        const controlNisIndex = controlColumn('nis', CI('C'));
        const controlDataStart = control?.detected ? control.rowIndex + 1 : 5;
        const controlMap = {};
        controlRows.slice(controlDataStart).forEach(row => {
            const nisValue = row[controlNisIndex];
            if (nisValue !== undefined && nisValue !== '') {
                controlMap[String(numStr(nisValue)).trim()] = row;
            }
        });
        if (control?.detected) {
            detection.controlSheet = control.name;
            detection.controlHeaderRow = control.rowIndex + 1;
        }

        const out = [];
        const nameIndex = columnFor('nama', CI('D'));
        const nisnIndex = columnFor('nisn', CI('F'));
        const nisIndex = columnFor('nis', CI('C'));
        const yearIndex = columnFor('tahun_ajaran');
        const classIndex = columnFor('kelas');
        const alumniFields = {
            status: 'status',
            tgl_keluar: 'tgl_keluar',
            kelas_keluar: 'kelas_keluar',
            alasan_keluar: 'alasan_keluar',
            tujuan: 'tujuan',
            no_skl: 'no_skl',
            no_ijazah_sma: 'no_ijazah_sma',
            tgl_ijazah_sma: 'tgl_ijazah_sma',
            no_transkrip: 'no_transkrip',
            tahun_lulus: 'tahun_lulus',
            lanjut: 'lanjut'
        };

        selected.rows.slice(headerRowIndex + 1).forEach((row, offset) => {
            const rawName = cl(row[nameIndex]);
            const rawNisn = cl(row[nisnIndex]);
            const rawNis = cl(row[nisIndex]);
            if (!rawName || rawName === '-' || (!rawNisn && !rawNis)) return;

            const student = {};
            let numericNik = false;
            for (const [column, field] of Object.entries(RMAP)) {
                const index = columnFor(field, CI(column));
                let value = index < 0 ? '-' : cl(row[index]);
                if (field === 'kelas_masuk' && value === null && String(row[index] ?? '').trim().toUpperCase() === 'X') {
                    value = 'X';
                }
                if (['tgl', 'tgl_masuk', 'tgl_ijazah_smp'].includes(field) && value !== null) {
                    value = toIso(value);
                }
                if (field === 'nik' && typeof value === 'number' && String(value).replace(/\D/g, '').length === 16) {
                    numericNik = true;
                }
                if (['nis', 'nisn', 'nik'].includes(field) && typeof value === 'number') value = numStr(value);
                student[field] = (value === '' || value == null) ? null : value;
            }

            for (const [field, key] of Object.entries(alumniFields)) {
                const index = columnFor(field);
                if (index < 0) continue;
                let value = cl(row[index]);
                if (field.startsWith('tgl_')) value = toIso(value);
                if (field === 'tahun_lulus') {
                    value = value === '' || value == null || value === '-' ? '' : numStr(value);
                }
                student[key] = (value === '' || value == null) && field !== 'tahun_lulus' ? null : value;
            }

            student.nis = numStr(student.nis);
            student.nisn = normN(String(row[nisnIndex] ?? student.nisn ?? '').trim());
            const classFromSheet = classIndex >= 0 ? normK(row[classIndex]) : '';
            const classFromAdmission = normK((student.kelas_masuk || '') + '-' + (student.rombel_masuk || ''));
            student.kelas = classFromSheet || classFromAdmission;
            const rawAcademicYear = yearIndex >= 0 ? String(row[yearIndex] ?? '').trim() : '';
            student.tahun_ajaran = rawAcademicYear
                ? (normAcademicYear(rawAcademicYear) || rawAcademicYear)
                : yearBeforeHeader(selected.rows, headerRowIndex, selected.name);
            if (numericNik) student.__numeric_nik = true;
            student.jk = /^p/i.test(String(student.jk)) ? 'P' : (/^l/i.test(String(student.jk)) ? 'L' : '');

            const controlRow = controlMap[student.nis];
            if (controlRow) {
                for (const [column, field] of Object.entries(KMAP)) {
                    const valueIndex = controlColumn(field, CI(column));
                    if (valueIndex < 0) continue;
                    let value = cl(controlRow[valueIndex]);
                    if (field.startsWith('tgl')) value = toIso(value);
                    student[field] = (value === '' || value == null) ? null : value;
                }
                for (const field of ['status', 'tahun_lulus', 'lanjut']) {
                    const index = controlColumn(field);
                    if (index < 0) continue;
                    const value = cl(controlRow[index]);
                    if (value !== '' && value != null && !(field === 'tahun_lulus' && value === '-')) {
                        student[field] = field === 'tahun_lulus' ? numStr(value) : value;
                    }
                }
            }

            const graduationText = [student.alasan_keluar, student.status]
                .map(value => String(value ?? '').trim().toLowerCase())
                .filter(Boolean);
            const isGraduated = graduationText.some(value =>
                /\blulus\b/.test(value) && !/\b(belum|tidak)\s+lulus\b/.test(value)
            ) || /^\d{4}$/.test(String(student.tahun_lulus ?? '').trim());
            student.status = isGraduated ? 'Lulus' : (String(student.status ?? '').trim() || 'Aktif');
            if (isGraduated && !student.tahun_lulus && /^\d{4}-\d{2}-\d{2}$/.test(String(student.tgl_keluar ?? ''))) {
                student.tahun_lulus = String(student.tgl_keluar).slice(0, 4);
            }
            student.__excel_row = headerRowIndex + offset + 2;
            out.push(student);
        });

        if (!useHeaders && !out.length) {
            const error = 'Kolom wajib tidak ditemukan: NISN dan Nama. Judul kolom tidak terdeteksi dan fallback format lama tidak menemukan data pada kolom F (NISN) dan D (Nama).';
            return {rows: [], detection, error};
        }

        return {rows: out, detection, error: null};
    }

    function hsplit(h) {
        const t = String(h).trim();
        const m = /\b(?:semester|smt|sem)\s*\.?\s*([1-6])\b|\b(ganjil|genap)\b/i.exec(t);
        if (!m) return {b:t, s:''};
        const b = t.replace(m[0],' ').replace(/[\s\-_:()]+$/,'').replace(/^[\s\-_:()]+/,'').trim();
        return {b:b||t, s:m[2]?(/genap/i.test(m[2])?'Genap':'Ganjil'):m[1]};
    }

    const fx = h => {
        let f = fieldOf(h);
        if (!f) {
            const g = fieldOf(String(h).replace(/[\s_\-.]*\d+$/,''));
            if (g==='ekskul'||g==='predikat') f = g;
        }
        return f;
    };

    // ─── Analisis Sheet Otomatis ────────────────────────────────────────────────
    function analyze(name, aoa, lockType) {
        let best = null;
        for (let r = 0; r < Math.min(aoa.length, 30); r++) {
            const stats = headerStats(aoa[r]);
            if ((stats.identityScore >= 1 || stats.score >= 2)
                && (!best || stats.score > best.sc
                    || (stats.score === best.sc && stats.identityScore > best.idf))) {
                best = {r, sc: stats.score, idf: stats.identityScore};
            }
        }

        if (!best) {
            for (let r = 0; r < Math.min(aoa.length, 15); r++) {
                const textCells = (aoa[r] || []).filter(c => {
                    const s = String(c ?? '').trim();
                    return s !== '' && isNaN(s) && !/^(no|nomor|urut)$/i.test(s);
                });
                if (textCells.length >= 2) {
                    best = {r, sc: 1};
                    break;
                }
            }
        }

        if (!best) return null;
        const hr = best.r, heads = (aoa[hr] || []).map(h => String(h).trim()), map = {}, semOf = {}, base = {};
        heads.forEach((h, i) => {
            if (!h) return;
            const q = hsplit(h);
            base[i] = q.b;
            let m = mapelOf(q.b), f = m ? '' : fx(q.b);
            map[i] = f || (m ? 'm:' + m : '');
            if (q.s && map[i]) semOf[i] = q.s;
        });

        const ks = Object.values(map);
        const has = k => ks.includes(k);
        const nm = ks.filter(k => k.startsWith('m:')).length;
        const ts = [];

        // JIKA HALAMAN SUDAH DIKUNCI (lockType)
        if (lockType) {
            ts.push(lockType);
        } else {
            if (nm || (has('mapelcol') && has('nilaicol'))) ts.push('nilai');
            if (has('ekskul')) ts.push('ekskul');
            if (has('sakit') || has('izin') || has('alpa')) ts.push('hadir');
            if (has('nip') || has('nuptk')) ts.push('guru');
            // Jika ada nama & (nisn / nis / kelas / jk / agama / alamat) maka siswa
            if (has('nama') && (has('nisn') || has('nis') || has('kelas') || has('jk') || has('agama') || has('tmp') || has('tgl'))) {
                ts.push('siswa');
            }
            if (!ts.length) ts.push('siswa');
        }

        let kelas = '';
        if (/^\s*(kelas\s*)?(XII|XI|X|12|11|10)([\s\-._]*[A-Za-z0-9 ]*)?$/i.test(name)) kelas = normK(name);
        if (!kelas) {
            for (let r = 0; r < hr && !kelas; r++) {
                const m = /kelas\s*[:=]?\s*([A-Za-z0-9.\- ]{1,10})/i.exec((aoa[r] || []).join(' '));
                if (m && /^(XII|XI|X|12|11|10)/i.test(m[1].trim())) kelas = normK(m[1]);
            }
        }
        const tahunAjaran = yearBeforeHeader(aoa, hr, name);
        return {name, aoa, hr, heads, map, semOf, base, t: ts[0] || 'siswa', ts, kelas, tahunAjaran};
    }

    // ─── Deteksi Kolom Otomatis (assignX Sesuai Prototipe) ──────────────────────
    function assignX(z, pt) {
        const ty = k => !k || k === 'x' ? '' : (k.startsWith('m:') || k === 'new' || k === 'mapelcol' || k === 'nilaicol') ? 'nilai' : ['sakit', 'izin', 'alpa', 'kokur'].includes(k) ? 'hadir' : ['ekskul', 'predikat'].includes(k) ? 'ekskul' : FK.includes(k) ? 'siswa' : '';
        const ty2 = j => {
            const m = z.map[j];
            return m === 'x' ? (z.xt && z.xt[j]) : m === 'new' ? 'nilai' : ty(m);
        };
        const n = (z.heads || []).length;
        const single = (z.ts && z.ts.length === 1) ? z.ts[0] : '';
        z.xt = z.xt || {};
        for (let i = 0; i < n; i++) {
            const h = z.heads[i];
            if (!h || (z.map[i] && z.map[i] !== 'x' && z.map[i] !== 'new')) continue;
            const vals = (z.aoa || []).slice(z.hr + 1).map(r => String(r[i] ?? '').trim()).filter(x => x !== '' && x.toUpperCase() !== 'X');
            if (!vals.length) {
                z.map[i] = '';
                continue;
            }
            const b = z.base[i] || h, nb = nz(b);
            if (/^((no|nomor)( urut)?|(jumlah|total|rata|ranking|rank|peringkat|kkm|kktp|absen|urut)\b.*)$/.test(nb)) {
                z.map[i] = '';
                continue;
            }
            let T = /terlambat|dispensasi|bolos|cuti|hadir/.test(nb) ? 'hadir' : /ekskul|ekstra|pramuka|organisasi/.test(nb) ? 'ekskul' : '';
            for (let d = 1; d < n && !T; d++) {
                T = (i - d >= 0 ? ty2(i - d) : '') || (i + d < n ? ty2(i + d) : '');
            }
            T = T || single || pt;
            z.xt[i] = T;
            const nn = x => +x.replace(',', '.');
            if (T === 'nilai' && vals.filter(x => !isNaN(nn(x)) && nn(x) >= 0 && nn(x) <= 100).length >= vals.length * 0.8) {
                z.map[i] = 'new';
            } else if (T === 'siswa') {
                z.map[i] = fieldOf(b) || '';
            } else {
                z.map[i] = z.map[i] || '';
            }
        }
    }

    // ─── Pilihan Dropdown Pemetaan Kolom (FOPT) ─────────────────────────────────
    function FOPT(t) {
        const c = [['nisn','NISN'], ['nama','Nama Lengkap'], ['kelas','Kelas'], ['sem','Semester']];
        if (t === 'siswa') {
            const list = [
                ['nisn', 'NISN (10 Digit)'],
                ['nis', 'Nomor Induk (NIS)'],
                ['nama', 'Nama Lengkap'],
                ['kelas', 'Kelas'],
                ['tahun_ajaran', 'Tahun Ajaran'],
                ['jk', 'Jenis Kelamin (L/P)'],
                ['tmp', 'Tempat Lahir'],
                ['tgl', 'Tanggal Lahir (YYYY-MM-DD)'],
                ['agama', 'Agama'],
                ['nik', 'NIK (16 Digit)'],
                ['kewarganegaraan', 'Kewarganegaraan'],
                ['alamat', 'Alamat Jalan'],
                ['rt_rw', 'RT / RW'],
                ['kelurahan', 'Kelurahan / Desa'],
                ['kecamatan', 'Kecamatan'],
                ['kab', 'Kabupaten / Kota'],
                ['prov', 'Provinsi'],
                ['kodepos', 'Kode Pos'],
                ['hp', 'Nomor HP Siswa'],
                ['tinggal', 'Tempat Tinggal'],
                ['jarak', 'Jarak ke Sekolah (km)'],
                ['gol_darah', 'Golongan Darah'],
                ['tinggi', 'Tinggi Badan (cm)'],
                ['berat', 'Berat Badan (kg)'],
                ['asal', 'Sekolah Asal SMP'],
                ['ayah', 'Nama Ayah'],
                ['pend_ayah', 'Pendidikan Ayah'],
                ['kerja_ayah', 'Pekerjaan Ayah'],
                ['gaji_ayah', 'Penghasilan Ayah'],
                ['hp_ayah', 'HP Ayah'],
                ['ibu', 'Nama Ibu'],
                ['pend_ibu', 'Pendidikan Ibu'],
                ['kerja_ibu', 'Pekerjaan Ibu'],
                ['gaji_ibu', 'Penghasilan Ibu'],
                ['hp_ibu', 'HP Ibu'],
                ['wali', 'Nama Wali'],
                ['status', 'Status Siswa'],
                ['x', '＋ Kolom baru (nama = judul kolom)']
            ];
            return list;
        } else if (t === 'nilai') {
            return [
                ...c,
                ...SUB.map(m => ['m:' + m[0], 'Mapel: ' + m[1]]),
                ['new', '＋ Mapel baru (nama = judul kolom)'],
                ['mapelcol', 'Kolom nama mapel (format panjang)'],
                ['nilaicol', 'Kolom nilai (format panjang)']
            ];
        } else if (t === 'hadir') {
            return [
                ...c,
                ['sakit', 'Sakit (hari)'],
                ['izin', 'Izin (hari)'],
                ['alpa', 'Alpa (hari)'],
                ['kokur', 'Deskripsi Kokurikuler'],
                ['x', '＋ Kolom baru']
            ];
        } else if (t === 'ekskul') {
            return [
                ...c,
                ['ekskul', 'Nama Ekstrakurikuler'],
                ['predikat', 'Predikat Nilai (A/B/C/D)'],
                ['x', '＋ Kolom baru']
            ];
        } else {
            return [
                ['nama', 'Nama Lengkap'],
                ['nip', 'NIP'],
                ['nuptk', 'NUPTK'],
                ['jk', 'Jenis Kelamin'],
                ['hp', 'Nomor HP'],
                ['status', 'Status']
            ];
        }
    }

    // ─── Bangun Baris Data Terstruktur (buildRows) ──────────────────────────────
    function buildRows(I) {
        const t = I.t;
        const newM = [], lm = {}, out = [];
        const sw = /^(rata|jumlah|total|ttd|mengetahui|keterangan)/i;
        const com = ['nisn','nama','kelas','sem','nis','tahun_ajaran'];

        const newFor = h => {
            const q = nz(h);
            if (!lm[q]) {
                const c = (q.replace(/[^a-z]/g,'') + ' xxx').slice(0,3).trim();
                lm[q] = c;
                newM.push({kode: c, nama: String(h).trim()});
            }
            return lm[q];
        };

        const nv = (k,v) => {
            if (['nisn','nis','nik','hp','nip'].includes(k)) return numStr(v);
            if (k.startsWith('tgl')||k==='tgl') return toIso(v);
            if (k==='jk') return /^p/i.test(String(v)) ? 'P' : 'L';
            if (k==='kelas') return normK(v);
            if (k==='tahun_ajaran') return normAcademicYear(v) || String(v).trim();
            if (k==='sem') return normSem(v);
            if (['sakit','izin','alpa'].includes(k)) return /\d/.test(String(v)) ? parseInt(String(v).match(/\d+/)[0]) : 0;
            if ((k.startsWith('m:')||k==='new'||k==='nilaicol') && typeof v==='string' && /^\d+([.,]\d+)?$/.test(v.trim())) {
                return v.trim().replace(',', '.');
            }
            return v;
        };

        (I.raws||[]).forEach(Z => {
            const nc = {};
            Object.entries(Z.map||{}).forEach(([i,k]) => {
                if (k === 'new') nc[i] = newFor(Z.base[i] || Z.heads[i]);
            });
            for (let r = Z.hr + 1; r < (Z.aoa||[]).length; r++) {
                const row = Z.aoa[r];
                if (!row) continue;
                const o0 = {}, bk = {};
                Object.entries(Z.map||{}).forEach(([i,k]) => {
                    if (!k) return;
                    const v = cl(row[i]);
                    if (v === '' || v == null) return;
                    const sm = Z.semOf[i] || '';
                    const key = k.startsWith('m:') ? k.slice(2) : (k === 'new' ? nc[i] : k);
                    if (com.includes(k)) {
                        o0[k] = nv(k, v);
                    } else {
                        const B = bk[sm] = bk[sm] || {};
                        if (k === 'ekskul' || k === 'predikat') {
                            (B[k] = B[k] || []).push(nv(k, v));
                        } else {
                            B[key] = nv(k, v);
                        }
                    }
                });
                if (!o0.nisn && !o0.nama) continue;
                if (!o0.nisn && sw.test(String(o0.nama).trim())) continue;
                if (!o0.kelas && Z.kelas) o0.kelas = Z.kelas;
                if (!o0.tahun_ajaran && Z.tahunAjaran) o0.tahun_ajaran = Z.tahunAjaran;
                if (o0.nisn) o0.nisn = normN(o0.nisn);

                if (t === 'siswa') {
                    const o = {...o0, ...(bk['']||{})};
                    if (typeof o.nik === 'number' && String(o.nik).replace(/\D/g, '').length === 16) {
                        o.__numeric_nik = true;
                    }
                    out.push({...o, __excel_row: r + 1});
                    continue;
                }
                const sms = Object.keys(bk).filter(x=>x);
                const keys = sms.length ? sms : [''];
                keys.forEach(sm => {
                    const o = {...o0, ...(bk['']||{}), ...(bk[sm]||{})};
                    if (sm) o.sem = sm;
                    if (t === 'ekskul') {
                        const eks = (o.ekskul || []).filter(x=>String(x).trim());
                        const pd = o.predikat || [];
                        eks.forEach((n,j) => out.push({...o, ekskul:String(n).trim(), predikat:pd[j] ?? (pd.length===1?pd[0]:'A')}));
                        return;
                    }
                    if (t === 'hadir' && !['sakit','izin','alpa','kokur'].some(k=>o[k]!=null)) return;
                    if (t === 'nilai' && !Object.keys(o).some(k=>!com.includes(k))) return;
                    out.push({...o, __excel_row: r + 1});
                });
            }
        });
        I.newM = newM.filter(m => out.some(r => r[m.kode] != null && r[m.kode] !== ''));
        return out;
    }

    // ─── Validasi Per Baris ─────────────────────────────────────────────────────
    function validateRows(rows, t) {
        const errors = [], warns = [];
        const seenNisn = new Set();
        const statuses = rows.map((row, idx) => ({
            status: 'ready',
            errors: [],
            warns: [],
            row: row.__excel_row || idx + 2
        }));
        const addError = (idx, id, msg) => {
            const entry = {no: statuses[idx].row, id, msg, index: idx};
            errors.push(entry);
            statuses[idx].status = 'error';
            statuses[idx].errors.push(msg);
        };
        const addWarning = (idx, id, msg) => {
            const entry = {no: statuses[idx].row, id, msg, index: idx};
            warns.push(entry);
            if (statuses[idx].status !== 'error') statuses[idx].status = 'warning';
            statuses[idx].warns.push(msg);
        };
        const classSelect = document.querySelector('[name="kelas"]') || document.getElementById('kelas');
        const knownClasses = classSelect
            ? [...classSelect.options].map(option => normK(option.value)).filter(Boolean)
            : [];
        const valueFields = new Set([...SC, ...(S.imp.newM || []).map(mapel => mapel.kode), 'nilai', 'nilaicol']);
        const dateFields = ['tgl', 'tgl_masuk', 'tgl_ijazah_smp', 'tgl_keluar', 'tgl_ijazah_sma'];
        const validDate = value => {
            const match = /^(\d{4})-(\d{2})-(\d{2})$/.exec(String(value ?? ''));
            if (!match) return false;
            const date = new Date(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
            return date.getFullYear() === Number(match[1])
                && date.getMonth() === Number(match[2]) - 1
                && date.getDate() === Number(match[3]);
        };

        rows.forEach((r, idx) => {
            const id = r.nama || (r.nisn ? 'NISN: ' + r.nisn : 'Baris ' + (idx + 2));

            if (t === 'siswa' || !t) {
                if (!r.nama && !r.nisn) {
                    addError(idx, 'Baris ' + statuses[idx].row, 'Nama dan NISN kosong');
                }
                if (!r.nisn) {
                    addError(idx, id, 'NISN kosong — baris ini akan dilewati');
                } else if (!/^\d{10}$/.test(r.nisn)) {
                    addError(idx, id, 'NISN bukan 10 digit: "' + r.nisn + '"');
                } else if (seenNisn.has(r.nisn)) {
                    addWarning(idx, id, 'NISN ganda dalam file: ' + r.nisn);
                } else {
                    seenNisn.add(r.nisn);
                }
                if (!r.nama) {
                    addError(idx, id, 'Nama kosong');
                }
            } else if (t === 'nilai' || t === 'hadir' || t === 'ekskul') {
                if (!r.nisn && !r.nama) {
                    addError(idx, 'Baris ' + statuses[idx].row, 'Tidak ada NISN atau Nama untuk identifikasi');
                }
                if (r.nisn && !/^\d{10}$/.test(r.nisn)) {
                    addError(idx, id, 'NISN bukan 10 digit: "' + r.nisn + '"');
                }
                const rowClass = normK(r.kelas || S.imp.kelas);
                if (!rowClass) {
                    addError(idx, id, 'Kelas tidak ditentukan');
                } else if (knownClasses.length && !knownClasses.includes(rowClass)) {
                    addError(idx, id, `Kelas tidak dikenal: "${rowClass}"`);
                } else if (S.imp.mode === 'kelas' && S.imp.kelas && rowClass !== S.imp.kelas) {
                    addError(idx, id, `Kelas pada baris (${rowClass}) berbeda dari kelas yang dipilih (${S.imp.kelas})`);
                }
                const explicitSemester = Number(normSem(r.sem) || (['3', '4', '5', '6'].includes(String(S.imp.sem)) ? S.imp.sem : ''));
                if (explicitSemester >= 3 && rowClass) {
                    const grade = /^(XII|XI|X)(?:-|$)/.exec(rowClass)?.[1];
                    const expectedGrade = explicitSemester <= 2 ? 'X'
                        : explicitSemester <= 4 ? 'XI' : 'XII';
                    if (grade && grade !== expectedGrade) {
                        addError(idx, id, `Semester ${explicitSemester} tidak sesuai dengan tingkat kelas ${grade}`);
                    }
                }
                if (t === 'nilai') {
                    const values = Object.entries(r).filter(([key, value]) => value !== '' && value != null && valueFields.has(key));
                    values.forEach(([key, value]) => {
                        const numeric = Number(String(value).replace(',', '.'));
                        if (!Number.isFinite(numeric) || numeric < 0 || numeric > 100) {
                            addError(idx, id, `Nilai ${SN[key] || key} harus angka 0–100`);
                        }
                    });
                    if (!values.length) addError(idx, id, 'Nilai mapel tidak ditemukan');
                }
                if (t === 'hadir') {
                    ['sakit', 'izin', 'alpa'].forEach(key => {
                        if (r[key] !== '' && r[key] != null && !Number.isFinite(Number(r[key]))) {
                            addError(idx, id, `${FL[key] || key} harus berupa angka`);
                        }
                    });
                }
                if (t === 'ekskul' && !String(r.ekskul || '').trim()) {
                    addError(idx, id, 'Nama ekstrakurikuler kosong');
                }
            } else if (t === 'guru') {
                if (!r.nama) {
                    addError(idx, 'Baris ' + statuses[idx].row, 'Nama guru kosong');
                }
            }

            if (r.nik && r.nik !== '-' && String(r.nik).replace(/\D/g, '').length !== 16) {
                addWarning(idx, id, 'NIK sebaiknya terdiri dari 16 digit');
            }
            dateFields.forEach(key => {
                if (!r[key] || r[key] === '-') return;
                if (!validDate(r[key])) {
                    addWarning(idx, id, `${FL[key] || key} bukan tanggal yang valid`);
                    return;
                }
                const date = new Date(`${r[key]}T00:00:00`);
                if (date > new Date()) addWarning(idx, id, `${FL[key] || key} berada di masa depan`);
                if (key === 'tgl' && (Date.now() - date.getTime()) / (365.2425 * 864e5) > 120) {
                    addWarning(idx, id, 'Usia berdasarkan tanggal lahir melebihi 120 tahun');
                }
            });
        });
        return {errors, warns, statuses};
    }

    // ─── State Import ───────────────────────────────────────────────────────────
    const S = {
        imp: {
            t: 'siswa',
            mode: 'semua',
            kelas: 'X-1',
            sem: 'auto',
            dup: 'skip',
            ta: '2024/2025',
            raws: null,
            rows: null,
            detection: null,
            studentColumnOverrides: {},
            studentColumnCorrections: {},
            studentOriginalMap: {},
            dictionaries: {columns: {}, subjects: {}},
            savedFormats: [],
            mappingIssues: [],
            statuses: [],
            warningsConfirmed: false,
            fileName: '',
            newM: [],
            errors: [],
            warns: []
        }
    };
    let studentWorkbook = null;
    window.JosBookState = S;

    function readFormOptions() {
        const I = S.imp;
        const taEl = document.querySelector('input[name="tahun_ajaran"]') || document.getElementById('tahun_ajaran');
        I.ta = taEl ? taEl.value.trim() : '';

        const semEl = document.querySelector('select[name="semester"]') || document.getElementById('semester');
        if (semEl && semEl.value) I.sem = semEl.value;
        const monthEl = document.querySelector('select[name="bulan"]') || document.getElementById('bulan');
        if (monthEl && monthEl.value) I.sem = Number(monthEl.value) >= 7 ? '1' : '2';

        const dupEl = document.querySelector('select[name="duplikat"]') || document.getElementById('duplikat');
        if (dupEl && dupEl.value) I.dup = dupEl.value;

        const modeEl = document.querySelector('select[name="mode"]') || document.getElementById('mode');
        if (modeEl && modeEl.value) I.mode = (modeEl.value === 'kelas') ? 'kelas' : 'semua';

        const kelasEl = document.querySelector('[name="kelas"]') || document.getElementById('kelas');
        if (kelasEl) {
            if (kelasEl.value === '*') {
                I.mode = 'semua';
                I.kelas = '';
            } else if (modeEl && modeEl.value === 'semua') {
                I.mode = 'semua';
                I.kelas = normK(kelasEl.value) || '';
            } else {
                I.mode = 'kelas';
                I.kelas = normK(kelasEl.value) || '';
            }
        }
    }

    function syncDetectedAcademicYear(rows) {
        if (['nilai', 'hadir', 'ekskul'].includes(S.imp.t)) return;
        const years = [...new Set((rows || []).map(row => normAcademicYear(row.tahun_ajaran || '')).filter(Boolean))];
        if (years.length !== 1) return;

        const I = S.imp;
        I.ta = years[0];
        const taEl = document.querySelector('input[name="tahun_ajaran"]') || document.getElementById('tahun_ajaran');
        if (taEl) taEl.value = years[0];
    }

    function importEndpoint(action) {
        const endpointMeta = document.querySelector('meta[name="batch-import-url"]');
        const endpoint = endpointMeta ? endpointMeta.getAttribute('content') : '';
        return endpoint.replace(/\/save-batch\/?$/, `/${action}`);
    }

    async function postImportJson(endpoint, payload) {
        const token = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = token ? token.getAttribute('content') : '';
        const response = await fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        });
        const contentType = response.headers.get('content-type') || '';
        if (!contentType.includes('application/json')) {
            await response.text();
            throw new Error(`Server mengembalikan halaman, bukan JSON (HTTP ${response.status}). Muat ulang halaman dan pastikan sesi login masih aktif.`);
        }

        const data = await response.json();
        if (!response.ok || !data.success) {
            throw new Error(data.message || `Permintaan import gagal (HTTP ${response.status}).`);
        }

        return data;
    }

    function savedFormatFor(formats, index) {
        return Array.isArray(formats) ? formats[index] : null;
    }

    function applyFormatMapping(sheet, format) {
        if (!format?.mapping) return false;
        Object.entries(format.mapping).forEach(([column, mapping]) => {
            if (Number.isInteger(Number(column)) && column in sheet.map) {
                sheet.map[column] = mapping;
            }
        });
        sheet.formatId = format.format_id;
        return true;
    }

    const escapeHtml = value => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');

    function mappingIssues(I) {
        if (I.t === 'siswa') {
            return (I.detection?.missingRequired || []).map(field =>
                `Kolom wajib ${field === 'nisn' ? 'NISN' : 'Nama'} belum dipetakan.`
            );
        }

        const mapped = (I.raws || []).flatMap(sheet => Object.values(sheet.map || {}));
        const hasIdentity = mapped.includes('nisn') || mapped.includes('nama');
        const issues = [];
        if (!hasIdentity) issues.push('Kolom identitas wajib dipetakan: NISN atau Nama.');
        if (I.t === 'nilai' && !mapped.some(key => key.startsWith('m:') || key === 'new')
            && !mapped.some(key => SC.includes(key) || (I.newM || []).some(mapel => mapel.kode === key))
            && !(mapped.includes('mapelcol') && mapped.includes('nilaicol'))) {
            issues.push('Kolom nilai mapel belum terdeteksi atau dipetakan.');
        }
        if (I.t === 'hadir' && !mapped.some(key => ['sakit', 'izin', 'alpa', 'kokur'].includes(key))) {
            issues.push('Kolom kehadiran belum terdeteksi atau dipetakan.');
        }
        if (I.t === 'ekskul' && !mapped.includes('ekskul')) {
            issues.push('Kolom nama ekstrakurikuler belum terdeteksi atau dipetakan.');
        }
        return issues;
    }

    // ─── Render Panel Pratinjau & Pemetaan Kolom ────────────────────────────────
    function renderPreviewUI() {
        const I = S.imp;
        const container = document.getElementById('josbook-import-preview-area');
        if (!container) return;

        const t = I.t;
        const tLabel = {
            siswa: 'Data Siswa & Induk',
            nilai: 'Data Nilai Rapor',
            hadir: 'Ketidakhadiran & Kokurikuler',
            ekskul: 'Ekstrakurikuler',
            guru: 'Data Guru'
        };

        const rows = I.rows || [];
        const totalRows = rows.length;
        const uniqueClasses = [...new Set(rows.map(r => normK(r.kelas || '')).filter(Boolean))];
        const uniqueAcademicYears = [...new Set(rows.map(r => normAcademicYear(r.tahun_ajaran || '')).filter(Boolean))];
        const {errors, warns, statuses} = validateRows(rows, t);
        I.errors = errors;
        I.warns = warns;
        I.statuses = statuses;
        I.mappingIssues = mappingIssues(I);
        const readyCount = statuses.filter(row => row.status === 'ready').length;
        const warningCount = statuses.filter(row => row.status === 'warning').length;
        const errorCount = statuses.filter(row => row.status === 'error').length;
        const distinctStudents = new Set(rows.map(row => row.nisn || row.nama).filter(Boolean)).size;
        const subjectCount = new Set(rows.flatMap(row => [
            ...Object.keys(row).filter(key => SC.includes(key) || (I.newM || []).some(mapel => mapel.kode === key)),
            ...(row.mapelcol ? [String(row.mapelcol).trim().toLowerCase()] : [])
        ])).size;

        // Breakdown per kelas
        const classBreakdown = {};
        rows.forEach(r => {
            const k = I.mode === 'semua' ? (normK(r.kelas) || '(Belum Ada Kelas)') : I.kelas;
            classBreakdown[k] = (classBreakdown[k] || 0) + 1;
        });

        // 1. Deteksi Otomatis & Pemetaan Kolom (detBox Sesuai Prototype)
        let detHtml = '';
        if (t === 'siswa' && I.detection) {
            const detected = I.detection;
            const studentFields = [...new Set([...Object.values(RMAP), ...Object.values(KMAP)])];
            const mappingNeedsAttention = detected.uncertain.length > 0
                || detected.unknown.length > 0
                || (detected.missingRequired || []).length > 0;
            detHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h3 style="margin:0">2. Hasil Deteksi Otomatis</h3>
                    <span class="bd ok" style="font-size:12px;padding:4px 10px">Terdeteksi: Data Siswa</span>
                </div>
                <div class="bar" style="flex-wrap:wrap;gap:6px">
                    <span class="bd">Sheet “${escapeHtml(detected.sheet)}” · judul baris ${detected.headerRow}</span>
                    <span class="bd">${detected.sheetCount} sheet</span>
                    <span class="bd ok">${distinctStudents} siswa</span>
                    ${uniqueClasses.length ? `<span class="bd ok">Kelas: <b>${uniqueClasses.join(', ')}</b></span>` : ''}
                    ${uniqueAcademicYears.length ? `<span class="bd ok">Tahun ajaran: <b>${uniqueAcademicYears.join(', ')}</b></span>` : ''}
                </div>
                <details ${mappingNeedsAttention ? 'open' : ''} style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0">
                    <summary style="cursor:pointer;font-weight:600;font-size:13px;color:var(--pr, #2563eb)">🔍 Lihat / Ubah Pemetaan Kolom Excel</summary>
                    <p class="sub" style="margin:6px 0 10px;font-size:12px">Kolom yang tidak dikenali dapat dipetakan di sini. Kolom wajib: NISN dan Nama.</p>
                    ${I.mappingIssues.map(issue => `<p class="bd e">${escapeHtml(issue)}</p>`).join('')}
                    <div class="tw" style="max-height:260px;overflow:auto">
                        <table><thead><tr><th>Kolom di Excel</th><th>Status deteksi</th><th>Dibaca sebagai</th></tr></thead><tbody>
                            ${detected.columns.map(column => `
                                <tr>
                                    <td>${escapeHtml(column.header)}</td>
                                    <td><span class="bd ${column.status === 'recognized' ? 'ok' : 'w'}">${column.status === 'recognized' ? 'Dikenali' : escapeHtml(column.reason || 'Perlu pemetaan')}</span></td>
                                    <td><select style="width:100%;font-size:12px;padding:4px" onchange="window.changeStudentColumnMap(${column.index}, this.value)">
                                        <option value="">— abaikan —</option>
                                        ${studentFields.map(field => `<option value="${escapeHtml(field)}" ${(column.field || '') === field ? 'selected' : ''}>${escapeHtml(FL[field] || field)}</option>`).join('')}
                                    </select></td>
                                </tr>`).join('')}
                        </tbody></table>
                    </div>
                </details>
            </div>`;
        } else if (I.raws) {
            const opts = FOPT(t);
            const ignoredCols = (I.raws || []).reduce((acc, z) => acc + (z.heads || []).filter((h, i) => h && !z.map[i]).length, 0);
            const uncertainCols = (I.raws || []).reduce((acc, z) => acc + (z.uncertain || []).length, 0);
            const mappingNeedsAttention = ignoredCols > 0 || uncertainCols > 0 || I.mappingIssues.length > 0;
            const subjectSummary = subjectCount ? `<span class="bd"> ${subjectCount} mapel</span>` : '';
            const studentSummary = totalRows ? `<span class="bd">${distinctStudents} siswa</span>` : '';
            const selectedSheet = I.detection?.sheet || (I.raws || []).map(z => z.name).join(', ');

            detHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h3 style="margin:0">2. Hasil Deteksi Otomatis</h3>
                    <span class="bd ok" style="font-size:12px;padding:4px 10px">Terdeteksi: ${tLabel[t] || t}</span>
                </div>
                <div class="bar" style="flex-wrap:wrap;gap:6px">
                    <span class="bd ok">Terdeteksi: ${tLabel[t] || escapeHtml(t)}</span>
                    <span class="bd">${I.detection?.sheetCount || (I.raws || []).length} sheet</span>
                    ${selectedSheet ? `<span class="bd">Sheet “${escapeHtml(selectedSheet)}”${I.detection?.headerRow ? ` · judul baris ${I.detection.headerRow}` : ''}</span>` : ''}
                    ${studentSummary}
                    ${subjectSummary}
                    ${(I.newM || []).length ? `<span class="bd w">${I.newM.length} mapel baru</span>` : ''}
                    ${ignoredCols ? `<span class="bd w">${ignoredCols} kolom diabaikan</span>` : ''}
                    ${(I.raws || []).map(z => `<span class="bd">Sheet “${escapeHtml(z.name)}” · judul baris ${z.hr + 1}${z.kelas ? ` · ${escapeHtml(z.kelas)}` : ''}</span>`).join('')}
                    ${I.fileName ? `<span class="bd">${escapeHtml(I.fileName)}</span>` : ''}
                    ${uniqueClasses.length ? `<span class="bd ok">Kelas: <b>${uniqueClasses.join(', ')}</b></span>` : ''}
                    ${uniqueAcademicYears.length ? `<span class="bd ok">Tahun ajaran: <b>${uniqueAcademicYears.join(', ')}</b></span>` : ''}
                    ${(I.newX||[]).length ? `<span class="bd ok">Kolom Baru (${I.newX.length}): <b>${I.newX.map(x=>x.l).join(', ')}</b></span>` : ''}
                </div>
                ${I.mappingIssues.map(issue => `<p class="bd e" style="margin-top:8px">${escapeHtml(issue)}</p>`).join('')}

                <details ${mappingNeedsAttention ? 'open' : ''} style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0">
                    <summary style="cursor:pointer;font-weight:600;font-size:13px;color:var(--pr, #2563eb)">
                        🔍 Lihat / Ubah Pemetaan Kolom Excel
                    </summary>
                    <p class="sub" style="margin:6px 0 10px;font-size:12px">
                        Jika ada kolom yang belum cocok, pilih padanan kolom di bawah. Pratinjau akan langsung diperbarui otomatis.
                    </p>
                    ${(I.raws||[]).map((z, si) => `
                        <div style="margin-bottom:12px">
                            <strong style="font-size:12px;color:#475569">Sheet “${escapeHtml(z.name)}” · judul baris ${z.hr + 1}</strong>
                            <div class="tw" style="margin-top:6px;max-height:220px;overflow-y:auto">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:35%">Kolom di Excel</th>
                                            <th style="width:30%">Contoh Baris 1</th>
                                            <th style="width:35%">Dibaca Sebagai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        ${(z.heads||[]).map((h, i) => {
                                            if (!h) return '';
                                            const sampleVal = (z.aoa && z.aoa[z.hr + 1]) ? String(z.aoa[z.hr + 1][i] ?? '') : '-';
                                            const currentMap = z.map[i] || '';
                                            return `
                                                <tr>
                                                    <td><strong>${escapeHtml(h)}</strong></td>
                                                    <td style="color:#64748b;font-size:12px">${escapeHtml(sampleVal.slice(0, 35))}</td>
                                                    <td>
                                                        <select class="cmap-select" style="width:100%;font-size:12px;padding:4px" onchange="window.changeColMap(${si}, ${i}, this.value)">
                                                            <option value="">— abaikan —</option>
                                                            ${opts.map(([v, l]) => `<option value="${v}" ${currentMap === v ? 'selected' : ''}>${l}</option>`).join('')}
                                                        </select>
                                                    </td>
                                                </tr>`;
                                        }).join('')}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    `).join('')}
                </details>
            </div>`;
        }

        // 2. Peringatan & Laporan Validasi
        let warnHtml = '';
        if (errors.length || warns.length) {
            warnHtml = `
            <div class="card" style="margin-top:14px;border-left:3px solid ${errors.length ? '#c0392b' : '#e67e22'}">
                <h3 style="margin:0 0 8px;font-size:13px">⚠️ Laporan Validasi (${errors.length} error, ${warns.length} peringatan)</h3>
                <div style="max-height:160px;overflow-y:auto">
                    ${errors.map(e => `<div class="bar" style="font-size:12px;padding:4px 0;border-bottom:1px solid #f5e0de"><span class="bd e" style="min-width:50px">Error</span> <b>Baris ${e.no}</b> · ${escapeHtml(e.id)} — ${escapeHtml(e.msg)}</div>`).join('')}
                    ${warns.map(w => `<div class="bar" style="font-size:12px;padding:4px 0;border-bottom:1px solid #fdf3e1"><span class="bd w" style="min-width:70px">Peringatan</span> <b>Baris ${w.no}</b> · ${escapeHtml(w.id)} — ${escapeHtml(w.msg)}</div>`).join('')}
                </div>
                <div class="bar" style="margin-top:10px">
                    ${errors.length ? `<button type="button" class="btn" style="font-size:12px;padding:5px 10px" onclick="downloadValidationReport()">⬇ Unduh laporan error (CSV)</button>` : ''}
                </div>
            </div>`;
        }

        // 3. Pratinjau Tabel & Tombol Eksekusi
        const allKeys = [...new Set(rows.flatMap(row => Object.keys(row).filter(key => !key.startsWith('__'))))];
        const priorityKeys = ['nisn','nis','nama','kelas','tahun_ajaran','jk','tgl','ayah','ibu','sakit','izin','alpa','ekskul','predikat'];
        const previewKeys = priorityKeys.filter(k => allKeys.includes(k))
            .concat(allKeys.filter(k => !priorityKeys.includes(k)))
            .filter((key, index, list) => list.indexOf(key) === index);
        const previewRows = rows;
        const statusByRow = statuses;
        const warningsAccepted = Boolean(I.warningsConfirmed);

        let prevHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <h3 style="margin:0">3. Pratinjau · ${totalRows} baris</h3>
                    <div style="display:flex;gap:8px">
                        <span class="bd ok">✔ ${readyCount} Siap</span>
                        ${warningCount ? `<span class="bd w">⚠️ ${warningCount} Peringatan</span>` : ''}
                        ${errorCount ? `<span class="bd e">❌ ${errorCount} Error</span>` : ''}
                    </div>
                </div>
                ${!totalRows ? `<p class="bd e">Belum ada baris yang dapat dipratinjau. ${escapeHtml(I.mappingIssues[0] || 'Periksa format file atau pemetaan kolom.')}</p>` : ''}

                <div class="bar" style="flex-wrap:wrap;gap:6px;margin-bottom:12px">
                    ${Object.entries(classBreakdown).map(([k, n]) => `<span class="bd ${k === '(Belum Ada Kelas)' ? 'e' : 'ok'}">${escapeHtml(k)}: <b>${n}</b> baris</span>`).join('')}
                </div>

                <div class="tw"><table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Status</th>
                            ${previewKeys.map(k => `<th>${escapeHtml(FL[k] || SN[k] || k.toUpperCase())}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${previewRows.map((r, i) => `
                            <tr style="background:${statusByRow[i].status === 'error' ? '#fff1f0' : statusByRow[i].status === 'warning' ? '#fffbeb' : '#f0fdf4'}">
                                <td>${i + 1} <small>(Excel ${statusByRow[i].row})</small></td>
                                <td><span class="bd ${statusByRow[i].status === 'error' ? 'e' : statusByRow[i].status === 'warning' ? 'w' : 'ok'}">${statusByRow[i].status === 'error' ? 'Error' : statusByRow[i].status === 'warning' ? 'Peringatan' : 'Siap'}</span>${[...statusByRow[i].errors, ...statusByRow[i].warns].map(message => `<div style="font-size:11px;margin-top:3px">${escapeHtml(message)}</div>`).join('')}</td>
                                ${previewKeys.map(k => `<td contenteditable="true" data-row="${i}" data-field="${escapeHtml(k)}" onblur="window.editImportCell(this)" style="min-width:80px">${r[k] !== undefined && r[k] !== null && r[k] !== '' ? escapeHtml(r[k]) : ''}</td>`).join('')}
                            </tr>`).join('')}
                    </tbody>
                </table></div>

                ${warningCount ? `<label style="display:flex;gap:8px;align-items:center;margin-top:12px;font-size:12px"><input type="checkbox" ${warningsAccepted ? 'checked' : ''} onchange="window.confirmImportWarnings(this.checked)"> Saya telah memeriksa peringatan dan tetap ingin mengimpor baris peringatan.</label>` : ''}
                <div class="bar" style="margin-top:16px;padding-top:14px;border-top:1px solid #e2e8f0">
                    <button type="button" class="btn o" onclick="resetImportPreview()">Batal / Ganti File</button>
                    <span class="sp"></span>
                    ${(readyCount + warningCount) <= 0 || I.mappingIssues.length ?
                        `<span class="bd e">Tidak ada baris valid untuk diimpor</span>` :
                        `<button type="button" id="btn-do-import" class="btn a" style="font-size:12px;padding:6px 12px" ${warningCount && !warningsAccepted ? 'disabled' : ''} onclick="executeBatchImport()">
                            ⬇ Impor ${readyCount + warningCount} Baris ${tLabel[t] || t}
                        </button>`
                    }
                </div>
            </div>`;

        container.innerHTML = detHtml + warnHtml + prevHtml;
    }

    // ─── Ubah Pemetaan Kolom Interaktif (C.cmap) ────────────────────────────────
    window.changeColMap = function(sheetIdx, colIdx, newMapping) {
        const I = S.imp;
        if (!I.raws || !I.raws[sheetIdx]) return;
        const sheet = I.raws[sheetIdx];
        sheet.map[colIdx] = newMapping;
        sheet.correctedMap ||= {};
        if (newMapping === (sheet.initialMap?.[colIdx] || '')) {
            delete sheet.correctedMap[colIdx];
        } else {
            sheet.correctedMap[colIdx] = newMapping;
        }
        I.rows = buildRows(I);
        I.warningsConfirmed = false;
        if (I.rows.some(q => q.kelas)) I.mode = 'semua';
        renderPreviewUI();
    };

    window.changeStudentColumnMap = function(columnIndex, field) {
        const I = S.imp;
        if (!studentWorkbook || !I.detection) return;
        I.studentColumnOverrides[columnIndex] = field;
        if (field === (I.studentOriginalMap[columnIndex] || '')) {
            delete I.studentColumnCorrections[columnIndex];
        } else {
            I.studentColumnCorrections[columnIndex] = field;
        }

        const result = realRows(studentWorkbook, I.studentColumnOverrides);
        I.detection = result.detection;
        I.rows = result.rows;
        I.mappingIssues = mappingIssues(I);
        I.warningsConfirmed = false;
        renderPreviewUI();
    };

    function mappingCorrections(I) {
        if (I.t === 'siswa') {
            if (!Object.keys(I.studentColumnCorrections || {}).length || !I.detection?.headers?.length) return [];
            return [{
                name: I.detection.sheet,
                header_row: I.detection.headerRow,
                headers: I.detection.headers,
                mapping: {...(I.detection.mapping || {}), ...(I.studentColumnOverrides || {})},
                corrected: I.studentColumnCorrections
            }];
        }

        return (I.raws || []).filter(sheet => Object.keys(sheet.correctedMap || {}).length).map(sheet => ({
            name: sheet.name,
            header_row: sheet.hr + 1,
            headers: sheet.heads || [],
            mapping: sheet.map || {},
            corrected: sheet.correctedMap
        }));
    }

    window.editImportCell = function(cell) {
        const I = S.imp;
        const rowIndex = Number(cell?.dataset?.row);
        const field = cell?.dataset?.field;
        if (!Number.isInteger(rowIndex) || !field || !I.rows?.[rowIndex]) return;

        const row = I.rows[rowIndex];
        let value = cl(cell.textContent);
        if (value === null) value = '';
        if (['sakit', 'izin', 'alpa', ...SC, ...(I.newM || []).map(mapel => mapel.kode), 'nilai', 'nilaicol'].includes(field)
            && value !== '') {
            value = value.replace(',', '.');
        }
        if (field === 'nisn' || field === 'nis' || field === 'nik' || field === 'hp') {
            value = numStr(value).replace(/\D/g, '');
            if (field === 'nisn') value = normN(value);
        } else if (field === 'kelas') {
            value = normK(value);
        } else if (field === 'sem') {
            value = normSem(value);
        } else if (field === 'tahun_ajaran') {
            value = normAcademicYear(value) || value;
        } else if (['tgl', 'tgl_masuk', 'tgl_ijazah_smp', 'tgl_keluar', 'tgl_ijazah_sma'].includes(field)) {
            value = toIso(value);
        }

        row[field] = value === '' ? null : value;
        I.warningsConfirmed = false;
        renderPreviewUI();
    };

    window.confirmImportWarnings = function(confirmed) {
        S.imp.warningsConfirmed = Boolean(confirmed);
        renderPreviewUI();
    };

    // ─── Download Laporan Validasi CSV ──────────────────────────────────────────
    window.downloadValidationReport = function() {
        const I = S.imp;
        const rows = (I.errors || []).map(e => ['Error', e.no, e.id, e.msg]);
        const csv = [['Jenis', 'Baris', 'Identitas', 'Pesan'], ...rows]
            .map(r => r.map(c => '"' + String(c).replace(/"/g, '""') + '"').join(',')).join('\n');
        const blob = new Blob(['\uFEFF' + csv], {type: 'text/csv;charset=utf-8'});
        const a = document.createElement('a');
        a.href = URL.createObjectURL(blob);
        a.download = 'laporan-validasi-import.csv';
        a.click();
    };

    // ─── Entry Point: Baca File Excel (smartFile) ───────────────────────────────
    window.smartFile = function(e, expectedType) {
        const fileInput = e.target || e;
        const f = fileInput.files ? fileInput.files[0] : null;
        if (!f) return;
        if (!window.XLSX) {
            alert('Pustaka SheetJS belum dimuat. Periksa koneksi internet atau muat ulang halaman.');
            return;
        }

        readFormOptions();

        const r = new FileReader();
        r.onload = async function(x) {
            let w;
            try {
                w = XLSX.read(x.target.result, {type: 'array'});
            } catch(err) {
                alert('File tidak bisa dibaca sebagai format Excel atau CSV.');
                return;
            }

            const I = S.imp;
            // KUNCI TIPE: Tipe selalu sesuai dengan halaman import yang sedang dibuka
            I.t = expectedType || 'siswa';
            I.detection = null;
            I.fileName = f.name || '';
            I.studentColumnOverrides = {};
            I.studentColumnCorrections = {};
            I.studentOriginalMap = {};
            I.dictionaries = {columns: {}, subjects: {}};
            I.savedFormats = [];
            I.warningsConfirmed = false;
            I.newM = [];
            I.newX = [];

            try {
                const metadata = await postImportJson(importEndpoint('metadata'), {type: I.t});
                I.dictionaries = metadata.dictionaries || {columns: {}, subjects: {}};
            } catch (error) {
                alert('Kamus import tidak dapat dimuat: ' + error.message);
                return;
            }

            if (I.t === 'siswa') {
                studentWorkbook = w;
                let result = realRows(w, I.studentColumnOverrides);
                if (result.detection?.headerDetected) {
                    try {
                        const formats = await postImportJson(importEndpoint('metadata'), {
                            type: I.t,
                            sheets: [{name: result.detection.sheet, headers: result.detection.headers}]
                        });
                        const format = savedFormatFor(formats.formats, 0);
                        I.studentOriginalMap = {...(result.detection.mapping || {})};
                        if (applyFormatMapping({map: I.studentColumnOverrides}, format)) {
                            I.savedFormats = [format];
                            result = realRows(w, I.studentColumnOverrides);
                        }
                    } catch (error) {
                        alert('Format import tidak dapat diperiksa: ' + error.message);
                        return;
                    }
                }
                I.detection = result.detection;
                I.raws = null;
                I.rows = result.rows;
                I.mappingIssues = mappingIssues(I);
                syncDetectedAcademicYear(I.rows);
                renderPreviewUI();
                if (window.toast && result.detection) window.toast(I.rows.length + ' siswa terbaca dari sheet ' + result.detection.sheet);
                return;
            }

            // 2. Analisis Fleksibel — semua sheet (skip Kontrol Induk untuk siswa)
            const rd = n => readRows(w.Sheets[n]);
<<<<<<< HEAD
            const sheetNames = w.SheetNames.filter(name => !/^(?:kontrol\s*induk|petunjuk)$/i.test(String(name).trim()));
=======
            const sheetNames = I.t === 'siswa'
                ? w.SheetNames.filter(name => !/kontrol\s*induk/i.test(name))
                : w.SheetNames;
>>>>>>> 497b07e27e50c1c03a318e4e1e5f3c83921f699c
            const allAnalyzed = sheetNames.map(n => analyze(n, rd(n), I.t)).filter(Boolean);

            let use = allAnalyzed;
            if (!use.length) {
                const n = w.SheetNames[0];
                const aoa = rd(n);
                const hr = Math.max(0, aoa.findIndex(q => (q || []).filter(c => String(c).trim() !== '').length >= 2));
                const hd = (aoa[hr] || []).map(h => String(h).trim());
                use = [{
                    name: n,
                    aoa: aoa,
                    hr: hr,
                    heads: hd,
                    map: {},
                    semOf: {},
                    base: Object.fromEntries(hd.map((h, i) => [i, h])),
                    t: I.t,
                    ts: [I.t],
                    kelas: ''
                }];
            }

            try {
                const formats = await postImportJson(importEndpoint('metadata'), {
                    type: I.t,
                    sheets: use.map(sheet => ({name: sheet.name, headers: sheet.heads || []}))
                });
                I.savedFormats = (formats.formats || []).filter(format => format?.format_id);
                use.forEach((sheet, index) => applyFormatMapping(sheet, savedFormatFor(formats.formats, index)));
            } catch (error) {
                alert('Format import tidak dapat diperiksa: ' + error.message);
                return;
            }
            use.forEach(sheet => assignX(sheet, I.t));
            use.forEach(sheet => {
                sheet.initialMap = {...sheet.map};
                sheet.correctedMap = {};
            });
            I.raws = use;
            const detectedColumns = use.flatMap(sheet => (sheet.heads || []).map((header, index) => ({
                sheet: sheet.name,
                index,
                header: String(header ?? ''),
                field: sheet.map?.[index] || '',
                status: sheet.map?.[index] ? 'recognized' : (header ? 'unknown' : '')
            })).filter(column => column.header !== ''));
            I.detection = {
                sheetCount: w.SheetNames.length,
                sheets: use.map(sheet => ({name: sheet.name, headerRow: sheet.hr + 1})),
                columns: detectedColumns,
                recognized: detectedColumns.filter(column => column.status === 'recognized'),
                uncertain: [],
                unknown: detectedColumns.filter(column => column.status === 'unknown')
            };
            I.rows = buildRows(I);
            I.mappingIssues = mappingIssues(I);
            syncDetectedAcademicYear(I.rows);
            if (I.mode !== 'kelas' && I.rows.some(q => q.kelas)) I.mode = 'semua';

            renderPreviewUI();
            if (window.toast) window.toast('Terdeteksi ' + I.rows.length + ' baris dari ' + w.SheetNames.length + ' sheet');
        };
        r.readAsArrayBuffer(f);
    };

    window.resetImportPreview = function() {
        S.imp.rows = null;
        S.imp.raws = null;
        S.imp.detection = null;
        S.imp.studentColumnOverrides = {};
        S.imp.studentColumnCorrections = {};
        S.imp.studentOriginalMap = {};
        S.imp.dictionaries = {columns: {}, subjects: {}};
        S.imp.savedFormats = [];
        S.imp.mappingIssues = [];
        S.imp.statuses = [];
        S.imp.warningsConfirmed = false;
        S.imp.fileName = '';
        S.imp.errors = [];
        S.imp.warns = [];
        studentWorkbook = null;
        const c = document.getElementById('josbook-import-preview-area');
        if (c) c.innerHTML = '';
        document.querySelectorAll('input[type="file"]').forEach(i => { i.value = ''; });
    };

    // ─── Eksekusi Batch Import ke Server ────────────────────────────────────────
    window.executeBatchImport = async function() {
        const I = S.imp;
        readFormOptions();

        if (I.mappingIssues?.length) {
            alert('Perbaiki pemetaan kolom sebelum mengimpor: ' + I.mappingIssues.join(' '));
            return;
        }
        if (I.warns?.length && !I.warningsConfirmed) {
            alert('Konfirmasikan peringatan sebelum mengimpor.');
            return;
        }

        const invalidRows = new Set((I.statuses || [])
            .map((status, index) => status.status === 'error' ? index : -1)
            .filter(index => index >= 0));
        const rowsToSend = (I.rows || []).filter((row, index) => !invalidRows.has(index));
        if (!rowsToSend.length) {
            alert('Tidak ada baris yang valid untuk diimpor.');
            return;
        }

        const btn = document.getElementById('btn-do-import');
        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Menyimpan ke database...';
        }

        const endpointMeta = document.querySelector('meta[name="batch-import-url"]');
        const endpoint = endpointMeta ? endpointMeta.getAttribute('content') : '';
        const appBaseMeta = document.querySelector('meta[name="app-base-url"]');
        const appBaseUrl = appBaseMeta ? appBaseMeta.getAttribute('content').replace(/\/+$/, '') : '';
        const importRolePath = endpoint.includes('/guru/') ? 'guru' : 'admin';
        const importPage = {
            nilai: importRolePath === 'guru' ? 'import-nilai' : 'import/nilai',
            hadir: importRolePath === 'guru' ? 'import-ketidakhadiran' : 'import/kehadiran',
            ekskul: importRolePath === 'guru' ? 'import-ekskul' : 'import/ekskul'
        };

        if (!endpoint) {
            alert('Alamat layanan import tidak tersedia. Muat ulang halaman lalu coba lagi.');
            if (btn) {
                btn.disabled = false;
                btn.textContent = '⬇ Coba Impor Lagi';
            }
            return;
        }

        const payload = {
            type: I.t,
            file_name: I.fileName || 'import.xlsx',
            total_rows: rowsToSend.length,
            tahun_ajaran: I.ta || '',
            semester: I.sem || 'Genap',
            mode: I.mode || 'semua',
            kelas: I.kelas || '',
            dup: I.dup || 'skip',
            format_id: I.savedFormats?.find(format => format?.format_id)?.format_id || null,
            mapping_corrections: mappingCorrections(I),
            new_classes: [...new Set(rowsToSend.map(r => normK(r.kelas || '')).filter(Boolean))],
            new_mapel: (I.newM || []).filter(mapel => rowsToSend.some(row => row[mapel.kode] !== '' && row[mapel.kode] != null))
        };

        const targetKelas = I.mode === 'kelas' ? I.kelas : (rowsToSend[0] ? normK(rowsToSend[0].kelas) : '');
        let batchId = null;
        try {
            const started = await postImportJson(importEndpoint('start-batch'), payload);
            batchId = started.batch_id;
            const chunkSize = 200;
            const chunkCount = Math.ceil(rowsToSend.length / chunkSize);
            const errors = [];
            let count = 0;
            let skipped = 0;
            let lastResponse = null;

            for (let chunkIndex = 0; chunkIndex < chunkCount; chunkIndex++) {
                const start = chunkIndex * chunkSize;
                const chunk = rowsToSend.slice(start, start + chunkSize);
                if (btn) {
                    btn.textContent = `⏳ Potongan ${chunkIndex + 1}/${chunkCount} · ${Math.min(start + chunk.length, rowsToSend.length)}/${rowsToSend.length} baris`;
                }
                lastResponse = await postImportJson(endpoint, {
                    ...payload,
                    batch_id: batchId,
                    rows: chunk,
                    final_chunk: chunkIndex === chunkCount - 1
                });
                count += Number(lastResponse.count) || 0;
                skipped += Number(lastResponse.skipped) || 0;
                errors.push(...(Array.isArray(lastResponse.errors) ? lastResponse.errors : []));
            }

            const data = {
                ...lastResponse,
                success: true,
                count,
                skipped,
                errors,
                message: `Berhasil mengimpor ${count} data ${I.t}${skipped ? ` (${skipped} baris gagal/dilewati)` : ''}.`
            };
            const container = document.getElementById('josbook-import-preview-area');
            if (container) {
                const escapedErrors = errors.map(error => {
                    const reason = escapeHtml(error.reason || 'Baris dilewati');
                    return `<li>Baris ${Number(error.row) || '-'}: ${reason}</li>`;
                }).join('');
                const errorSummary = errors.length
                    ? `<details style="margin:0 0 14px"><summary style="cursor:pointer">Lihat ${errors.length} baris gagal/dilewati</summary><ul>${escapedErrors}</ul></details>`
                    : '';
                container.innerHTML = `
                    <div class="card" style="margin-top:14px;border-left:4px solid #27ae60;background:#f6fbf8;padding:20px">
                        <h3 style="color:#27ae60;margin:0 0 8px;font-size:16px">✅ ${escapeHtml(data.message)}</h3>
                        ${data.class_warning ? `<p class="sub" style="margin:0 0 14px;color:#e67e22">${escapeHtml(data.class_warning)}</p>` : ''}
                        ${errorSummary}
                        <div class="bar" style="margin-top:14px;gap:10px;flex-wrap:wrap">
                            ${I.t === 'siswa' ? `
                                <a href="${appBaseUrl}/admin/data-siswa" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">👉 Buka Halaman Data Siswa</a>
                                ${targetKelas ? `<a href="${appBaseUrl}/admin/data-siswa?kelas=${encodeURIComponent(targetKelas)}" class="btn" style="padding:6px 10px;font-size:12px;text-decoration:none">Lihat Kelas ${escapeHtml(targetKelas)}</a>` : ''}
                                <a href="${appBaseUrl}/admin/alumni?aj=${encodeURIComponent('Alumni (Lulus)')}" class="btn o" style="padding:6px 10px;font-size:12px;text-decoration:none">Lihat Alumni &amp; Kontrol Induk</a>
                                <a href="${appBaseUrl}/admin/pelacakan-alumni" class="btn o" style="padding:6px 10px;font-size:12px;text-decoration:none">Periksa Pelacakan Alumni</a>
                            ` : I.t === 'nilai' ? `
                                <a href="${appBaseUrl}/${importRolePath}/${importPage.nilai}" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">🔄 Selesai &amp; Muat Ulang Data Nilai</a>
                            ` : I.t === 'hadir' ? `
                                <a href="${appBaseUrl}/${importRolePath}/${importPage.hadir}" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">🔄 Selesai &amp; Muat Ulang Data Kehadiran</a>
                            ` : `
                                <a href="${appBaseUrl}/${importRolePath}/${importPage.ekskul}" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">🔄 Selesai &amp; Muat Ulang Data Ekskul</a>
                            `}
                            <button type="button" class="btn o" onclick="resetImportPreview()">⬆ Import File Lain</button>
                        </div>
                    </div>`;
            }
            if (window.toast) window.toast(data.message, 4000);
        } catch (error) {
            let message = error.message;
            if (batchId !== null) {
                try {
                    await postImportJson(importEndpoint('fail-batch'), {
                        batch_id: batchId,
                        message: message.slice(0, 500)
                    });
                } catch (failureError) {
                    message += ` Gagal mencatat status batch: ${failureError.message}`;
                }
            }
            alert('Impor gagal: ' + message);
            if (btn) {
                btn.disabled = false;
                btn.textContent = '⬇ Coba Impor Lagi';
            }
        }
    };

})(window);
