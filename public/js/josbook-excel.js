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

    const iso = v => {
        if (typeof v === 'number' && v > 20000 && v < 80000) {
            return new Date(Math.round((v - 25569) * 864e5)).toISOString().slice(0, 10);
        }
        return v;
    };

    const cl = v => (typeof v === 'string' ? v.trim() : v);
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

    const normSem = v => (/genap|2/i.test(String(v)) ? 'Genap' : /ganjil|1/i.test(String(v)) ? 'Ganjil' : '');

    const normN = v => {
        v = String(v ?? '').replace(/[^0-9]/g, '').trim();
        if (v.length >= 6 && v.length <= 9) return v.padStart(10, '0');
        return v;
    };

    const numStr = v => (typeof v === 'number' ? v.toLocaleString('fullwide', {useGrouping:false}) : String(v ?? '').trim());

    const toIso = v => {
        if (typeof v === 'number' && v > 20000 && v < 80000) return iso(v);
        const t = String(v ?? '').trim();
        let m = /^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/.exec(t);
        if (m) return m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0');
        m = /^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/.exec(t);
        const bi = m ? BLN.findIndex(b => b.toLowerCase() === m[2].toLowerCase()) : -1;
        return bi >= 0 ? m[3] + '-' + String(bi + 1).padStart(2, '0') + '-' + m[1].padStart(2, '0') : t;
    };

    const nz = s => String(s ?? '').toLowerCase().replace(/\([^)]*\)/g, ' ').replace(/[^a-z0-9]+/g, ' ').trim();

    // ─── Kamus Alias Header (Mendukung Format Apapun) ─────────────────────────
    const ALX = {
        nisn: 'nisn|nomor induk siswa nasional',
        nis: 'nis|nomor induk|no induk|nomor induk siswa|no induk siswa|nipd',
        nama: 'nama|nama lengkap|nama siswa|nama peserta didik|nama murid|peserta didik|siswa',
        jk: 'jk|l p|jenis kelamin|gender|kelamin',
        tmp: 'tempat lahir|tmp lahir|kota lahir',
        tgl: 'tanggal lahir|tgl lahir|tgl lhr',
        kelas: 'kelas|kelas saat ini|kelas sekarang|rombel|rombongan belajar|nama rombel|kls',
        tahun_ajaran: 'tahun ajaran|tahunajaran|tahun pelajaran|academic year|ta',
        agama: 'agama',
        kewarganegaraan: 'kewarganegaraan|warga negara|wn',
        nik: 'nik|no nik|nomor induk kependudukan',
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
        status: 'status|status kepegawaian'
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

    const fieldOf = h => AMAP()[nz(h)] || '';
    const mapelOf = h => {
        const x = nz(h);
        if (!x) return '';
        const r = SUB.find(m => nz(m[1]) === x || m[0] === x);
        if (r) return r[0];
        for (const [c,a] of Object.entries(MEX)) {
            if (a.split('|').includes(x)) return c;
        }
        return '';
    };

    // ─── Pembaca biodata dari sheet PesertaDidik & Kontrol Induk ──────────────
    function realRows(w) {
        const pKey = w.SheetNames.find(s => /peserta\s*didik/i.test(s)) || 'PesertaDidik';
        const kKey = w.SheetNames.find(s => /kontrol\s*induk/i.test(s)) || 'Kontrol Induk';

        const rd = n => (n && w.Sheets[n]) ? XLSX.utils.sheet_to_json(w.Sheets[n], {header: 1, defval: ''}) : [];
        const km = {};
        if (w.Sheets[kKey]) {
            rd(kKey).slice(5).forEach(r => {
                if (r[2] !== undefined && r[2] !== '') km[String(r[2]).trim()] = r;
            });
        }

        const out = [];
        const rows = rd(pKey);
        if (!rows.length) return out;

        const headers = rows[4] || rows[3] || [];
        const headerFields = headers.map(fieldOf);
        const headerCounts = headerFields.reduce((counts, field) => {
            if (field) counts[field] = (counts[field] || 0) + 1;
            return counts;
        }, {});
        const headerIndex = (field, fallback) => headerCounts[field] === 1
            ? headerFields.indexOf(field)
            : fallback;
        const yearIndex = headerIndex('tahun_ajaran', -1);
        const classIndex = headerIndex('kelas', -1);
        const nisIndex = headerIndex('nis', CI('C'));
        const nisnIndex = headerIndex('nisn', CI('F'));
        const nameIndex = headerIndex('nama', CI('D'));
        const groupedFields = new Set([
            'agama_ayah','agama_ibu','agama_wali',
            'hp_ayah','hp_ibu','hp_wali',
            'kerja_ayah','kerja_ibu','kerja_wali'
        ]);

        rows.slice(5).forEach((r, offset) => {
            if (!cl(r[nameIndex]) || (r[nisIndex] === '' && r[nisnIndex] === '')) return;
            const o = {};
            let numericNik = false;
            for (const [c, k] of Object.entries(RMAP)) {
                let columnIndex = CI(c);
                const positionalHeaderField = headerFields[columnIndex];
                if (positionalHeaderField !== k && positionalHeaderField && !groupedFields.has(k)) {
                    if (headerCounts[k] === 1) {
                        columnIndex = headerFields.indexOf(k);
                    }
                }
                let v = cl(r[columnIndex]);
                if ((k === 'tgl' || k === 'tgl_masuk' || k === 'tgl_ijazah_smp') && typeof v === 'number') v = iso(v);
                if (k === 'nik' && typeof v === 'number' && String(v).replace(/\D/g, '').length === 16) {
                    numericNik = true;
                }
                if (['nis', 'nisn', 'nik'].includes(k) && typeof v === 'number') v = numStr(v);
                o[k] = (v === '' || v == null) ? '-' : v;
            }
            o.nis = String(o.nis ?? '');
            o.nisn = normN(String(r[nisnIndex] ?? o.nisn ?? '').trim());
            const classFromSheet = classIndex >= 0 ? normK(r[classIndex]) : '';
            const classFromAdmission = normK((o.kelas_masuk || '') + '-' + (o.rombel_masuk || ''));
            o.kelas = classFromSheet || classFromAdmission;
            const rawAcademicYear = yearIndex >= 0 ? String(r[yearIndex] ?? '').trim() : '';
            o.tahun_ajaran = rawAcademicYear
                ? (normAcademicYear(rawAcademicYear) || rawAcademicYear)
                : yearBeforeHeader(rows, 4, pKey);
            if (numericNik) {
                o.__numeric_nik = true;
            }
            o.jk = /^p/i.test(String(o.jk)) ? 'P' : (/^l/i.test(String(o.jk)) ? 'L' : '');

            // Gabungkan data dari Kontrol Induk berdasarkan NIS
            const q = km[o.nis];
            if (q) {
                for (const [c, k] of Object.entries(KMAP)) {
                    let v = cl(q[CI(c)]);
                    if (typeof v === 'number' && k.startsWith('tgl')) v = iso(v);
                    o[k] = (v === '' || v == null) ? '-' : v;
                }
            }
            o.status = (o.alasan_keluar === 'Lulus') ? 'Lulus' : 'Aktif';
            if (o.tgl_keluar && o.tgl_keluar !== '-') o.thn_lulus = +String(o.tgl_keluar).slice(0, 4);
            o.__excel_row = offset + 6;
            out.push(o);
        });
        return out;
    }

    function hsplit(h) {
        const t = String(h).trim();
        const m = /\b(?:semester|smt|sem)\s*\.?\s*([12])\b|\b(ganjil|genap)\b/i.exec(t);
        if (!m) return {b:t, s:''};
        const b = t.replace(m[0],' ').replace(/[\s\-_:()]+$/,'').replace(/^[\s\-_:()]+/,'').trim();
        return {b:b||t, s:m[2]?(/genap/i.test(m[2])?'Genap':'Ganjil'):(m[1]==='2'?'Genap':'Ganjil')};
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
            let sc = 0, idf = 0;
            (aoa[r] || []).forEach(h => {
                const t = String(h).trim();
                if (!t || /^\d+$/.test(t)) return;
                const b = hsplit(t).b, f = fx(b) || fx(t);
                if (f || mapelOf(b)) {
                    sc++;
                    if (['nama', 'nisn', 'nis', 'kelas', 'nip', 'nuptk', 'ekskul', 'sakit'].includes(f)) idf++;
                }
            });
            if ((idf >= 1 || sc >= 2) && (!best || sc > best.sc)) best = {r, sc};
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
            let f = fx(q.b), m = f ? '' : mapelOf(q.b);
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

        rows.forEach((r, idx) => {
            const no = idx + 1;
            const id = r.nama || (r.nisn ? 'NISN: ' + r.nisn : 'Baris ' + (idx + 2));

            if (t === 'siswa' || !t) {
                if (!r.nama && !r.nisn) {
                    errors.push({no, id: 'Baris ' + (idx + 2), msg: 'Nama dan NISN kosong'});
                    return;
                }
                if (!r.nisn) {
                    errors.push({no, id, msg: 'NISN kosong — baris ini akan dilewati'});
                } else if (!/^\d{10}$/.test(r.nisn)) {
                    errors.push({no, id, msg: 'NISN tidak tepat 10 digit: "' + r.nisn + '"'});
                } else if (seenNisn.has(r.nisn)) {
                    warns.push({no, id, msg: 'NISN ganda dalam file: ' + r.nisn});
                } else {
                    seenNisn.add(r.nisn);
                }
                if (!r.nama) {
                    warns.push({no, id, msg: 'Nama kosong'});
                }
            } else if (t === 'nilai' || t === 'hadir' || t === 'ekskul') {
                if (!r.nisn && !r.nama) {
                    errors.push({no, id: 'Baris ' + (idx + 2), msg: 'Tidak ada NISN atau Nama untuk identifikasi'});
                }
                if (r.nisn && !/^\d{10}$/.test(r.nisn)) {
                    warns.push({no, id, msg: 'NISN tidak tepat 10 digit: "' + r.nisn + '"'});
                }
            } else if (t === 'guru') {
                if (!r.nama) {
                    errors.push({no, id: 'Baris ' + (idx + 2), msg: 'Nama guru kosong'});
                }
            }
        });
        return {errors, warns};
    }

    // ─── State Import ───────────────────────────────────────────────────────────
    const S = {
        imp: {
            t: 'siswa',
            mode: 'semua',
            kelas: 'X-1',
            sem: 'Genap',
            dup: 'skip',
            ta: '2024/2025',
            raws: null,
            rows: null,
            newM: [],
            errors: [],
            warns: []
        }
    };
    window.JosBookState = S;

    function readFormOptions() {
        const I = S.imp;
        const taEl = document.querySelector('input[name="tahun_ajaran"]') || document.getElementById('tahun_ajaran');
        I.ta = taEl ? taEl.value.trim() : '';

        const semEl = document.querySelector('select[name="semester"]') || document.getElementById('semester');
        if (semEl && semEl.value) I.sem = semEl.value;

        const dupEl = document.querySelector('select[name="duplikat"]') || document.getElementById('duplikat');
        if (dupEl && dupEl.value) I.dup = dupEl.value;

        const modeEl = document.querySelector('select[name="mode"]') || document.getElementById('mode');
        if (modeEl && modeEl.value) I.mode = (modeEl.value === 'kelas') ? 'kelas' : 'semua';

        const kelasEl = document.querySelector('input[name="kelas"]') || document.getElementById('kelas');
        if (kelasEl && kelasEl.value) I.kelas = normK(kelasEl.value) || I.kelas;
    }

    function syncDetectedAcademicYear(rows) {
        const years = [...new Set((rows || []).map(row => normAcademicYear(row.tahun_ajaran || '')).filter(Boolean))];
        if (years.length !== 1) return;

        const I = S.imp;
        I.ta = years[0];
        const taEl = document.querySelector('input[name="tahun_ajaran"]') || document.getElementById('tahun_ajaran');
        if (taEl) taEl.value = years[0];
    }

    // ─── Render Panel Pratinjau & Pemetaan Kolom ────────────────────────────────
    function renderPreviewUI() {
        const I = S.imp;
        const container = document.getElementById('josbook-import-preview-area');
        if (!container) return;

        if (!I.rows || !I.rows.length) {
            container.innerHTML = `
                <div class="card" style="margin-top:14px">
                    <div class="note" style="background:#fbe5e1;border-color:#efc6bf;color:#8b2a1e">
                        ⚠️ File berhasil dibaca namun tidak ada baris data yang cocok. Periksa baris judul atau gunakan panel pemetaan kolom di bawah.
                    </div>
                </div>`;
            return;
        }

        const t = I.t;
        const tLabel = {
            siswa: 'Data Siswa & Induk',
            nilai: 'Data Nilai Rapor',
            hadir: 'Ketidakhadiran & Kokurikuler',
            ekskul: 'Ekstrakurikuler',
            guru: 'Data Guru'
        };

        const totalRows = I.rows.length;
        const uniqueClasses = [...new Set(I.rows.map(r => normK(r.kelas || '')).filter(Boolean))];
        const uniqueAcademicYears = [...new Set(I.rows.map(r => normAcademicYear(r.tahun_ajaran || '')).filter(Boolean))];
        const {errors, warns} = validateRows(I.rows, t);
        I.errors = errors;
        I.warns = warns;

        // Breakdown per kelas
        const classBreakdown = {};
        I.rows.forEach(r => {
            const k = I.mode === 'semua' ? (normK(r.kelas) || '(Belum Ada Kelas)') : I.kelas;
            classBreakdown[k] = (classBreakdown[k] || 0) + 1;
        });

        // 1. Deteksi Otomatis & Pemetaan Kolom (detBox Sesuai Prototype)
        let detHtml = '';
        if (!I.raws) {
            // Khusus format database sekolah (PesertaDidik + Kontrol Induk): semua kolom sudah terpetakan penuh
            detHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h3 style="margin:0">2. Hasil Deteksi Otomatis</h3>
                    <span class="bd ok" style="font-size:12px;padding:4px 10px">Terdeteksi: Data Siswa (Peserta Didik + Kontrol Induk)</span>
                </div>
                <div class="bar" style="flex-wrap:wrap;gap:6px">
                    <span class="bd ok">Sheet “Peserta Didik” &amp; “Kontrol Induk” terbaca 100% lengkap</span>
                    <span class="bd ok">Total: <b>${I.rows.length} siswa</b></span>
                    ${uniqueClasses.length ? `<span class="bd ok">Kelas: <b>${uniqueClasses.join(', ')}</b></span>` : ''}
                    ${uniqueAcademicYears.length ? `<span class="bd ok">Tahun ajaran: <b>${uniqueAcademicYears.join(', ')}</b></span>` : ''}
                </div>
            </div>`;
        } else {
            const opts = FOPT(t);
            const ignoredCols = (I.raws || []).reduce((acc, z) => acc + (z.heads || []).filter((h, i) => h && !z.map[i]).length, 0);

            detHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h3 style="margin:0">2. Hasil Deteksi Otomatis</h3>
                    <span class="bd ok" style="font-size:12px;padding:4px 10px">Terdeteksi: ${tLabel[t] || t}</span>
                </div>
                <div class="bar" style="flex-wrap:wrap;gap:6px">
                    ${(I.raws||[]).map(z => `<span class="bd">Sheet “<b>${z.name}</b>” · baris ${z.hr+1}${z.kelas ? ' · ' + z.kelas : ''}</span>`).join('')}
                    ${uniqueClasses.length ? `<span class="bd ok">Kelas: <b>${uniqueClasses.join(', ')}</b></span>` : ''}
                    ${uniqueAcademicYears.length ? `<span class="bd ok">Tahun ajaran: <b>${uniqueAcademicYears.join(', ')}</b></span>` : ''}
                    ${(I.newM||[]).length ? `<span class="bd w">⚠️ Mapel Baru (${I.newM.length}): <b>${I.newM.map(m=>m.nama).join(', ')}</b></span>` : ''}
                    ${(I.newX||[]).length ? `<span class="bd ok">Kolom Baru (${I.newX.length}): <b>${I.newX.map(x=>x.l).join(', ')}</b></span>` : ''}
                    ${ignoredCols > 0 ? `<span class="bd w">${ignoredCols} kolom diabaikan</span>` : ''}
                </div>

                <details ${ignoredCols > 0 ? 'open' : ''} style="margin-top:12px;padding-top:12px;border-top:1px solid #e2e8f0">
                    <summary style="cursor:pointer;font-weight:600;font-size:13px;color:var(--pr, #2563eb)">
                        🔍 Lihat / Ubah Pemetaan Kolom Excel
                    </summary>
                    <p class="sub" style="margin:6px 0 10px;font-size:12px">
                        Jika ada kolom yang belum cocok, pilih padanan kolom di bawah. Pratinjau akan langsung diperbarui otomatis.
                    </p>
                    ${(I.raws||[]).map((z, si) => `
                        <div style="margin-bottom:12px">
                            <strong style="font-size:12px;color:#475569">Sheet “${z.name}”</strong>
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
                                                    <td><strong>${h}</strong></td>
                                                    <td style="color:#64748b;font-size:12px">${sampleVal.slice(0, 35)}</td>
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
                    ${errors.map(e => `<div class="bar" style="font-size:12px;padding:4px 0;border-bottom:1px solid #f5e0de"><span class="bd e" style="min-width:50px">Error</span> <b>Baris ${e.no}</b> · ${e.id} — ${e.msg}</div>`).join('')}
                    ${warns.map(w => `<div class="bar" style="font-size:12px;padding:4px 0;border-bottom:1px solid #fdf3e1"><span class="bd w" style="min-width:70px">Peringatan</span> <b>Baris ${w.no}</b> · ${w.id} — ${w.msg}</div>`).join('')}
                </div>
                <div class="bar" style="margin-top:10px">
                    <button type="button" class="btn" style="font-size:12px;padding:5px 10px" onclick="downloadValidationReport()">⬇ Unduh Laporan CSV</button>
                </div>
            </div>`;
        }

        // 3. Pratinjau Tabel & Tombol Eksekusi
        const sampleRow = I.rows[0] || {};
        const allKeys = Object.keys(sampleRow);
        const priorityKeys = ['nisn','nis','nama','kelas','tahun_ajaran','jk','tgl','ayah','ibu','sakit','izin','alpa','ekskul','predikat'];
        const previewKeys = priorityKeys.filter(k => allKeys.includes(k))
            .concat(allKeys.filter(k => !priorityKeys.includes(k)))
            .slice(0, 8);
        const previewRows = I.rows.slice(0, 5);

        let prevHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <h3 style="margin:0">3. Pratinjau · ${totalRows} Baris</h3>
                    <div style="display:flex;gap:8px">
                        <span class="bd ok">✔ ${totalRows - errors.length} Siap</span>
                        ${errors.length ? `<span class="bd e">❌ ${errors.length} Dilewati</span>` : ''}
                        ${warns.length ? `<span class="bd w">⚠️ ${warns.length} Peringatan</span>` : ''}
                    </div>
                </div>

                <div class="bar" style="flex-wrap:wrap;gap:6px;margin-bottom:12px">
                    ${Object.entries(classBreakdown).map(([k, n]) => `<span class="bd ${k === '(Belum Ada Kelas)' ? 'e' : 'ok'}">${k}: <b>${n}</b> siswa</span>`).join('')}
                </div>

                <div class="tw"><table>
                    <thead>
                        <tr>
                            <th>#</th>
                            ${previewKeys.map(k => `<th>${FL[k] || SN[k] || k.toUpperCase()}</th>`).join('')}
                        </tr>
                    </thead>
                    <tbody>
                        ${previewRows.map((r, i) => `
                            <tr>
                                <td>${i + 1}</td>
                                ${previewKeys.map(k => `<td>${r[k] !== undefined && r[k] !== null && r[k] !== '' ? String(r[k]) : '<span style="color:#a0aec0">-</span>'}</td>`).join('')}
                            </tr>`).join('')}
                    </tbody>
                </table></div>
                ${totalRows > 5 ? `<p class="sub" style="margin:10px 0 0;font-size:12px">... dan ${totalRows - 5} baris lainnya.</p>` : ''}

                <div class="bar" style="margin-top:16px;padding-top:14px;border-top:1px solid #e2e8f0">
                    <button type="button" class="btn o" onclick="resetImportPreview()">Batal / Ganti File</button>
                    <span class="sp"></span>
                    ${(totalRows - errors.length) <= 0 ?
                        `<span class="bd e">Tidak ada baris valid untuk diimpor</span>` :
                        `<button type="button" id="btn-do-import" class="btn a" style="font-size:12px;padding:6px 12px" onclick="executeBatchImport()">
                            ⬇ Impor ${totalRows - errors.length} Baris ${tLabel[t] || t} ke Database
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
        I.raws[sheetIdx].map[colIdx] = newMapping;
        I.rows = buildRows(I);
        if (I.rows.some(q => q.kelas)) I.mode = 'semua';
        renderPreviewUI();
    };

    // ─── Download Laporan Validasi CSV ──────────────────────────────────────────
    window.downloadValidationReport = function() {
        const I = S.imp;
        const rows = [
            ...(I.errors || []).map(e => ['Error', e.no, e.id, e.msg]),
            ...(I.warns || []).map(w => ['Peringatan', w.no, w.id, w.msg])
        ];
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
        r.onload = function(x) {
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

            // 1. Cek apakah ini file database sekolah dengan sheet PesertaDidik yang cocok
            const pSheetKey = w.SheetNames.find(s => /peserta\s*didik/i.test(s));
            if (pSheetKey && I.t === 'siswa') {
                const rows = realRows(w);
                if (rows && rows.length > 0) {
                    I.raws = null;
                    I.rows = rows;
                    I.mode = 'semua';
                    I.newM = [];
                    I.newX = [];
                    syncDetectedAcademicYear(I.rows);
                    renderPreviewUI();
                    if (window.toast) window.toast(I.rows.length + ' siswa terbaca dari ' + pSheetKey + (w.Sheets['Kontrol Induk'] ? ' + Kontrol Induk' : ''));
                    return;
                }
            }

            // 2. Analisis Fleksibel — semua sheet (skip Kontrol Induk untuk siswa)
            const rd = n => XLSX.utils.sheet_to_json(w.Sheets[n], {header: 1, defval: ''});
            const sheetNames = I.t === 'siswa'
                ? w.SheetNames.filter(name => !/kontrol\s*induk/i.test(name))
                : w.SheetNames;
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

            // Jalankan deteksi kolom otomatis untuk kolom tambahan/baru
            use.forEach(z => assignX(z, I.t));

            I.raws = use;
            I.rows = buildRows(I);
            syncDetectedAcademicYear(I.rows);
            if (I.rows.some(q => q.kelas)) I.mode = 'semua';

            renderPreviewUI();
            if (window.toast) window.toast('Terdeteksi ' + I.rows.length + ' baris dari ' + use.length + ' sheet');
        };
        r.readAsArrayBuffer(f);
    };

    window.resetImportPreview = function() {
        S.imp.rows = null;
        S.imp.raws = null;
        S.imp.errors = [];
        S.imp.warns = [];
        const c = document.getElementById('josbook-import-preview-area');
        if (c) c.innerHTML = '';
        document.querySelectorAll('input[type="file"]').forEach(i => { i.value = ''; });
    };

    // ─── Eksekusi Batch Import ke Server ────────────────────────────────────────
    window.executeBatchImport = function() {
        const I = S.imp;
        readFormOptions();

        let rowsToSend = I.rows || [];

        const btn = document.getElementById('btn-do-import');
        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Menyimpan ke database...';
        }

        const token = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = token ? token.getAttribute('content') : '';
        const endpointMeta = document.querySelector('meta[name="batch-import-url"]');
        const endpoint = endpointMeta ? endpointMeta.getAttribute('content') : '';
        const appBaseMeta = document.querySelector('meta[name="app-base-url"]');
        const appBaseUrl = appBaseMeta ? appBaseMeta.getAttribute('content').replace(/\/+$/, '') : '';

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
            tahun_ajaran: I.ta || '',
            semester: I.sem || 'Genap',
            mode: I.mode || 'semua',
            kelas: I.kelas || '',
            dup: I.dup || 'skip',
            rows: rowsToSend,
            new_classes: [...new Set(rowsToSend.map(r => normK(r.kelas || '')).filter(Boolean))],
            new_mapel: I.newM || []
        };

        const targetKelas = I.mode === 'kelas' ? I.kelas : (rowsToSend[0] ? normK(rowsToSend[0].kelas) : '');

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        })
        .then(async res => {
            const contentType = res.headers.get('content-type') || '';
            if (!contentType.includes('application/json')) {
                await res.text();
                throw new Error(`Server mengembalikan halaman, bukan JSON (HTTP ${res.status}). Muat ulang halaman dan pastikan sesi login masih aktif.`);
            }

            return res.json();
        })
        .then(data => {
            if (data.success) {
                const container = document.getElementById('josbook-import-preview-area');
                if (container) {
                    const rowErrors = Array.isArray(data.errors) ? data.errors : [];
                    const escapedErrors = rowErrors.map(function(error) {
                        const reason = String(error.reason || 'Baris dilewati')
                            .replace(/&/g, '&amp;')
                            .replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;')
                            .replace(/"/g, '&quot;')
                            .replace(/'/g, '&#039;');
                        return `<li>Baris ${Number(error.row) || '-'}: ${reason}</li>`;
                    }).join('');
                    const errorSummary = rowErrors.length
                        ? `<details style="margin:0 0 14px"><summary style="cursor:pointer">Lihat ${rowErrors.length} baris gagal/dilewati</summary><ul>${escapedErrors}</ul></details>`
                        : '';
                    container.innerHTML = `
                        <div class="card" style="margin-top:14px;border-left:4px solid #27ae60;background:#f6fbf8;padding:20px">
                            <h3 style="color:#27ae60;margin:0 0 8px;font-size:16px">✅ ${data.message}</h3>
                            ${data.class_warning ? `<p class="sub" style="margin:0 0 14px;color:#e67e22">${String(data.class_warning).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')}</p>` : ''}
                            ${errorSummary}
                            <div class="bar" style="margin-top:14px;gap:10px;flex-wrap:wrap">
                                ${I.t === 'siswa' ? `
                                    <a href="${appBaseUrl}/admin/data-siswa" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">👉 Buka Halaman Data Siswa</a>
                                    ${targetKelas ? `<a href="${appBaseUrl}/admin/data-siswa?kelas=${encodeURIComponent(targetKelas)}" class="btn" style="padding:6px 10px;font-size:12px;text-decoration:none">Lihat Kelas ${targetKelas}</a>` : ''}
                                ` : I.t === 'nilai' ? `
                                    <a href="${appBaseUrl}/admin/import/nilai" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">🔄 Selesai &amp; Muat Ulang Data Nilai</a>
                                ` : I.t === 'hadir' ? `
                                    <a href="${appBaseUrl}/admin/import/kehadiran" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">🔄 Selesai &amp; Muat Ulang Data Kehadiran</a>
                                ` : `
                                    <a href="${appBaseUrl}/admin/import/ekskul" class="btn a" style="padding:6px 12px;font-size:12px;text-decoration:none;font-weight:600">🔄 Selesai &amp; Muat Ulang Data Ekskul</a>
                                `}
                                <button type="button" class="btn o" onclick="resetImportPreview()">⬆ Import File Lain</button>
                            </div>
                        </div>`;
                }
                if (window.toast) window.toast(data.message, 4000);
            } else {
                alert('Gagal mengimpor: ' + (data.message || 'Kesalahan sistem'));
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = '⬇ Coba Impor Lagi';
                }
            }
        })
        .catch(err => {
            alert('Impor gagal: ' + err.message);
            if (btn) {
                btn.disabled = false;
                btn.textContent = '⬇ Coba Impor Lagi';
            }
        });
    };

})(window);
