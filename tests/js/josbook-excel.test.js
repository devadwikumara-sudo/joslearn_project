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

function importWorkbook(workbook, expectedType = 'siswa') {
    const alerts = [];
    const window = {toast() {}};
    const preview = {innerHTML: ''};
    let payload = null;
    const csrfMeta = {getAttribute: () => 'csrf-test'};
    const endpointMeta = {getAttribute: () => '/test/save-batch'};
    const baseMeta = {getAttribute: () => '/test'};
    const document = {
        getElementById(id) {
            return id === 'josbook-import-preview-area' ? preview : null;
        },
        querySelector(selector) {
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
                return sheet;
            }
        }
    };
    window.XLSX = XLSX;
    window.fetch = async (endpoint, options) => {
        payload = JSON.parse(options.body);
        return {
            headers: {get: () => 'application/json'},
            json: async () => ({success: true, message: 'Impor selesai'})
        };
    };

    class FileReaderStub {
        readAsArrayBuffer() {
            this.onload({target: {result: null}});
        }
    }

    vm.runInNewContext(script, {
        window,
        document,
        XLSX,
        fetch: window.fetch,
        Blob,
        URL,
        FileReader: FileReaderStub,
        alert: message => alerts.push(message)
    });
    window.smartFile({target: {files: [{name: 'fixture.xlsx'}]}}, expectedType);

    return {
        state: JSON.parse(JSON.stringify(window.JosBookState.imp)),
        alerts,
        preview,
        window,
        getPayload: () => payload
    };
}

test('reads student records across four sheet and header variations', () => {
    fixtures.forEach((fixtureName, index) => {
        const fixture = JSON.parse(readFileSync(resolve(directory, '../Fixtures', fixtureName), 'utf8'));
        const {state, alerts} = importWorkbook(workbookFromFixture(fixture));
        const keyFields = state.rows.map(({nisn, nama, nis, kelas}) => ({nisn, nama, nis, kelas}));

        assert.deepEqual(alerts, [], fixtureName);
        assert.deepEqual(keyFields, expectedStudents, fixtureName);
        assert.equal(state.rows.length, 2, fixtureName);
        assert.equal(state.detection.sheet, fixture.sheetName, fixtureName);
        assert.equal(state.detection.legacyFallback, index === 3, fixtureName);
        assert.equal(state.detection.headerRow, index === 0 ? 2 : index === 1 ? 7 : index === 2 ? 1 : 5, fixtureName);
    });
});

test('maps repeated parent and guardian headings by family group', () => {
    const fixture = JSON.parse(readFileSync(resolve(directory, '../Fixtures', fixtures[2]), 'utf8'));
    const {state} = importWorkbook(workbookFromFixture(fixture));

    assert.equal(state.rows[0].agama_ayah, 'Islam');
    assert.equal(state.rows[0].kerja_ayah, 'Petani');
    assert.equal(state.rows[0].agama_ibu, 'Kristen');
    assert.equal(state.rows[0].kerja_ibu, 'Guru');
    assert.equal(state.rows[0].agama_wali, 'Hindu');
    assert.equal(state.rows[0].kerja_wali, 'Dokter');
    assert.equal(state.detection.recognized.length, 13);
    assert.equal(state.detection.unknown.length, 0);
});

test('reports missing required student headings and allows correcting the mapping', () => {
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

    workbooks.forEach((workbook, index) => {
        const {state, alerts, window} = importWorkbook(workbook);

        assert.deepEqual(alerts, []);
        assert.equal(state.rows.length, 0);
        assert.equal(state.detection.headerDetected, true);
        assert.ok(state.detection.missingRequired.length);
        assert.match(state.mappingIssues[0], new RegExp(expectedMissingHeadings[index]));
        window.changeStudentColumnMap(1, index === 0 ? 'nisn' : 'nama');
        assert.equal(window.JosBookState.imp.mappingIssues.length, 0);
        assert.equal(window.JosBookState.imp.rows.length, 1);
    });
});

test('selects the sheet with the strongest matching header row', () => {
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
    const {state, alerts} = importWorkbook(workbook);

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
    const {state, preview, window, getPayload} = importWorkbook(workbook, 'nilai');

    assert.equal(state.rows.length, 1);
    assert.equal(window.JosBookState.imp.statuses[0].status, 'error');
    assert.match(preview.innerHTML, /Terdeteksi: Data Nilai Rapor/);
    assert.match(preview.innerHTML, /Nilai Matematika harus angka 0–100/);

    window.editImportCell({dataset: {row: '0', field: 'mtk'}, textContent: '85'});
    assert.equal(window.JosBookState.imp.statuses[0].status, 'ready');
    assert.match(preview.innerHTML, />Siap</);

    await window.executeBatchImport();
    assert.equal(getPayload().rows.length, 1);
    assert.equal(getPayload().rows[0].mtk, '85');
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
    const {window, getPayload} = importWorkbook(workbook, 'nilai');

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
                ['NISN', 'Nama Lengkap', 'NIK'],
                ['0000000001', 'Siswa Uji', '12345']
            ]
        }
    };
    const {alerts, window, getPayload} = importWorkbook(workbook);

    assert.equal(window.JosBookState.imp.statuses[0].status, 'warning');
    await window.executeBatchImport();
    assert.equal(getPayload(), null);
    assert.match(alerts.at(-1), /Konfirmasikan peringatan/);

    window.confirmImportWarnings(true);
    await window.executeBatchImport();
    assert.equal(getPayload().rows.length, 1);
});
