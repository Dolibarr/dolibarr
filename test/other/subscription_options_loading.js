/* Copyright (C) 2026 Marcel
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

// Run with node. Exercise the controller's actual inline script without a browser or application data.
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const source = fs.readFileSync(path.join(__dirname, '../../htdocs/adherents/list.php'), 'utf8');
const start = source.indexOf('(function () {', source.indexOf("'confirm_subscription_options', $formquestion"));
assert.ok(start >= 0);
const end = source.indexOf('})();', start);
assert.ok(end > start);
const translation = "\\''.dol_escape_js($langs->transnoentities(\"InProgress\")).'\\'";
const script = source.slice(start, end + 5);
assert.ok(script.includes(translation), 'Use the escaped existing InProgress translation');
const javascript = script.replace(translation, JSON.stringify('In progress'));
let scenarios = 0;

function run(options = {}) {
	const handlers = {};
	const timers = [];
	const messages = [];
	let unblocked = 0;
	let prevented = !!options.prevented;
	const state = { submitting: !!options.synthetic };
	const form = {
		querySelector: () => options.wrongAction ? null : {},
		elements: { namedItem: () => ({ value: options.no ? 'no' : 'yes' }) },
		checkValidity: () => !options.invalid
	};
	const invoice = { form, checked: true, addEventListener: () => {} };
	const thirdparty = { disabled: false, checked: false };
	const context = {
		document: { getElementById: id => id === 'subscriptioninvoice' ? invoice : thirdparty },
		window: { addEventListener: (name, callback) => { handlers[name] = callback; } },
		setTimeout: (callback, delay) => { assert.equal(delay, 0); timers.push(callback); },
		dolBlockUI: message => messages.push(message),
		dolUnblockUI: () => { unblocked++; },
		jQuery: target => {
			assert.equal(target, form);
			return {
				on: (name, callback) => { assert.equal(name, 'submit.subscriptionBulk'); handlers.submit = callback; },
				data: name => { assert.equal(name, 'formconfirm-submitting'); return state.submitting; }
			};
		}
	};
	if (options.noJQuery) delete context.jQuery;
	if (options.noLoader) delete context.dolBlockUI;
	vm.runInNewContext(javascript, context);
	const event = { isDefaultPrevented: () => prevented };
	if (!options.synthetic) {
		event.originalEvent = {
			submitter: options.noSubmitter ? null : {
				name: options.button || '',
				classList: { contains: name => !options.button && name === 'confirmvalidatebutton' }
			}
		};
	}
	if (!options.noSubmit && handlers.submit) handlers.submit(event);
	if (options.cancelLater) prevented = true;
	timers.forEach(callback => callback());
	if (options.back && handlers.pageshow) handlers.pageshow({ persisted: true });
	assert.equal(messages.length, options.expected || 0);
	if (messages.length) assert.equal(messages[0], 'In progress');
	assert.equal(unblocked, options.back && options.expected ? 1 : 0);
	assert.equal(form.elements.namedItem('confirm').value, options.no ? 'no' : 'yes');
	scenarios++;
}

run({ synthetic: true, expected: 1 }); // Core formconfirm uses jQuery form.submit().
run({ expected: 1 }); // Native submission from the final validation button.
run({ synthetic: true, no: true });
run({ no: true });
run({ synthetic: true, invalid: true });
run({ invalid: true });
run({ synthetic: true, wrongAction: true });
run({ synthetic: true, prevented: true });
run({ synthetic: true, cancelLater: true });
run({ synthetic: true, noLoader: true });
run({ noJQuery: true });
run({ noSubmitter: true });
run({ noSubmit: true }); // Editing inputs/options does not start processing.
for (const button of ['refreshsubscriptions', 'applysubscriptiondate', 'applysubscriptioncalendar', 'recalculatesubscription', 'cancel', 'button_search', 'button_removefilter']) {
	run({ button }); // Even with confirm=yes, auxiliary controls must not show the loader.
}
run({ synthetic: true, expected: 1, back: true });
run({ no: true, back: true });
console.log(`${scenarios} loading-indicator scenarios passed. No browser, database or mail transport used.`);
