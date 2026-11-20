import assert from 'node:assert/strict';
import {readFileSync} from 'node:fs';
import {dirname, resolve} from 'node:path';
import test from 'node:test';
import vm from 'node:vm';
import {fileURLToPath} from 'node:url';

const directory = dirname(fileURLToPath(import.meta.url));
const view = readFileSync(resolve(directory, '../../resources/views/admin/_kenaikan-mutasi-kenaikan.php'), 'utf8');
const script = view.match(/@push\('scripts'\)\s*<script>\s*([\s\S]*?)\s*<\/script>/)?.[1];

test('mass decision changes retain and re-enable the destination selector', () => {
    assert.ok(script);

    const targetSelect = {style: {display: ''}, disabled: false};
    const statuses = ['tinggal', 'keluar'].map(status => {
        const classNames = new Set([`target-status-${status}`, 'target-status-hidden']);

        return {
            classList: {
                contains: className => classNames.has(className),
                toggle: (className, force) => {
                    if (force) {
                        classNames.add(className);
                    } else {
                        classNames.delete(className);
                    }
                }
            }
        };
    });
    const wrap = {
        querySelector: selector => selector === '.sel-tujuan' ? targetSelect : null,
        querySelectorAll: selector => selector === '.target-status' ? statuses : []
    };
    const decisionSelect = {
        options: [{value: 'Naik'}, {value: 'Tinggal'}, {value: 'Keluar'}],
        selectedIndex: 0,
        getAttribute: name => name === 'data-id' ? '42' : null,
        get value() {
            return this.options[this.selectedIndex].value;
        }
    };
    const document = {
        querySelectorAll: selector => selector === '.sel-keputusan' ? [decisionSelect] : [],
        getElementById: id => id === 'target-wrap-42' ? wrap : null
    };
    const context = {document};

    vm.runInNewContext(script, context);
    context.setSemuaHasil('Tinggal');

    assert.equal(targetSelect.style.display, 'none');
    assert.equal(targetSelect.disabled, true);
    assert.equal(statuses[0].classList.contains('target-status-hidden'), false);

    context.setSemuaHasil('Naik');

    assert.equal(targetSelect.style.display, '');
    assert.equal(targetSelect.disabled, false);
    assert.equal(wrap.querySelector('.sel-tujuan'), targetSelect);
    assert.equal(statuses[0].classList.contains('target-status-hidden'), true);
});
