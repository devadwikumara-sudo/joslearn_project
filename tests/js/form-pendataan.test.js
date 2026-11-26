import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {dirname, resolve} from 'node:path';
import test from 'node:test';
import vm from 'node:vm';
import {fileURLToPath} from 'node:url';

const directory = dirname(fileURLToPath(import.meta.url));
const view = readFileSync(resolve(directory, '../../resources/views/admin/_form-pendataan.php'), 'utf8');
const script = view.match(/@push\('scripts'\)\s*<script>\s*([\s\S]*?)\s*<\/script>/)?.[1];
const validationSetup = script?.match(/function setupStudentFormValidation\(form, panes, showTab\) \{[\s\S]*?\n\}/)?.[0];

test('invalid fields in hidden tabs are shown before form submission is blocked', () => {
    assert.ok(validationSetup);
    assert.match(view, /id="form-pendataan-siswa"[^>]*novalidate/);
    assert.match(script, /setupStudentFormValidation\(studentForm, panes, showTab\)/);

    const pane = {};
    const invalidField = {
        disabled: false,
        checkValidity: () => false,
        closest: () => pane,
        reportValidity: () => {
            invalidField.reported = true;
        }
    };
    const form = {
        elements: [invalidField],
        addEventListener: (eventName, listener) => {
            form.submitListener = listener;
        }
    };
    const context = {};
    let activePane = null;
    let wasPrevented = false;

    vm.runInNewContext(validationSetup, context);
    context.setupStudentFormValidation(form, [pane], index => {
        activePane = index;
    });
    form.submitListener({
        preventDefault: () => {
            wasPrevented = true;
        }
    });

    assert.equal(wasPrevented, true);
    assert.equal(activePane, 0);
    assert.equal(invalidField.reported, true);
});

test('valid student form submissions are not interrupted', () => {
    assert.ok(validationSetup);

    const form = {
        elements: [{disabled: false, checkValidity: () => true}],
        addEventListener: (eventName, listener) => {
            form.submitListener = listener;
        }
    };
    const context = {};
    let wasPrevented = false;

    vm.runInNewContext(validationSetup, context);
    context.setupStudentFormValidation(form, [], () => {});
    form.submitListener({
        preventDefault: () => {
            wasPrevented = true;
        }
    });

    assert.equal(wasPrevented, false);
});
