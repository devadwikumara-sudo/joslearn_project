/**
 * JosBook Flexible Excel Import Engine
 * Mengimplementasikan 8 Tahap Pipeline Import Fleksibel:
 * 1. Baca berkas (SheetJS / XLSX array buffer)
 * 2. Deteksi struktur otomatis (baris judul, buang baris sampah)
 * 3. Pemetaan kolom berlapis (kamus alias + toleran kembar & kemiripan)
 * 4. Penggabungan antar-sheet (PesertaDidik + Kontrol Induk lewat NIS/Nama)
 * 5. Pembersihan & normalisasi (NISN 10 digit, ISO date, nama kelas standar)
 * 6. Validasi per baris (Error vs Peringatan)
 * 7. Pratinjau interaktif (deteksi sheet, ubah pemetaan kolom, pratinjau data)
 * 8. Simpan batch ke Laravel backend API (/admin/import/save-batch)
 */

(function(window) {
    'use strict';

    // Konversi Kolom Huruf Excel ke Indeks (0-based)
    const CI = a => [...a].reduce((n, c) => n * 26 + c.charCodeAt(0) - 64, 0) - 1;

    // Pemetaan Kolom DATABASE_SISWA.xlsx sheet PesertaDidik
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
        BP:'pend_wali', BQ:'kerja_wali', BR:'gaji_wali', BS:'hp_wali', BT:'ket_wali',
        BU:'alw_jalan', BV:'alw_rt', BW:'alw_desa', BX:'alw_kec', BY:'alw_kab',
        BZ:'alw_prov', CA:'alw_pos', CD:'nopes'
    };

    // Pemetaan Kolom DATABASE_SISWA.xlsx sheet Kontrol Induk
    const KMAP = {
        I:'alasan_keluar', J:'tgl_keluar', K:'kelas_keluar', L:'tujuan', M:'nopes',
        N:'no_skl', O:'no_ijazah_sma', P:'tgl_ijazah_sma', Q:'no_transkrip'
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
        const r = m[2].replace(/\s+/g, ' ').replace(/[\s\-._]+$/, '').trim();
        return r ? t + '-' + r : t;
    };

    const normSem = v => (/genap|2/i.test(String(v)) ? 'Genap' : /ganjil|1/i.test(String(v)) ? 'Ganjil' : '');
    const normN = v => {
        v = String(v ?? '').trim();
        return /^\d{6,9}$/.test(v) ? v.padStart(10, '0') : v;
    };

    const numStr = v => (typeof v === 'number' ? v.toLocaleString('fullwide', { useGrouping: false }) : String(v).trim());

    const toIso = v => {
        if (typeof v === 'number' && v > 20000 && v < 80000) return iso(v);
        const t = String(v).trim();
        let m = /^(\d{1,2})[\/.\-](\d{1,2})[\/.\-](\d{4})$/.exec(t);
        if (m) return m[3] + '-' + m[2].padStart(2, '0') + '-' + m[1].padStart(2, '0');
        m = /^(\d{1,2})\s+([A-Za-z]+)\s+(\d{4})$/.exec(t);
        const bi = m ? BLN.findIndex(b => b.toLowerCase() === m[2].toLowerCase()) : -1;
        return bi >= 0 ? m[3] + '-' + String(bi + 1).padStart(2, '0') + '-' + m[1].padStart(2, '0') : t;
    };

    const nz = s => String(s ?? '').toLowerCase().replace(/\([^)]*\)/g, ' ').replace(/[^a-z0-9]+/g, ' ').trim();

    // Kamus Alias Header Kolom Fleksibel
    const ALX = {
        nisn: 'nisn',
        nis: 'nis|nomor induk|no induk|nomor induk siswa|no induk siswa|nipd',
        nama: 'nama|nama lengkap|nama siswa|nama peserta didik|nama murid|peserta didik|siswa',
        jk: 'jk|l p|jenis kelamin|gender|kelamin',
        tmp: 'tempat lahir|tmp lahir|kota lahir',
        tgl: 'tanggal lahir|tgl lahir',
        kelas: 'kelas|rombel|rombongan belajar|nama rombel|kls',
        ayah: 'nama ayah|ayah|nama ayah kandung|nama bapak',
        ibu: 'nama ibu|ibu|nama ibu kandung',
        agama: 'agama',
        kewarganegaraan: 'kewarganegaraan|warga negara',
        alamat: 'alamat|jalan|dusun|alamat jalan|jalan dusun',
        rt_rw: 'rt rw|rt|rtrw',
        kelurahan: 'desa|kelurahan|desa kelurahan',
        kecamatan: 'kecamatan',
        kab: 'kabupaten|kota|kabupaten kota',
        prov: 'provinsi',
        kodepos: 'kode pos',
        hp: 'hp|no hp|nomor hp|telepon|no telepon|nomor telepon|no telp',
        sakit: 'sakit',
        izin: 'izin|ijin',
        alpa: 'alpa|alpha|alfa|tanpa keterangan',
        ekskul: 'ekskul|ekstrakurikuler|ekstra kurikuler|nama ekskul|kegiatan ekstrakurikuler',
        predikat: 'predikat|nilai ekskul',
        kokur: 'kokurikuler|deskripsi kokurikuler|catatan kokurikuler|deskripsi nilai kokurikuler',
        sem: 'semester|smt',
        mapelcol: 'mata pelajaran|mapel|nama mapel',
        nilaicol: 'nilai|nilai akhir|skor|nilai rapor'
    };

    const MEX = {
        agm: 'pai|pabp|pendidikan agama|agama dan budi pekerti|pendidikan agama islam',
        pnc: 'pkn|ppkn|pancasila|pendidikan kewarganegaraan',
        ind: 'b indonesia|b ind|bahasa indonesia',
        mtk: 'mat|matematika umum|matematika wajib',
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
        ['agm','Pendidikan Agama dan Budi Pekerti'],['pnc','Pendidikan Pancasila'],['ind','Bahasa Indonesia'],
        ['mtk','Matematika'],['ipa','Ilmu Pengetahuan Alam (IPA)'],['ips','Ilmu Pengetahuan Sosial (IPS)'],
        ['big','Bahasa Inggris'],['pjk','Pendidikan Jasmani Olahraga dan Kesehatan'],['sej','Sejarah'],
        ['sbd','Seni Budaya dan Prakarya/Seni dan Budaya'],['inf','Informatika'],['mtl','Matematika Tingkat Lanjut'],
        ['fis','Fisika'],['kim','Kimia'],['bio','Biologi'],['sos','Sosiologi'],['eko','Ekonomi'],
        ['geo','Geografi'],['sjl','Sejarah Tingkat Lanjut'],['bil','Bahasa Inggris Tingkat Lanjut'],
        ['pkw','Prakarya dan Kewirausahaan'],['jaw','Bahasa Jawa']
    ];

    const SC = SUB.map(x => x[0]);
    const SN = Object.fromEntries(SUB);

    const FD = [
        ['A. Keterangan peserta didik',['nama|Nama lengkap','panggilan|Nama panggilan','nisn|NISN','nis|Nomor induk','jk|Jenis kelamin|s:L,P','tmp|Tempat lahir','tgl|Tanggal lahir|d','agama|Agama','kewarganegaraan|Kewarganegaraan','nik|NIK','anak_ke|Anak ke|n','jml_saudara|Jumlah saudara kandung','yatim|Anak yatim / yatim piatu','bahasa|Bahasa sehari-hari','kelas|Kelas (tahun ajaran ini)|k']],
        ['B. Tempat tinggal',['alamat|Jalan / Dusun','rt_rw|RT/RW','kelurahan|Desa','kecamatan|Kecamatan','kab|Kabupaten','prov|Provinsi','kodepos|Kode pos','hp|Nomor telepon/HP','tinggal|Tempat tinggal','jarak|Jarak ke sekolah (km)']],
        ['C. Kesehatan',['gol_darah|Golongan darah','penyakit|Penyakit yang diderita','kelainan|Kelainan jasmani/fisik','tinggi|Tinggi badan (cm)','berat|Berat badan (kg)']],
        ['D. Pendidikan',['asal|Sekolah asal','tgl_ijazah_smp|Tanggal ijazah','no_ijazah_smp|Nomor ijazah','lama|Lama belajar (tahun)','masuk|Pindahan / jenis registrasi','sekolah_pindah|Pindahan dari sekolah','alasan_pindah|Alasan pindah','kelas_masuk|Diterima di kelas','rombel_masuk|Rombel kelas ke','tgl_masuk|Tanggal diterima|d']],
        ['E. Ayah kandung',['ayah|Nama','agama_ayah|Agama','wn_ayah|Kewarganegaraan','pend_ayah|Pendidikan terakhir','kerja_ayah|Pekerjaan','gaji_ayah|Penghasilan per bulan','hp_ayah|Nomor telepon/HP','ket_ayah|Keadaan']],
        ['E. Ibu kandung',['ibu|Nama','agama_ibu|Agama','wn_ibu|Kewarganegaraan','pend_ibu|Pendidikan terakhir','kerja_ibu|Pekerjaan','gaji_ibu|Penghasilan per bulan','hp_ibu|Nomor telepon/HP','ket_ibu|Keadaan']],
        ['E. Alamat orang tua',['al_jalan|Jalan','al_rt|RT/RW','al_desa|Desa','al_kec|Kecamatan','al_kab|Kabupaten','al_prov|Provinsi','al_pos|Kode pos']],
        ['F. Wali',['wali|Nama wali','agama_wali|Agama','wn_wali|Kewarganegaraan','pend_wali|Pendidikan terakhir','kerja_wali|Pekerjaan','gaji_wali|Penghasilan per bulan','hp_wali|Nomor telepon/HP','alw_jalan|Alamat: jalan','alw_rt|Alamat: RT/RW','alw_desa|Alamat: desa','alw_kec|Alamat: kecamatan','alw_kab|Alamat: kabupaten','alw_prov|Alamat: provinsi','alw_pos|Alamat: kode pos']],
        ['G-I. Keluar, lulus, catatan',['status|Status|s:Aktif,Pindah keluar,Keluar,Lulus','tgl_keluar|Tanggal meninggalkan sekolah|d','kelas_keluar|Keluar dari kelas','alasan_keluar|Alasan meninggalkan sekolah','tujuan|Sekolah tujuan','nopes|Nomor peserta PSAJ','no_skl|Nomor SKL','no_ijazah_sma|Nomor ijazah nasional','tgl_ijazah_sma|Tanggal ijazah|d','no_transkrip|Nomor transkrip nilai','lanjut|Melanjutkan studi / bekerja','catatan|Catatan']],
        ['Lampiran berkas',['doc_kk|Kartu Keluarga|c','doc_akta|Akta kelahiran|c','doc_ijazah|Ijazah SMP|c','doc_foto|Pas foto 3x4|c','doc_pindah|Surat pindah|c']],
        ['Kenaikan kelas (laporan)',['kls_ke|Naik ke kelas','tgl_naik|Tanggal keputusan|d','ket_naik|Keputusan|s:NAIK,TIDAK NAIK,LULUS,TIDAK LULUS','wk_nama|Nama wali kelas','wk_nip|NIP wali kelas']]
    ];

    const FK = FD.flatMap(x => x[1].map(f => f.split('|')[0]));
    const FL = Object.fromEntries(FD.flatMap(x => x[1].map(f => {
        const q = f.split('|');
        return [q[0], q[1]];
    })));

    let _am = null;
    const AMAP = () => {
        if (_am) return _am;
        _am = {};
        const cnt = {};
        FD.forEach(([, fs]) => fs.forEach(f => {
            const q = f.split('|');
            cnt[nz(q[1])] = (cnt[nz(q[1])] || 0) + 1;
        }));
        FD.forEach(([, fs]) => fs.forEach(f => {
            const q = f.split('|');
            if (cnt[nz(q[1])] === 1) _am[nz(q[1])] = q[0];
            _am[nz(q[0])] = q[0];
        }));
        Object.entries(ALX).forEach(([k, a]) => a.split('|').forEach(x => { _am[x] = k; }));
        return _am;
    };

    const fieldOf = h => AMAP()[nz(h)] || '';
    const mapelOf = h => {
        const x = nz(h);
        if (!x) return '';
        const r = SUB.find(m => nz(m[1]) === x || m[0] === x);
        if (r) return r[0];
        for (const [c, a] of Object.entries(MEX)) {
            if (a.split('|').includes(x)) return c;
        }
        return '';
    };

    // Pembaca Khusus multi-sheet DATABASE_SISWA.xlsx (PesertaDidik + Kontrol Induk)
    function realRows(w) {
        const rd = n => w.Sheets[n] ? XLSX.utils.sheet_to_json(w.Sheets[n], { header: 1, defval: '' }) : [];
        const km = {};
        rd('Kontrol Induk').slice(5).forEach(r => {
            if (r[2] !== '' && r[2] != null) km[String(r[2]).trim()] = r;
        });

        const out = [];
        rd('PesertaDidik').slice(5).forEach(r => {
            if (!cl(r[CI('D')]) || r[CI('C')] === '') return;
            const o = {};
            for (const [c, k] of Object.entries(RMAP)) {
                let v = cl(r[CI(c)]);
                if ((k === 'tgl' || k === 'tgl_masuk' || k === 'tgl_ijazah_smp') && typeof v === 'number') {
                    v = iso(v);
                }
                o[k] = (v === '' || v == null) ? '-' : v;
            }
            o.nis = String(o.nis);
            o.nisn = normN(String(r[CI('F')] ?? '').trim());
            o.jk = /^p/i.test(o.jk) ? 'P' : 'L';
            o.kelas = normK((o.kelas_masuk || '') + '-' + (o.rombel_masuk || ''));

            const q = km[o.nis];
            if (q) {
                for (const [c, k] of Object.entries(KMAP)) {
                    let v = cl(q[CI(c)]);
                    if (typeof v === 'number' && k.startsWith('tgl')) v = iso(v);
                    o[k] = (v === '' || v == null) ? '-' : v;
                }
                if (q[CI('K')]) o.kelas = normK(q[CI('K')]);
            }

            o.status = (o.alasan_keluar === 'Lulus' || /lulus/i.test(o.alasan_keluar || '')) ? 'Lulus' : 'Aktif';
            if (o.tgl_keluar && o.tgl_keluar !== '-') o.thn_lulus = +String(o.tgl_keluar).slice(0, 4);
            out.push(o);
        });
        return out;
    }

    function hsplit(h) {
        const t = String(h).trim();
        const m = /\b(?:semester|smt|sem)\s*\.?\s*([12])\b|\b(ganjil|genap)\b/i.exec(t);
        if (!m) return { b: t, s: '' };
        const b = t.replace(m[0], ' ').replace(/[\s\-_:()]+$/, '').replace(/^[\s\-_:()]+/, '').trim();
        return { b: b || t, s: m[2] ? (/genap/i.test(m[2]) ? 'Genap' : 'Ganjil') : (m[1] === '2' ? 'Genap' : 'Ganjil') };
    }

    const fx = h => {
        let f = fieldOf(h);
        if (!f) {
            const g = fieldOf(String(h).replace(/[\s_\-.]*\d+$/, ''));
            if (g === 'ekskul' || g === 'predikat') f = g;
        }
        return f;
    };

    function analyze(name, aoa) {
        let best = null;
        for (let r = 0; r < Math.min(aoa.length, 25); r++) {
            let sc = 0, idf = 0;
            (aoa[r] || []).forEach(h => {
                const t = String(h).trim();
                if (!t || /^\d+$/.test(t)) return;
                const b = hsplit(t).b, f = fx(b) || fx(t);
                if (f || mapelOf(b)) {
                    sc++;
                    if (f === 'nama' || f === 'nisn') idf = 1;
                }
            });
            if (idf && sc >= 2 && (!best || sc > best.sc)) best = { r, sc };
        }

        if (!best) return null;
        const hr = best.r, heads = (aoa[hr] || []).map(h => String(h).trim()), map = {}, semOf = {}, base = {};
        heads.forEach((h, i) => {
            if (!h) return;
            const q = hsplit(h);
            base[i] = q.b;
            let f = fx(q.b), m = f ? '' : mapelOf(q.b);
            if (q.s && (f === 'mapelcol' || f === 'nilaicol' || f === 'nama' || f === 'nisn' || f === 'kelas')) f = '';
            map[i] = f || (m ? 'm:' + m : '');
            if (q.s && map[i]) semOf[i] = q.s;
        });

        const ks = Object.values(map);
        const has = k => ks.includes(k);
        const nm = ks.filter(k => k.startsWith('m:')).length;
        const sf = ks.filter(k => FK.includes(k) && !['nisn','nama','kelas','nis'].includes(k)).length;
        const ts = [];

        if (nm || (has('mapelcol') && has('nilaicol'))) ts.push('nilai');
        if (has('ekskul')) ts.push('ekskul');
        if (has('sakit') || has('izin') || has('alpa')) ts.push('hadir');
        if (sf >= 2) ts.push('siswa');

        let kelas = '';
        if (/^\s*(kelas\s*)?(XII|XI|X|12|11|10)([\s\-._]*[A-Za-z0-9 ]*)?$/i.test(name)) kelas = normK(name);
        if (!kelas) {
            for (let r = 0; r < hr && !kelas; r++) {
                const m = /kelas\s*[:=]?\s*([A-Za-z0-9.\- ]{1,10})/i.exec((aoa[r] || []).join(' '));
                if (m && /^(XII|XI|X|12|11|10)/i.test(m[1].trim())) kelas = normK(m[1]);
            }
        }
        return { name, aoa, hr, heads, map, semOf, base, t: ts[0] || '', ts, kelas };
    }

    function buildRows(I) {
        const t = I.t;
        const newM = [], newX = [], lm = {}, out = [];
        const sw = /^(rata|jumlah|total|ttd|mengetahui|keterangan)/i;
        const com = ['nisn','nama','kelas','sem'];

        const newFor = h => {
            const q = nz(h);
            if (!lm[q]) {
                const c = (q.replace(/[^a-z]/g, '') + 'xxx').slice(0, 3);
                lm[q] = c;
                newM.push({ kode: c, nama: String(h).trim() });
            }
            return lm[q];
        };

        const nv = (k, v) => {
            if (['nisn','nis','nik','hp'].includes(k)) return numStr(v);
            if (k.startsWith('tgl')) return toIso(v);
            if (k === 'jk') return /^p/i.test(String(v)) ? 'P' : 'L';
            if (k === 'kelas') return normK(v);
            if (k === 'sem') return normSem(v);
            if (['sakit','izin','alpa'].includes(k)) return /\d/.test(String(v)) ? parseInt(String(v).match(/\d+/)[0]) : 0;
            if ((k.startsWith('m:') || k === 'new' || k === 'nilaicol') && typeof v === 'string' && /^\d+([.,]\d+)?$/.test(v.trim())) {
                return v.trim().replace(',', '.');
            }
            return v;
        };

        I.raws.forEach(Z => {
            const ks = Object.values(Z.map);
            const long = ks.includes('mapelcol') && ks.includes('nilaicol');
            const nc = {};
            Object.entries(Z.map).forEach(([i, k]) => {
                if (k === 'new') nc[i] = newFor(Z.base[i] || Z.heads[i]);
            });

            for (let r = Z.hr + 1; r < Z.aoa.length; r++) {
                const row = Z.aoa[r];
                if (!row) continue;
                const o0 = {}, bk = {};
                Object.entries(Z.map).forEach(([i, k]) => {
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
                if (o0.nisn) o0.nisn = normN(o0.nisn);

                if (t === 'siswa') {
                    const o = { ...o0, ...(bk[''] || {}) };
                    if (!o.nisn && o.nis) o.nisn = normN(o.nis);
                    out.push(o);
                    continue;
                }

                const sms = Object.keys(bk).filter(x => x);
                const keys = sms.length ? sms : [''];
                keys.forEach(sm => {
                    const o = { ...o0, ...(bk[''] || {}), ...(bk[sm] || {}) };
                    if (sm) o.sem = sm;
                    if (t === 'ekskul') {
                        const eks = (o.ekskul || []).filter(x => String(x).trim());
                        const pd = o.predikat || [];
                        eks.forEach((n, j) => out.push({ ...o, ekskul: String(n).trim(), predikat: pd[j] ?? (pd.length === 1 ? pd[0] : 'A') }));
                        return;
                    }
                    if (t === 'hadir' && !['sakit','izin','alpa','kokur'].some(k => o[k] != null)) return;
                    if (t === 'nilai' && !Object.keys(o).some(k => !com.includes(k))) return;
                    out.push(o);
                });
            }
        });

        I.newM = newM.filter(m => out.some(r => r[m.kode] != null && r[m.kode] !== ''));
        return out;
    }

    // State Import Tunggal
    const S = {
        imp: {
            t: 'siswa',
            mode: 'semua',
            kelas: 'X-1',
            sem: 'Genap',
            dup: 'timpa',
            ta: '2024/2025',
            raws: null,
            rows: null,
            newM: [],
            newX: [],
            bad: []
        }
    };

    // Fungsi Render Card Deteksi & Pratinjau
    function renderPreviewUI() {
        const I = S.imp;
        const container = document.getElementById('josbook-import-preview-area');
        if (!container) return;

        if (!I.rows || !I.rows.length) {
            container.innerHTML = `
                <div class="card" style="margin-top:14px">
                    <div class="note" style="background:#fbe5e1;border-color:#efc6bf;color:#8b2a1e">
                        ⚠️ File berhasil dibaca namun tidak ada baris data yang cocok. Periksa apakah kolom nama atau NISN tersedia.
                    </div>
                </div>`;
            return;
        }

        const t = I.t;
        const typeLabels = {
            siswa: 'Data Siswa & Induk',
            nilai: 'Data Nilai Rapor',
            hadir: 'Ketidakhadiran & Kokurikuler',
            ekskul: 'Ekstrakurikuler',
            guru: 'Data Guru'
        };

        const totalRows = I.rows.length;
        const uniqueClasses = [...new Set(I.rows.map(r => normK(r.kelas)).filter(Boolean))];

        // Validasi ringkas
        let errCount = 0;
        let warnCount = 0;
        I.rows.forEach(r => {
            if (!r.nama && !r.nisn) errCount++;
            if (r.nisn && !/^\d{10}$/.test(r.nisn)) warnCount++;
        });

        let detHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:10px">
                    <h3 style="margin:0">2. Hasil Deteksi Otomatis</h3>
                    <span class="bd ok" style="font-size:12px;padding:4px 10px">Terdeteksi: ${typeLabels[t] || t}</span>
                </div>
                <div class="bar" style="margin-bottom:12px">
                    ${(I.raws || []).map(z => `<span class="bd in">Sheet: <b>${z.name}</b> · Baris ${z.hr + 1}</span>`).join(' ')}
                    ${uniqueClasses.length ? `<span class="bd ok">Kelas Terdeteksi (${uniqueClasses.length}): <b>${uniqueClasses.slice(0, 6).join(', ')}${uniqueClasses.length > 6 ? '...' : ''}</b></span>` : ''}
                    ${(I.newM || []).length ? `<span class="bd w">Mapel Baru (${I.newM.length}): <b>${I.newM.map(m=>m.nama).join(', ')}</b></span>` : ''}
                </div>
            </div>`;

        // Kolom Pratinjau (Maksimal 9 kolom pertama yang ada isi)
        const sampleRow = I.rows[0] || {};
        const allKeys = Object.keys(sampleRow);
        const previewKeys = ['nisn', 'nis', 'nama', 'kelas', 'jk', 'tmp', 'tgl', 'ayah', 'status', 'mtk', 'ind', 'sakit', 'izin', 'alpa', 'ekskul']
            .filter(k => allKeys.includes(k))
            .concat(allKeys.filter(k => !['nisn','nis','nama','kelas','jk','tmp','tgl','ayah','status','mtk','ind','sakit','izin','alpa','ekskul'].includes(k)))
            .slice(0, 8);

        const previewRows = I.rows.slice(0, 5);

        let prevHtml = `
            <div class="card" style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
                    <h3 style="margin:0">3. Pratinjau — ${totalRows} Baris Siap Diimpor</h3>
                    <div style="display:flex;gap:8px">
                        <span class="bd ok">✔ ${totalRows - errCount} Baris Siap</span>
                        ${errCount ? `<span class="bd e">❌ ${errCount} Baris Error</span>` : ''}
                        ${warnCount ? `<span class="bd w">⚠️ ${warnCount} Peringatan</span>` : ''}
                    </div>
                </div>

                <div class="tw">
                    <table>
                        <thead>
                            <tr style="background:#f4f7fb">
                                <th style="width:40px">No</th>
                                ${previewKeys.map(k => `<th>${FL[k] || SN[k] || k.toUpperCase()}</th>`).join('')}
                            </tr>
                        </thead>
                        <tbody>
                            ${previewRows.map((r, i) => `
                                <tr>
                                    <td>${i + 1}</td>
                                    ${previewKeys.map(k => `<td>${r[k] !== undefined && r[k] !== null && r[k] !== '' ? String(r[k]) : '<span style="color:#a0aec0">-</span>'}</td>`).join('')}
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>

                ${totalRows > 5 ? `<p class="sub" style="margin:10px 0 0;font-size:12px">... dan ${totalRows - 5} baris lainnya akan diproses.</p>` : ''}

                <div class="bar" style="margin-top:16px;padding-top:14px;border-top:1px solid #e2e8f0">
                    <button type="button" class="btn o" onclick="resetImportPreview()">Batal</button>
                    <span class="sp"></span>
                    <button type="button" id="btn-do-import" class="btn a" style="font-size:13px;padding:9px 18px" onclick="executeBatchImport()">
                        ⬇ Impor ${totalRows} Data ${typeLabels[t] || t} ke Database
                    </button>
                </div>
            </div>`;

        container.innerHTML = detHtml + prevHtml;
    }

    // Fungsi Utama Pembaca File Excel
    window.smartFile = function(e, expectedType) {
        const fileInput = e.target || e;
        const f = fileInput.files ? fileInput.files[0] : null;
        if (!f) return;

        if (!window.XLSX) {
            alert('Pustaka SheetJS belum dimuat. Mohon muat ulang halaman.');
            return;
        }

        const toastEl = document.getElementById('ts');
        if (toastEl) {
            toastEl.textContent = 'Membaca dan memvalidasi file Excel...';
            toastEl.style.display = 'block';
        }

        const r = new FileReader();
        r.onload = function(x) {
            let w;
            try {
                w = XLSX.read(x.target.result, { type: 'array' });
            } catch (err) {
                alert('File tidak bisa dibaca sebagai berkas Excel / CSV.');
                return;
            }

            const I = S.imp;
            I.t = expectedType || 'siswa';

            // Ambil opsi dari form di layar jika ada
            const taSelect = document.querySelector('select[name="tahun_ajaran"]') || document.getElementById('tahun_ajaran');
            if (taSelect) I.ta = taSelect.value;
            const semSelect = document.querySelector('select[name="semester"]') || document.getElementById('semester');
            if (semSelect) I.sem = semSelect.value;
            const dupSelect = document.querySelector('select[name="duplikat"]') || document.querySelector('select[name="dup"]');
            if (dupSelect) I.dup = dupSelect.value;
            const modeSelect = document.querySelector('select[name="mode"]');
            if (modeSelect) I.mode = modeSelect.value === 'satu_kelas' ? 'kelas' : 'semua';
            const kelasSelect = document.querySelector('select[name="kelas"]');
            if (kelasSelect) I.kelas = normK(kelasSelect.value);

            // 1. Cek jika file adalah DATABASE_SISWA.xlsx (ada sheet PesertaDidik)
            if (w.Sheets.PesertaDidik) {
                I.t = 'siswa';
                I.raws = [{ name: 'PesertaDidik', hr: 4, heads: Object.keys(RMAP) }];
                if (w.Sheets['Kontrol Induk']) {
                    I.raws.push({ name: 'Kontrol Induk', hr: 4, heads: Object.keys(KMAP) });
                }
                I.rows = realRows(w);
                I.mode = 'semua';
                renderPreviewUI();
                if (window.toast) toast('Terbaca ' + I.rows.length + ' siswa dari PesertaDidik + Kontrol Induk');
                return;
            }

            // 2. Analisis umum (Nilai, Kehadiran, Ekskul, Guru, dsb)
            const rd = n => XLSX.utils.sheet_to_json(w.Sheets[n], { header: 1, defval: '' });
            const allAnalyzed = w.SheetNames.map(n => analyze(n, rd(n))).filter(Boolean);

            let cand = allAnalyzed.filter(a => a.ts.includes(I.t));
            let use = cand.length ? cand : allAnalyzed;

            if (!use.length) {
                // Fallback jika tidak ada sheet yang otomatis terdeteksi: ambil sheet pertama
                const n = w.SheetNames[0];
                const aoa = rd(n);
                const hr = Math.max(0, aoa.findIndex(q => (q || []).filter(c => String(c).trim() !== '').length >= 2));
                const hd = (aoa[hr] || []).map(h => String(h).trim());
                use = [{ name: n, aoa, hr, heads: hd, map: {}, semOf: {}, base: Object.fromEntries(hd.map((h, i) => [i, h])), t: I.t, ts: [], kelas: '' }];
            }

            I.raws = use;
            I.rows = buildRows(I);
            if (I.rows.some(q => q.kelas)) I.mode = 'semua';

            renderPreviewUI();
            if (window.toast) {
                toast('Terbaca ' + I.rows.length + ' baris dari ' + use.length + ' sheet');
            }
        };

        r.readAsArrayBuffer(f);
    };

    window.resetImportPreview = function() {
        S.imp.rows = null;
        S.imp.raws = null;
        const container = document.getElementById('josbook-import-preview-area');
        if (container) container.innerHTML = '';
        const fileInputs = document.querySelectorAll('input[type="file"]');
        fileInputs.forEach(i => { i.value = ''; });
    };

    window.executeBatchImport = function() {
        const I = S.imp;
        if (!I.rows || !I.rows.length) {
            alert('Tidak ada baris data yang siap diimpor.');
            return;
        }

        const btn = document.getElementById('btn-do-import');
        if (btn) {
            btn.disabled = true;
            btn.textContent = '⏳ Menyimpan data ke database...';
        }

        const token = document.querySelector('meta[name="csrf-token"]') ? document.querySelector('meta[name="csrf-token"]').getAttribute('content') : '';

        const payload = {
            type: I.t,
            tahun_ajaran: I.ta || '2024/2025',
            semester: I.sem || 'Genap',
            mode: I.mode || 'semua',
            kelas: I.kelas || 'X-1',
            dup: I.dup || 'timpa',
            rows: I.rows,
            new_classes: [...new Set(I.rows.map(r => normK(r.kelas)).filter(Boolean))],
            new_mapel: I.newM || []
        };

        fetch('/admin/import/save-batch', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify(payload)
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                if (window.toast) toast(data.message, 4000);
                setTimeout(() => {
                    window.location.reload();
                }, 1200);
            } else {
                alert('Gagal mengimpor data: ' + (data.message || 'Terjadi kesalahan sistem'));
                if (btn) {
                    btn.disabled = false;
                    btn.textContent = '⬇ Coba Impor Lagi';
                }
            }
        })
        .catch(err => {
            alert('Koneksi server gagal: ' + err.message);
            if (btn) {
                btn.disabled = false;
                btn.textContent = '⬇ Coba Impor Lagi';
            }
        });
    };

})(window);

