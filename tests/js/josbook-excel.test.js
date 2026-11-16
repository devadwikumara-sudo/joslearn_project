import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {dirname, resolve} from 'node:path';
import test from 'node:test';
import vm from 'node:vm';
import {fileURLToPath} from 'node:url';

const directory = dirname(fileURLToPath(import.meta.url));
const script = readFileSync(resolve(directory, '../../public/js/josbook-excel.js'), 'utf8');
const expectedStudents = [
    {nisn: '0000000001', nama: 'Siswa Uji A', nis: 'NIS-001', kelas: 'X-1'},
    {nisn: '0000000002', nama: 'Siswa Uji B', nis: 'NIS-002', kelas: 'X-2'}
];
const fixtures = [
    'excel-siswa-variasi-1.json',
    'excel-siswa-variasi-2.json',
    'excel-siswa-variasi-3.json',
    'excel-siswa-variasi-4.json'
];

function columnIndex(column) {
    return [...column].reduce((index, letter) => index * 26 + letter.charCodeAt(0) - 64, 0) - 1;
}

function workbookFromFixture(fixture) {
    let rows;
    if (fixture.legacyPrefixRows) {
        rows = [...fixture.legacyPrefixRows, ...fixture.records.map(record => {
            const row = [];
            Object.entries(record).forEach(([column, value]) => {
                row[columnIndex(column)] = value;
            });
            return row;
        })];
    } else {
        rows = [
            ...(fixture.titleRows || Array.from({length: fixture.skipRows || 0}, () => [])),
            fixture.headers,
            ...fixture.records
        ];
    }

    return {
        SheetNames: [fixture.sheetName],
        Sheets: {[fixture.sheetName]: rows}
    };
}

async function importWorkbook(workbook, expectedType = 'siswa', dictionaries = {columns: {}, subjects: {}}, saveResponse = {}) {
    const alerts = [];
    const window = {toast() {}};
    const preview = {innerHTML: ''};
    let payload = null;
    const classField = expectedType === 'siswa' ? {value: 'X-1'} : null;
    const modeField = expectedType === 'siswa' ? {value: 'semua'} : null;
    const csrfMeta = {getAttribute: () => 'csrf-test'};
    const endpointMeta = {getAttribute: () => '/test/save-batch'};
    const baseMeta = {getAttribute: () => '/test'};
    const document = {
        getElementById(id) {
            return id === 'josbook-import-preview-area' ? preview : null;
        },
        querySelector(selector) {
            if (selector === '[name="kelas"]') return classField;
            if (selector === 'select[name="mode"]') return modeField;
            if (selector === 'meta[name="csrf-token"]') return csrfMeta;
            if (selector === 'meta[name="batch-import-url"]') return endpointMeta;
            if (selector === 'meta[name="app-base-url"]') return baseMeta;
            return null;
        },
        querySelectorAll() {
            return [];
        }
    };
    const XLSX = {
        read() {
            return workbook;
        },
        utils: {
            sheet_to_json(sheet) {
                const rows = Array.isArray(sheet) ? sheet : sheet.data;
                return rows.map(row => row.map(cell => cell && typeof cell === 'object' && 'v' in cell ? cell.v : cell));
            },
            encode_cell({r, c}) {
                let column = c + 1;
                let address = '';
                while (column > 0) {
                    const remainder = (column - 1) % 26;
                    address = String.fromCharCode(65 + remainder) + address;
                    column = Math.floor((column - 1) / 26);
                }
                return `${address}${r + 1}`;
            }
        }
    };
    window.XLSX = XLSX;
    const requests = [];
    window.fetch = async (endpoint, options) => {
        const request = JSON.parse(options.body);
        requests.push({endpoint, request});
        let data;
        if (endpoint.endsWith('/metadata')) {
            data = {
                success: true,
                dictionaries,
                formats: (request.sheets || []).map(sheet => ({
                    name: sheet.name,
                    fingerprint: 'test-fingerprint',
                    format_id: null,
                    mapping: null
                }))
            };
        } else if (endpoint.endsWith('/start-batch')) {
            data = {success: true, batch_id: 17, total_rows: request.total_rows};
        } else if (endpoint.endsWith('/save-batch')) {
            payload = request;
            data = {
                success: true,
                batch_id: request.batch_id,
                count: request.rows.length,
                active_count: 0,
                graduated_count: 0,
                skipped: 0,
                errors: [],
                completed: request.final_chunk,
                message: 'Impor selesai',
                ...saveResponse
            };
        } else {
            data = {success: true};
        }
        return {
            ok: true,
            status: 200,
            headers: {get: () => 'application/json'},
            json: async () => data
        };
    };

    class FileReaderStub {
        readAsArrayBuffer() {
            this.finished = this.onload({target: {result: null}});
        }
    }
    let reader;
    class TrackedFileReader extends FileReaderStub {
        constructor() {
            super();
            reader = this;
        }
    }

    vm.runInNewContext(script, {
        window,
        document,
        XLSX,
        fetch: window.fetch,
        Blob,
        URL,
        FileReader: TrackedFileReader,
        alert: message => alerts.push(message)
    });
    window.smartFile({target: {files: [{name: 'fixture.xlsx'}]}}, expectedType);
    await reader.finished;

    return {
        state: JSON.parse(JSON.stringify(window.JosBookState.imp)),
        alerts,
        preview,
        window,
        requests,
        modeField,
        getPayload: () => payload,
        getState: () => JSON.parse(JSON.stringify(window.JosBookState.imp)),
        runImport: () => window.executeBatchImport()
    };
}

test('reads student records across four sheet and header variations', async () => {
    for (const [index, fixtureName] of fixtures.entries()) {
        const fixture = JSON.parse(readFileSync(resolve(directory, '../Fixtures', fixtureName), 'utf8'));
        const {state, alerts} = await importWorkbook(workbookFromFixture(fixture));
        const keyFields = state.rows.map(({nisn, nama, nis, kelas}) => ({nisn, nama, nis, kelas}));

        assert.deepEqual(alerts, [], fixtureName);
        assert.deepEqual(keyFields, expectedStudents, fixtureName);
        assert.equal(state.rows.length, 2, fixtureName);
        assert.equal(state.detection.sheet, fixture.sheetName, fixtureName);
        assert.equal(state.detection.legacyFallback, index === 3, fixtureName);
        assert.equal(state.detection.headerRow, index === 0 ? 2 : index === 1 ? 7 : index === 2 ? 1 : 5, fixtureName);
    }
});

test('preserves leading zeroes displayed by Excel numeric formats in NISN cells', async () => {
    const sheet = {
        data: [
            ['NISN', 'Nama', 'Kelas'],
            [123456789, 'Siswa Uji Format Nol', 'X-1']
        ],
        A2: {v: 123456789, w: '0123456789'}
    };
    const {state, alerts} = await importWorkbook({
        SheetNames: ['Data Siswa'],
        Sheets: {'Data Siswa': sheet}
    });

    assert.deepEqual(alerts, []);
    assert.equal(state.rows.length, 1);
    assert.equal(state.rows[0].nisn, '0123456789');
    assert.equal(state.rows[0].nama, 'Siswa Uji Format Nol');
});

test('skips numeric and repeated-heading rows before validating imported students', async () => {
    const headers = [
        'NISN', 'Nomor Induk (NIS)', 'Nama Lengkap', 'Kelas', 'Jenis Kelamin',
        'Tanggal Lahir', 'Nama Ayah', 'Nama Ibu'
    ];
    const {state, alerts} = await importWorkbook({
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['DATA SISWA'],
                ['Tahun Ajaran: 2026/2027'],
                [],
                headers,
                ['4', '1', '2', '37-38', '7', '40', '48', '3'],
                headers,
                ['0076665147', '5474', 'Agung Sanggar Widinata', 'XII-1', 'L', '21/08/2007', 'Warsito', 'Musrini']
            ]
        }
    });

    assert.deepEqual(alerts, []);
    assert.equal(state.rows.length, 1);
    assert.equal(state.rows[0].nama, 'Agung Sanggar Widinata');
    assert.equal(state.rows[0].nisn, '0076665147');
    assert.equal(state.rows[0].__excel_row, 7);
    assert.equal(state.statuses.length, 1);
    assert.equal(state.statuses[0].status, 'ready');
});

test('maps the DATABASE_SISWA workbook headers and links Kontrol Induk by NIS', async () => {
    const studentHeaders = [
        'No Induk', 'Nama Peserta Didik', 'Nama Panggilan', 'NISN', 'Agama', 'Kewarganegaraan',
        'Jumlah Saudara Kandung', 'Status Anak Yatim/Piatu', 'Jalan/Dusun', 'RT/RW', 'Desa',
        'Kecamatan', 'Kabupaten', 'Provinsi', 'Kode Pos', 'Nomor Telepon/HP', 'Tempat Tinggal',
        'Penyakit yang diderita', 'Kelainan Jasmani/Fisik', 'Pindahan', 'Dari Sekolah', 'Alasan',
        'Diterima di kelas', 'Rombel Kelas ke', 'Tanggal',
        'Nama Ayah Kandung', 'Agama', 'Kewarganegaraan', 'Pendidikan Terakhir', 'Pekerjaan',
        'Penghasilan per bulan', 'Nomor Telepon/HP', 'Keadaan',
        'Nama Ibu Kandung', 'Agama', 'Kewarganegaraan', 'Pendidikan Terakhir', 'Pekerjaan',
        'Penghasilan per bulan', 'Nomor Telepon/HP', 'Keadaan',
        'Jalan', 'RT/RW', 'Desa', 'Kecamatan', 'Kabupaten', 'Provinsi', 'Kode Pos',
        'Nama Wali', 'Agama', 'Kewarganegaraan', 'Pendidikan Terakhir', 'Pekerjaan',
        'Penghasilan per bulan', 'Nomor Telepon/HP', 'Keadaan',
        'Jalan', 'RT/RW', 'Desa', 'Kecamatan', 'Kabupaten', 'Provinsi', 'Kode Pos',
        'NAMA Lengkap', 'NOPES PSAJ', 'NIS', 'NIS', 'CEK NIS'
    ];
    const valuesByHeader = {
        'No Induk': ['1001'],
        'Nama Peserta Didik': ['Nama Siswa Uji'],
        'Nama Panggilan': ['Uji'],
        NISN: ['0000000123'],
        Agama: ['Islam', 'Islam', 'Islam', 'Islam'],
        Kewarganegaraan: ['Indonesia', 'Indonesia', 'Indonesia', 'Indonesia'],
        'Jumlah Saudara Kandung': ['2'],
        'Status Anak Yatim/Piatu': ['Tidak'],
        'Jalan/Dusun': ['Jalan Siswa'],
        'RT/RW': ['01/02', '03/04', '05/06'],
        Desa: ['Desa Siswa', 'Desa Orang Tua', 'Desa Wali'],
        Kecamatan: ['Kecamatan Siswa', 'Kecamatan Orang Tua', 'Kecamatan Wali'],
        Kabupaten: ['Kabupaten Siswa', 'Kabupaten Orang Tua', 'Kabupaten Wali'],
        Provinsi: ['Provinsi Siswa', 'Provinsi Orang Tua', 'Provinsi Wali'],
        'Kode Pos': ['11111', '22222', '33333'],
        'Nomor Telepon/HP': ['0811111111', '0822222222', '0833333333', '0844444444'],
        'Tempat Tinggal': ['Bersama Orang Tua'],
        'Penyakit yang diderita': ['Tidak Ada'],
        'Kelainan Jasmani/Fisik': ['Tidak Ada'],
        Pindahan: ['Siswa Baru'],
        'Dari Sekolah': ['SMP Uji'],
        Alasan: ['-'],
        'Diterima di kelas': ['X'],
        'Rombel Kelas ke': ['1'],
        Tanggal: ['01/07/2022'],
        'Nama Ayah Kandung': ['Ayah Uji'],
        'Nama Ibu Kandung': ['Ibu Uji'],
        'Nama Wali': ['Wali Uji'],
        'Pendidikan Terakhir': ['SMA', 'SMP', 'SMA'],
        Pekerjaan: ['Pegawai', 'Ibu Rumah Tangga', 'Wiraswasta'],
        'Penghasilan per bulan': ['5000000', '0', '3000000'],
        Keadaan: ['Hidup', 'Hidup', 'Hidup'],
        Jalan: ['Jalan Orang Tua', 'Jalan Wali'],
        'NAMA Lengkap': ['Nama Duplikat'],
        'NOPES PSAJ': ['PSAJ-UJI-2025'],
        NIS: ['1001', '1001'],
        'CEK NIS': ['Sesuai']
    };
    const studentRow = studentHeaders.map(header => valuesByHeader[header]?.shift() || '');
    const controlHeaders = [
        'No Induk', 'Nama Peserta Didik', 'Jenis Registrasi', 'Tanggal Masuk', 'Masuk Kelas',
        'Sekolah Asal SMP', 'Keluar Karena', 'Tanggal Keluar', 'Keluar Kelas', 'Sekolah Tujuan',
        'Nomor Peserta PSAJ', 'Nomor SKL', 'Nomor Ijazah Nasional', 'Tgl Pengesahan Ijazah',
        'Nomor Transkrip Nilai'
    ];
    const workbook = {
        SheetNames: ['Peserta Didik', 'Kontrol Induk'],
        Sheets: {
            'Peserta Didik': [studentHeaders, studentRow],
            'Kontrol Induk': [
                ['Registrasi Masuk', '', '', '', '', '', 'Registrasi Keluar'],
                controlHeaders,
                [
                    '1001', 'Nama Siswa Uji', 'Siswa Baru', '01/07/2022', 'X', 'SMP Uji',
                    'Lulus', '15/05/2025', 'XII-1', '-', 'PSAJ-UJI-2025', 'SKL-UJI-2025',
                    'IJAZAH-UJI-2025', '15/05/2025', 'TRANSKRIP-UJI-2025'
                ]
            ]
        }
    };
    const {state, alerts} = await importWorkbook(workbook);

    assert.deepEqual(alerts, []);
    assert.equal(state.detection.controlSheet, 'Kontrol Induk');
    assert.equal(state.rows.length, 1);
    assert.equal(state.rows[0].nis, '1001');
    assert.equal(state.rows[0].nama, 'Nama Siswa Uji');
    assert.equal(state.rows[0].nisn, '0000000123');
    assert.equal(state.rows[0].kelas, 'XII-1');
    assert.equal(state.rows[0].status, 'Lulus');
    assert.equal(state.rows[0].tahun_lulus, '2025');
    assert.equal(state.rows[0].jml_saudara, '2');
    assert.equal(state.rows[0].yatim, 'Tidak');
    assert.equal(state.rows[0].tinggal, 'Bersama Orang Tua');
    assert.equal(state.rows[0].ayah, 'Ayah Uji');
    assert.equal(state.rows[0].agama_ayah, 'Islam');
    assert.equal(state.rows[0].ibu, 'Ibu Uji');
    assert.equal(state.rows[0].wali, 'Wali Uji');
    assert.equal(state.rows[0].al_jalan, 'Jalan Orang Tua');
    assert.equal(state.rows[0].wali_jalan, 'Jalan Wali');
    assert.equal(state.rows[0].no_skl, 'SKL-UJI-2025');
    assert.equal(state.rows[0].no_ijazah_sma, 'IJAZAH-UJI-2025');
    assert.equal(state.rows[0].tgl_ijazah_sma, '2025-05-15');
    assert.equal(state.rows[0].no_transkrip, 'TRANSKRIP-UJI-2025');
});

test('requires a class for active students and allows selecting one after preview', async () => {
    const fixture = {
        headers: ['NISN', 'Nama'],
        records: [['0000000123', 'Siswa Aktif Tanpa Kelas']]
    };
    const imported = await importWorkbook(workbookFromFixture(fixture));

    assert.equal(imported.getState().statuses[0].status, 'error');
    assert.match(imported.getState().errors[0].msg, /pilih mode "Satu kelas"/);

    imported.modeField.value = 'kelas';
    imported.window.refreshImportPreviewOptions();
    assert.equal(imported.getState().statuses[0].status, 'ready');

    await imported.runImport();
    assert.equal(imported.getPayload().mode, 'kelas');
    assert.equal(imported.getPayload().kelas, 'X-1');
});

test('allows an alumni row without a class when graduation is identified', async () => {
    const fixture = {
        headers: ['NISN', 'Nama', 'Status Kelulusan', 'Tahun Lulus'],
        records: [['0000000124', 'Siswa Alumni Tanpa Kelas', 'Lulus', 2025]]
    };
    const {state} = await importWorkbook(workbookFromFixture(fixture));

    assert.equal(state.rows[0].status, 'Lulus');
    assert.equal(state.statuses[0].status, 'ready');
});

test('ignores Petunjuk sheets and recognizes the active Excel template headers', async () => {
    const templates = [
        {
            type: 'siswa',
            headers: [
                'NISN', 'NIS', 'Nama', 'Kelas', 'Tahun Ajaran', 'Jenis Kelamin',
                'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'NIK', 'Kewarganegaraan',
                'Alamat', 'RT/RW', 'Kelurahan', 'Kecamatan', 'Kabupaten', 'Provinsi',
                'Kode Pos', 'Nomor HP', 'Tinggal', 'Jarak ke Sekolah', 'Golongan Darah',
                'Penyakit', 'Kelainan Fisik', 'Tinggi Badan', 'Berat Badan', 'Sekolah Asal',
                'Nama Ayah', 'Pendidikan Ayah', 'Pekerjaan Ayah', 'Penghasilan Ayah', 'HP Ayah',
                'Nama Ibu', 'Pendidikan Ibu', 'Pekerjaan Ibu', 'Penghasilan Ibu', 'HP Ibu',
                'Nama Wali', 'Status Siswa'
            ]
        },
        {
            type: 'guru',
            headers: ['Nama', 'NIP', 'NUPTK', 'NIK', 'Jenis Kelamin', 'Tempat Lahir', 'Tanggal Lahir', 'Pendidikan', 'Status Kepegawaian', 'Mulai Bertugas', 'Nomor HP', 'Alamat'],
            row: ['Guru Uji', '198000000000000001', '', '', 'L', '', '', '', 'PNS', 2020, '', '']
        },
        {
            type: 'nilai',
            headers: ['NISN', 'Nama', 'Kelas', 'Semester', 'Mata Pelajaran', 'Nilai Akhir'],
            row: ['0000000001', 'Siswa Uji', 'X-1', 'Ganjil', 'Matematika', 88]
        },
        {
            type: 'hadir',
            headers: ['NISN', 'Nama', 'Kelas', 'Semester', 'Sakit (hari)', 'Izin (hari)', 'Alpa (hari)', 'Kokurikuler'],
            row: ['0000000001', 'Siswa Uji', 'X-1', 'Ganjil', 1, 0, 0, 'Catatan sintetis']
        },
        {
            type: 'ekskul',
            headers: ['NISN', 'Nama', 'Kelas', 'Semester', 'Nama Ekskul', 'Predikat'],
            row: ['0000000001', 'Siswa Uji', 'X-1', 'Ganjil', 'Pramuka', 'Baik']
        }
    ];

    for (const template of templates) {
        const row = template.row || Array(template.headers.length).fill('');
        if (template.type === 'siswa') {
            row[0] = '0000000001';
            row[1] = 'NIS-001';
            row[2] = 'Siswa Uji';
            row[3] = 'X-1';
        }
        const workbook = {
            SheetNames: ['Data', 'Petunjuk'],
            Sheets: {
                Data: [template.headers, row],
                Petunjuk: [['Petunjuk penggunaan'], ['Tidak ada baris contoh.']]
            }
        };
        const {state, alerts} = await importWorkbook(workbook, template.type);

        assert.deepEqual(alerts, [], template.type);
        assert.equal(state.rows.length, 1, template.type);
        assert.deepEqual(state.detection.unknown, [], template.type);
        if (template.type === 'siswa') {
            assert.equal(state.detection.sheet, 'Data');
        } else {
            assert.deepEqual(state.detection.sheets.map(sheet => sheet.name), ['Data'], template.type);
        }
    }
});

test('shows active and graduated student totals after saving', async () => {
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama', 'Kelas'],
                ['0000000101', 'Siswa Aktif', 'X-1'],
                ['0000000102', 'Siswa Lulus', 'XII-1']
            ]
        }
    };
    const {window, preview, requests, runImport} = await importWorkbook(
        workbook,
        'siswa',
        {columns: {}, subjects: {}},
        {active_count: 1, graduated_count: 1}
    );
    assert.match(preview.innerHTML, /id="btn-do-import"[^>]*>[\s\S]*Impor 2 Baris ke Data Siswa/);
    window.JosBookState.imp.statuses = [
        {status: 'ready'},
        {status: 'ready'}
    ];

    await runImport();

    assert.match(preview.innerHTML, /1 siswa aktif masuk Data Siswa, 1 siswa lulus masuk Alumni/);
    assert.match(preview.innerHTML, /\/test\/admin\/data-siswa/);
    assert.match(preview.innerHTML, /\/test\/admin\/alumni/);
    const saveRequest = requests.find(({endpoint}) => endpoint.endsWith('/save-batch'));
    assert.equal(saveRequest.request.type, 'siswa');
    assert.equal(saveRequest.request.dup, 'lengkapi');
});

test('reloads an academic import on the same tab after saving', async () => {
    const workbook = {
        SheetNames: ['Nilai'],
        Sheets: {
            Nilai: [
                ['NISN', 'Nama', 'Kelas', 'Semester', 'Mata Pelajaran', 'Nilai Akhir'],
                ['0000000101', 'Siswa Uji', 'X-1', 'Ganjil', 'Matematika', 88]
            ]
        }
    };
    const {window, preview, runImport} = await importWorkbook(workbook, 'nilai');
    window.JosBookState.imp.statuses = [{status: 'ready'}];

    await runImport();

    assert.match(preview.innerHTML, /\/test\/admin\/import\?tab=nilai/);
});

test('aggregates student status totals across saved chunks', async () => {
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama', 'Kelas'],
                ['0000000101', 'Siswa Uji', 'X-1']
            ]
        }
    };
    const {window, preview, requests, runImport} = await importWorkbook(
        workbook,
        'siswa',
        {columns: {}, subjects: {}},
        {active_count: 1, graduated_count: 1}
    );
    window.JosBookState.imp.rows = Array.from({length: 201}, (_, index) => ({
        nisn: String(1000000000 + index),
        nama: `Siswa ${index}`,
        kelas: 'X-1'
    }));
    window.JosBookState.imp.statuses = Array.from({length: 201}, () => ({status: 'ready'}));

    await runImport();

    assert.match(preview.innerHTML, /2 siswa aktif masuk Data Siswa, 2 siswa lulus masuk Alumni/);
    assert.equal(requests.filter(({endpoint}) => endpoint.endsWith('/save-batch')).length, 2);
});

test('maps repeated parent and guardian headings by family group', async () => {
    const fixture = JSON.parse(readFileSync(resolve(directory, '../Fixtures', fixtures[2]), 'utf8'));
    const {state} = await importWorkbook(workbookFromFixture(fixture));

    assert.equal(state.rows[0].agama_ayah, 'Islam');
    assert.equal(state.rows[0].kerja_ayah, 'Petani');
    assert.equal(state.rows[0].agama_ibu, 'Kristen');
    assert.equal(state.rows[0].kerja_ibu, 'Guru');
    assert.equal(state.rows[0].agama_wali, 'Hindu');
    assert.equal(state.rows[0].kerja_wali, 'Dokter');
    assert.equal(state.detection.recognized.length, 13);
    assert.equal(state.detection.unknown.length, 0);
});

test('reports missing required student headings and allows correcting the mapping', async () => {
    const workbooks = [
        {
            SheetNames: ['Data Siswa'],
            Sheets: {'Data Siswa': [['Nama Lengkap', 'Nomor Induk', 'Kelas'], ['Siswa Uji', 'NIS-001', 'X-1']]}
        },
        {
            SheetNames: ['Data Siswa'],
            Sheets: {'Data Siswa': [['NISN', 'Nomor Induk', 'Kelas'], ['0000000001', 'NIS-001', 'X-1']]}
        }
    ];
    const expectedMissingHeadings = ['NISN', 'Nama'];

    for (const [index, workbook] of workbooks.entries()) {
        const {state, alerts, window} = await importWorkbook(workbook);

        assert.deepEqual(alerts, []);
        assert.equal(state.rows.length, 0);
        assert.equal(state.detection.headerDetected, true);
        assert.ok(state.detection.missingRequired.length);
        assert.match(state.mappingIssues[0], new RegExp(expectedMissingHeadings[index]));
        window.changeStudentColumnMap(1, index === 0 ? 'nisn' : 'nama');
        assert.equal(window.JosBookState.imp.mappingIssues.length, 0);
        assert.equal(window.JosBookState.imp.rows.length, 1);
    }
});

test('allows manual mapping when every Excel heading is unfamiliar', async () => {
    const workbook = {
        SheetNames: ['Data Aktif'],
        Sheets: {
            'Data Aktif': [
                ['Kode Peserta', 'Nama Lengkap Murid', 'Rombel Aktif', 'Lahir Pada'],
                ['0000000123', 'Siswa Uji', 'X-1', '12 Mei 2008']
            ]
        }
    };
    const {state, preview, window} = await importWorkbook(workbook);

    assert.equal(state.detection.headerDetected, true);
    assert.equal(state.detection.headerRow, 1);
    assert.equal(state.rows.length, 0);
    assert.match(preview.innerHTML, /Contoh data/);
    assert.match(preview.innerHTML, /12 Mei 2008/);

    window.changeStudentColumnMap(0, 'nisn');
    window.changeStudentColumnMap(1, 'nama');
    window.changeStudentColumnMap(2, 'kelas');
    window.changeStudentColumnMap(3, 'tgl');

    const mappedStudent = window.JosBookState.imp.rows[0];
    assert.equal(mappedStudent.nisn, '0000000123');
    assert.equal(mappedStudent.nama, 'Siswa Uji');
    assert.equal(mappedStudent.kelas, 'X-1');
    assert.equal(mappedStudent.tgl, '2008-05-12');
    assert.equal(window.JosBookState.imp.mappingIssues.length, 0);
});

test('selects the sheet with the strongest matching header row', async () => {
    const workbook = {
        SheetNames: ['Catatan', 'Data Siswa'],
        Sheets: {
            Catatan: [['Nama Lengkap', 'NISN'], ['Siswa Uji A', '0000000001']],
            'Data Siswa': [
                ['Data siswa sekolah'],
                ['Kelas', 'NISN', 'Nama Lengkap', 'NIS', 'Agama'],
                ['X-1', '0000000001', 'Siswa Uji A', 'NIS-001', 'Islam']
            ]
        }
    };
    const {state, alerts} = await importWorkbook(workbook);

    assert.deepEqual(alerts, []);
    assert.equal(state.detection.sheet, 'Data Siswa');
    assert.equal(state.detection.headerRow, 2);
    assert.equal(state.rows[0].kelas, 'X-1');
});

test('shows per-row errors, edits cells, and sends only corrected rows', async () => {
    const workbook = {
        SheetNames: ['Nilai Uji'],
        Sheets: {
            'Nilai Uji': [
                ['NISN', 'Nama Lengkap', 'Kelas', 'Matematika'],
                ['0000000001', 'Siswa Uji', 'X-1', 140]
            ]
        }
    };
    const {state, preview, window, getPayload} = await importWorkbook(workbook, 'nilai');

    assert.equal(state.rows.length, 1);
    assert.equal(window.JosBookState.imp.statuses[0].status, 'error');
    assert.match(preview.innerHTML, /Terdeteksi: Data Nilai Rapor/);
    assert.match(preview.innerHTML, /Nilai Matematika harus angka 0–100/);
    assert.match(preview.innerHTML, /<button[^>]*id="btn-do-import"[^>]*disabled[^>]*>[\s\S]*Tidak ada baris valid untuk diimpor/);

    window.editImportCell({dataset: {row: '0', field: 'mtk'}, textContent: '85'});
    assert.equal(window.JosBookState.imp.statuses[0].status, 'ready');
    assert.match(preview.innerHTML, />Siap</);
    assert.match(preview.innerHTML, /<button[^>]*id="btn-do-import"[^>]*>[\s\S]*Impor 1 Baris Data Nilai Rapor/);
    assert.ok(preview.innerHTML.indexOf('id="btn-do-import"') < preview.innerHTML.indexOf('<div class="tw"><table>'));

    await window.executeBatchImport();
    assert.equal(getPayload().rows.length, 1);
    assert.equal(getPayload().rows[0].mtk, '85');
});

test('warns when an active student row also contains a graduation year', async () => {
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama Lengkap', 'Kelas', 'Status Siswa', 'Tahun Lulus'],
                ['0000000101', 'Siswa Uji', 'XII-1', 'Aktif', '2025']
            ]
        }
    };
    const {state, preview, window} = await importWorkbook(workbook);

    assert.equal(state.rows[0].status, 'Lulus');
    assert.equal(window.JosBookState.imp.statuses[0].status, 'warning');
    assert.match(preview.innerHTML, /Tahun lulus terisi tetapi status siswa Aktif/);
    assert.doesNotMatch(preview.innerHTML, /active_status_with_graduation_year/);
});

test('normalizes alumni status but does not treat belum lulus as graduated', async () => {
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama Lengkap', 'Status Kelulusan'],
                ['0000000101', 'Siswa Alumni', 'Alumni'],
                ['0000000102', 'Siswa Belum Lulus', 'Belum Lulus']
            ]
        }
    };
    const {state} = await importWorkbook(workbook);

    assert.deepEqual(state.rows.map(row => row.status), ['Lulus', 'Belum Lulus']);
});

test('does not pad short NISN values and marks them invalid', async () => {
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama Lengkap', 'Kelas'],
                ['123456789', 'Siswa Uji', 'X-1']
            ]
        }
    };
    const {state, preview, window} = await importWorkbook(workbook);

    assert.equal(state.rows[0].nisn, '123456789');
    assert.equal(window.JosBookState.imp.statuses[0].status, 'error');
    assert.match(preview.innerHTML, /NISN bukan 10 digit/);
});

test('excludes error rows from the batch payload', async () => {
    const workbook = {
        SheetNames: ['Nilai Uji'],
        Sheets: {
            'Nilai Uji': [
                ['NISN', 'Nama Lengkap', 'Kelas', 'Matematika'],
                ['0000000001', 'Siap', 'X-1', 85],
                ['0000000002', 'Error', 'X-1', 140]
            ]
        }
    };
    const {window, getPayload} = await importWorkbook(workbook, 'nilai');

    assert.equal(window.JosBookState.imp.statuses[0].status, 'ready');
    assert.equal(window.JosBookState.imp.statuses[1].status, 'error');
    await window.executeBatchImport();
    assert.equal(getPayload().rows.length, 1);
    assert.equal(getPayload().rows[0].nama, 'Siap');
});

test('requires confirmation before including warning rows', async () => {
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama Lengkap', 'Kelas', 'NIK'],
                ['0000000001', 'Siswa Uji', 'X-1', '12345']
            ]
        }
    };
    const {alerts, window, getPayload} = await importWorkbook(workbook);

    assert.equal(window.JosBookState.imp.statuses[0].status, 'warning');
    await window.executeBatchImport();
    assert.equal(getPayload(), null);
    assert.match(alerts.at(-1), /Konfirmasikan peringatan/);

    window.confirmImportWarnings(true);
    await window.executeBatchImport();
    assert.equal(getPayload().rows.length, 1);
});

test('normalizes numeric identifiers, whitespace, and empty placeholders', async () => {
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama Lengkap', 'NIS', 'Alamat', 'Tempat Lahir', 'Agama', 'Kewarganegaraan'],
                [1234567890, '  Siswa   Uji  ', 1.23456789012e11, '-', 'X', 'N/A', '   ']
            ]
        }
    };
    const {state} = await importWorkbook(workbook);

    assert.equal(state.rows.length, 1);
    assert.equal(state.rows[0].nisn, '1234567890');
    assert.equal(state.rows[0].nis, '123456789012');
    assert.equal(state.rows[0].nama, 'Siswa Uji');
    assert.equal(state.rows[0].alamat, null);
    assert.equal(state.rows[0].tmp, null);
    assert.equal(state.rows[0].agama, null);
    assert.equal(state.rows[0].kewarganegaraan, null);
});

test('normalizes supported dates and warns for future or implausibly old dates', async () => {
    const excelDate = (Date.UTC(2008, 4, 12) - Date.UTC(1899, 11, 30)) / 864e5;
    const workbook = {
        SheetNames: ['Data Siswa'],
        Sheets: {
            'Data Siswa': [
                ['NISN', 'Nama Lengkap', 'Kelas', 'Tanggal Lahir', 'Tanggal Diterima'],
                ['0000000001', 'Tanggal Teks', 'X-1', '12/05/2008', '12 Mei 2008'],
                ['0000000002', 'Tanggal Serial', 'X-1', excelDate, excelDate],
                ['0000000003', 'Tanggal Masa Depan', 'X-1', '01/01/2999', '01-01-2024'],
                ['0000000004', 'Tanggal Usia Tidak Wajar', 'X-1', '01/01/1800', '01-01-2024']
            ]
        }
    };
    const {state} = await importWorkbook(workbook);

    assert.equal(state.rows[0].tgl, '2008-05-12');
    assert.equal(state.rows[0].tgl_masuk, '2008-05-12');
    assert.equal(state.rows[1].tgl, '2008-05-12');
    assert.equal(state.rows[1].tgl_masuk, '2008-05-12');
    assert.equal(state.rows[2].tgl_masuk, '2024-01-01');
    assert.equal(state.rows[3].tgl_masuk, '2024-01-01');
    assert.equal(state.statuses[2].status, 'warning');
    assert.equal(state.statuses[3].status, 'warning');
});

test('normalizes comma decimals and rejects scores outside the allowed range', async () => {
    const workbook = {
        SheetNames: ['Nilai Uji'],
        Sheets: {
            'Nilai Uji': [
                ['NISN', 'Nama Lengkap', 'Kelas', 'Matematika'],
                ['0000000001', 'Desimal', 'X-1', '85,5'],
                ['0000000002', 'Di Atas', 'X-1', '100,1'],
                ['0000000003', 'Di Bawah', 'X-1', '-0,1']
            ]
        }
    };
    const {state} = await importWorkbook(workbook, 'nilai');

    assert.equal(state.rows[0].mtk, '85.5');
    assert.equal(state.statuses[0].status, 'ready');
    assert.equal(state.statuses[1].status, 'error');
    assert.equal(state.statuses[2].status, 'error');
});

test('combines merged two-row headings and uses cached formula results', async () => {
    const workbook = {
        SheetNames: ['Nilai Uji'],
        Sheets: {
            'Nilai Uji': {
                data: [
                    ['Nilai', '', '', ''],
                    ['NISN', 'Nama Lengkap', 'Kelas', 'Matematika'],
                    ['1234567890', 'Siswa Uji', 'X-1', {f: '40+45.5', v: 85.5}]
                ],
                '!merges': [{s: {r: 0, c: 0}, e: {r: 0, c: 3}}]
            }
        }
    };
    const {state} = await importWorkbook(workbook, 'nilai');

    assert.equal(state.detection.sheets[0].headerRow, 2);
    assert.equal(state.raws[0].heads[3], 'Nilai | Matematika');
    assert.equal(state.rows[0].nisn, '1234567890');
    assert.equal(state.rows[0].mtk, 85.5);
    assert.equal(state.statuses[0].status, 'ready');
});

test('parses long-format subject and score columns with comma decimals', async () => {
    const workbook = {
        SheetNames: ['Nilai Uji'],
        Sheets: {
            'Nilai Uji': [
                ['NISN', 'Nama Lengkap', 'Kelas', 'Mata Pelajaran', 'Nilai'],
                ['0000000001', 'Siswa Uji', 'X-1', 'Matematika', '85,5']
            ]
        }
    };
    const {state} = await importWorkbook(workbook, 'nilai');

    assert.equal(state.rows.length, 1);
    assert.equal(state.rows[0].mapelcol, 'Matematika');
    assert.equal(state.rows[0].nilaicol, '85.5');
    assert.equal(state.statuses[0].status, 'ready');
});
