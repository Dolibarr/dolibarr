document.addEventListener('Dolibarr:Init', function () {

	'use strict';

	// Track triggers already armed, to avoid stacking duplicate click listeners on repeated uiDialog() calls
	const armedTriggers = new WeakSet();

	// Counter used to generate unique ids for non-persistent dialogs without dialogId
	let autoDialogIdCounter = 0;

	// Select2 inside dialogs: inline scripts of AJAX content are not executed (innerHTML), so Dolibarr's
	// ajax_combobox() init never runs. Until form elements are DOM based, apply a generic select2 on selects
	// of freshly injected dialog content. dropdownParent is required: a modal <dialog> lives in the top layer.
	Dolibarr.on('initNewContent', function ({ targets }) {
		if (typeof jQuery === 'undefined' || !jQuery.fn.select2) return;
		targets.forEach(function (root) {
			const dialogEl = root.closest('dialog.dol-dialog');
			if (!dialogEl) return;
			const selects = [
				...(root.matches('select:not(.select2-hidden-accessible)') ? [root] : []),
				...root.querySelectorAll('select:not(.select2-hidden-accessible)')
			];
			selects.forEach(function (el) {
				jQuery(el).select2({ dropdownParent: jQuery(dialogEl) });
			});
		});
	});

	/**
	 * uiDialog — open a <dialog> when a trigger element is clicked.
	 *
	 * @param {string} selector  CSS selector targeting the TRIGGER element(s) that open the dialog.
	 *                           Accepts an ID (single trigger) or a class / any selector matching
	 *                           several elements — all matches are armed in a single call.
	 * @param {Object} param     Dialog options. Beware: selector, dialogId and dialogClass are 3 distinct things:
	 *                           - selector    : targets the triggers (above), it is the only one that accepts a class to match many elements.
	 *                           - dialogId    : id assigned to the generated <dialog> element (NOT a selector). Must be unique;
	 *                                           used by getElementById() for persist, and auto-suffixed with the trigger index
	 *                                           when the selector matches several elements. Mandatory when persist is true.
	 *                           - dialogClass : CSS class(es) applied to the generated <dialog> element.
	 */
	Dolibarr.defineTool('uiDialog', async function(selector, param) {

		// --- Load language for messages ---
		await Dolibarr.tools.langs.load('main');
		await Dolibarr.tools.langs.load('errors');
		await Dolibarr.tools.langs.load('uxdocumentation');

		// --- Default parameters ---
		const defaultParams = {
			dialogClass: 'dol-dialog',		// CSS class(es) applied to the generated <dialog> element
			dialogId: null,					// Id of the generated <dialog> element (NOT a selector) - must be unique, mandatory when persist is true, auto-suffixed with index when the selector matches several triggers
			header: null,    				// Header config: { title, icon, iconColor } — null = no header
			align: 'center',				// Box alignment
			width: null,					// 'xs', 'lg', 'xl', 'xxl' or Override CSS by set xxx as integer or css size like 100vw or 500px
			height: 0,		 				// Box height - Override CSS - Does not work on align right
			closedBy: 'any', 				// 'any' (Escape + backdrop click) | 'closerequest' (Escape only) | 'none' (close buttons only)
			url: null,       				// Ajax url (relative urls are resolved from the current page)
			content: null,   				// Static HTML content (alternative to url)
			animation: true, 				// Enable open/close animations
			persist: true,  				// Keep dialog (and its loaded content) in DOM after close and reuse it as-is on reopen. Note: changing the trigger data-* between two openings does NOT re-fetch (cached on purpose). Set false to rebuild and reload the content on every open.
			footer: null,    				// Footer config: { showCancel, cancelLabel, showSubmit, submitLabel, submitFormId, borderTop }
			onSuccess: null, 				// Callback fired after dialog closes on AJAX form success
			onLoad: null,    				// Callback fired after dialog content is injected (url or content)
			isModal : true,					// Add backdrop
			confirmAction: false,			// Intercept trigger action (href/form) and execute it on confirm (footer submit button or [data-dol-dialog-confirm] element)
		};

		// --- Default header params ---
		const defaultHeader = {
			title    : '',		// Header title text
			icon     : '',		// FontAwesome class (e.g. 'fas fa-user')
			iconColor: '',		// CSS color value (e.g. '#3b89a8')
		};

		// --- Default footer params ---
		const defaultFooter = {
			showCancel      : true,										// Show cancel button
			cancelLabel     : Dolibarr.tools.langs.trans('Cancel'),		// Cancel button label
			moreCancelClass : '',										// Extra CSS classes for cancel button
			showSubmit      : true,										// Show submit button
			submitLabel     : Dolibarr.tools.langs.trans('Validate'),	// Submit button label
			moreSubmitClass : '',										// Extra CSS classes for submit button
			submitFormId    : '',										// Modal form ID
			borderTop       : true,										// Hide or show footer top border (true | false)
			align           : 'right', 									// Buttons alignment ('left' | 'center' | 'right')
		};

		// --- Merge params ---
		param = {...defaultParams, ...param};

		// --- A persistent dialog is found back by its id: a shared default id would mix up the contents of different dialogs ---
		if (!param.dialogId) {
			if (param.persist) {
				console.error('uiDialog: dialogId is mandatory when persist is true (selector: ' + selector + ')');
				return;
			}
			param.dialogId = 'dol-dialog-auto-' + (++autoDialogIdCounter);
		}

		// --- Attach click listener on every matching trigger element ---
		const triggers = document.querySelectorAll(selector);
		if (!triggers.length) return;

		triggers.forEach(function (trigger, triggerIndex) {

			// --- Skip triggers already armed by a previous uiDialog() call (avoid stacking click listeners) ---
			if (armedTriggers.has(trigger)) return;
			armedTriggers.add(trigger);

			// --- Resolve a unique dialog id per trigger (suffix the index when several triggers share the selector) ---
			const dialogId = triggers.length > 1 ? param.dialogId + '-' + triggerIndex : param.dialogId;

			// --- Show the dialog as modal (backdrop) or not ---
			function openDialog(dialogEl) {
				dialogEl.classList.remove('is-closing');
				if (param.isModal) {
					dialogEl.showModal();
				} else {
					dialogEl.show();
				}
			}

			// --- Execute the action the trigger click would have done (link or form submit) ---
			function executeTriggerAction() {
				const href = trigger.getAttribute('href');
				if (href && href !== '#') {
					window.location.href = trigger.href;
				} else if (trigger.form && trigger.type === 'submit') {
					trigger.form.requestSubmit(trigger);
				}
			}

			// --- Open Modal on click
			trigger.addEventListener('click', function (e) {

				// The trigger only opens the dialog: a link or a submit button must not navigate or submit
				e.preventDefault();

				// --- If persist: reuse the existing dialog without reloading (unless its loading failed) ---
				const existingDialog = document.getElementById(dialogId);
				if (param.persist && existingDialog) {
					if (!existingDialog.dataset.dolDialogLoadError) {
						openDialog(existingDialog);
						return;
					}
					existingDialog.remove();
				}

				// --- Collect data attributes from trigger element ---
				const triggerData = {...trigger.dataset};

				// --- Construction of the dialog ---
				let dialogClass = param.dialogClass;
				if (param.align) {
					dialogClass += ' dol-dialog__' + param.align;
				}
				if (!param.animation) {
					dialogClass += ' no-animation';
				}

				let style = '';
				if (param.width) {
					const sizes = ['xs', 'lg', 'xl', 'xxl'];
					if (sizes.includes(param.width)) {
						dialogClass += ` dol-dialog-${param.width}`;
					} else {
						style += 'width:' + (Number.isInteger(param.width) ? param.width + 'px' : param.width) + ';';
					}
				}
				if (param.height && param.align !== 'right') {
					style += 'height:' + (Number.isInteger(param.height) ? param.height + 'px' : param.height) + ';';
				}


				const dialogEl = document.createElement('dialog');
				dialogEl.id = dialogId;
				dialogEl.classList.add(...dialogClass.split(' ').filter(Boolean));
				if (!param.isModal) dialogEl.classList.add('no-backdrop');
				dialogEl.style.cssText = style;

				let dialogHTML = '';
				if (param.header !== null) {
					const h = {...defaultHeader, ...param.header};
					dialogHTML += '<div class="dol-dialog-header">';
					dialogHTML += '<h2 class="dol-dialog-title">';
					if (h.icon) dialogHTML += `<i class="dol-dialog-icon ${h.icon}"${h.iconColor ? ' style="color:' + h.iconColor + '"' : ''}></i>`;
					dialogHTML += h.title;
					dialogHTML += '</h2>';
					dialogHTML += '<button class="dol-dialog-close">&times;</button>';
					dialogHTML += '</div>';
				} else {
					dialogHTML += '<button class="dol-dialog-close">&times;</button>';
				}
				dialogHTML += '<div class="dol-dialog-content"></div>';

				// --- Footer from JS params ---
				if (param.footer !== null) {
					const f = {...defaultFooter, ...param.footer};
					let footerClass = 'dol-dialog-footer';
					if (!f.borderTop) footerClass += ' dol-dialog-footer--borderless';
					if (f.align && f.align !== 'right') footerClass += ' dol-dialog-footer--' + f.align;
					dialogHTML += '<div class="' + footerClass + '">';
					if (f.showCancel) {
						const cancelClass = 'dialog-btn ' + (f.moreCancelClass ? ' ' + f.moreCancelClass : '');
						dialogHTML += '<button type="button" class="' + cancelClass + '" data-dol-dialog-close>' + f.cancelLabel + '</button>';
					}
					if (f.showSubmit) {
						const formAttr = f.submitFormId ? ' form="' + f.submitFormId + '"' : '';
						// Without a form to submit, the submit button confirms the trigger action (confirmAction)
						const confirmAttr = (!f.submitFormId && param.confirmAction) ? ' data-dol-dialog-confirm' : '';
						const submitClass = 'dialog-btn dialog-btn-primary ' + (f.moreSubmitClass ? ' ' + f.moreSubmitClass : '');
						dialogHTML += '<button type="submit"' + formAttr + confirmAttr + ' class="' + submitClass + '">' + f.submitLabel + '</button>';
					}
					dialogHTML += '</div>';
				}

				dialogEl.innerHTML = dialogHTML;


				// --- Insert HTML ---
				document.body.appendChild(dialogEl);

				// --- Static HTML content ---
				if (param.content) {
					const contentEl = dialogEl.querySelector('.dol-dialog-content');
					contentEl.innerHTML = typeof param.content === 'function' ? param.content(triggerData) : param.content;

					const staticFooter = contentEl.querySelector('.dol-dialog-footer');
					if (staticFooter) {
						if (param.footer !== null) {
							staticFooter.remove();
						} else {
							dialogEl.appendChild(staticFooter);
						}
					}

					// Trigger initNewContent on the freshly injected DOM content (header, content and footer)
					Dolibarr.initNewContent(dialogEl, true);

					bindCloseButtons();

					bindConfirmButtons();

					bindAjaxForms();

					// Dispatch event and fire onLoad callback after static content injection.
					dialogEl.dispatchEvent(new CustomEvent('dol-dialog:loaded', { bubbles: true, detail: { dialogId: dialogId, triggerData: triggerData } }));
					if (param.onLoad) param.onLoad(dialogEl, triggerData);
				}

				// --- Load AJAX content if url is provided ---
				if (param.url) {
					const contentEl = dialogEl.querySelector('.dol-dialog-content');
					contentEl.innerHTML = '<div class="dol-dialog-spinner"><span></span></div>';

					const fetchUrl = new URL(param.url, window.location.href);
					Object.entries(triggerData).forEach(function ([key, value]) {
						fetchUrl.searchParams.set(key, value);
					});

					fetch(fetchUrl.toString())
						.then(function (response) {
							if (!response.ok) throw new Error('HTTP ' + response.status);
							return response.text();
						})
						.then(function (html) {
							contentEl.innerHTML = html;

							// Move the AJAX footer only if no JS footer is defined
							const ajaxFooter = contentEl.querySelector('.dol-dialog-footer');
							if (ajaxFooter) {
								if (param.footer !== null) {
									// JS footer has priority: remove the AJAX footer from the content
									ajaxFooter.remove();
								} else {
									const jsFooter = dialogEl.querySelector(':scope > .dol-dialog-footer');
									if (jsFooter) jsFooter.replaceWith(ajaxFooter);
									else dialogEl.appendChild(ajaxFooter);
								}
							}

							// Trigger initNewContent on the freshly injected DOM content (header, content and footer)
							Dolibarr.initNewContent(dialogEl, true);

							// Closing buttons in loaded content
							bindCloseButtons();

							// Confirm buttons in loaded content
							bindConfirmButtons();

							// AJAX forms
							bindAjaxForms();

							dialogEl.dispatchEvent(new CustomEvent('dol-dialog:loaded', { bubbles: true, detail: { dialogId: dialogId, triggerData: triggerData } }));
							if (param.onLoad) param.onLoad(dialogEl, triggerData);
						})
						.catch(function () {
							// Flag the failure so a persistent dialog is rebuilt (and the content fetched again) on next open
							dialogEl.dataset.dolDialogLoadError = '1';
							contentEl.innerHTML = '<span class="dol-dialog-error">' + Dolibarr.tools.langs.trans('ErrorAjaxRequestFailed') + '</span>';
						});
				}

				// --- Show modal ---
				openDialog(dialogEl);


				// --- Close with or without animation, optional callback fired after close ---
				function closeDialog(callback) {
					let closed = false;
					function finishClose() {
						if (closed) return;
						closed = true;
						param.persist ? dialogEl.close() : dialogEl.remove();
						if (typeof callback === "function") callback();
					}

					if (!param.animation) {
						finishClose();
						return;
					}
					dialogEl.classList.add('is-closing');

					// No closing animation defined for this dialog (custom dialogClass or align): close right away
					if (getComputedStyle(dialogEl).animationName === 'none') {
						finishClose();
						return;
					}
					dialogEl.addEventListener('animationend', function onAnimationEnd(e) {
						// Ignore animations of elements inside the dialog (animationend bubbles)
						if (e.target !== dialogEl) return;
						dialogEl.removeEventListener('animationend', onAnimationEnd);
						finishClose();
					});
					// Safety net if animationend never fires (e.g. animation interrupted)
					setTimeout(finishClose, 1000);
				}

				// --- Bind AJAX form submissions (form.dol-dialog-ajax) ---
				function bindAjaxForms() {
					dialogEl.querySelectorAll('form.dol-dialog-ajax').forEach(function (form) {
						form.addEventListener('submit', function (e) {
							e.preventDefault();

							// Hide previous error
							const prevError = form.querySelector('.dol-dialog-form-error');
							if (prevError) prevError.remove();

							fetch(form.getAttribute('action') || window.location.href, {
								method: (form.method || 'post').toUpperCase(),
								body: new FormData(form),
							})
							.then(function (response) { return response.json(); })
							.then(function (data) {
								if (data.result) {
									closeDialog(param.onSuccess ? function() { param.onSuccess(data); } : null);
								} else {
									const errorEl = document.createElement('div');
									errorEl.className = 'dol-dialog-form-error';
									errorEl.textContent = data.msg || Dolibarr.tools.langs.trans('Error');
									form.prepend(errorEl);
								}
							})
							.catch(function () {
								const errorEl = document.createElement('div');
								errorEl.className = 'dol-dialog-form-error';
								errorEl.textContent = Dolibarr.tools.langs.trans('ErrorAjaxRequestFailed');
								form.prepend(errorEl);
							});
						});
					});
				}

				// Bind all [data-dol-dialog-close] buttons once (idempotent, timing-safe)
				function bindCloseButtons() {
					dialogEl.querySelectorAll('[data-dol-dialog-close]').forEach(function (btn) {
						if (btn.dataset.dolDialogCloseBound) return;
						btn.dataset.dolDialogCloseBound = '1';
						btn.addEventListener('click', function () { closeDialog(); });
					});
				}

				// Bind all [data-dol-dialog-confirm] buttons once: close then execute the trigger action (confirmAction)
				function bindConfirmButtons() {
					if (!param.confirmAction) return;
					dialogEl.querySelectorAll('[data-dol-dialog-confirm]').forEach(function (btn) {
						if (btn.dataset.dolDialogConfirmBound) return;
						btn.dataset.dolDialogConfirmBound = '1';
						btn.addEventListener('click', function (e) {
							e.preventDefault();
							closeDialog(executeTriggerAction);
						});
					});
				}

				// Close button (×)
				dialogEl.querySelector('.dol-dialog-close').addEventListener('click', function () { closeDialog(); });

				// Boutons data-dol-dialog-close / data-dol-dialog-confirm du footer JS (sans AJAX)
				bindCloseButtons();
				bindConfirmButtons();

				// Escape key (always intercepted to keep the closing animation, closes only if closedBy allows it)
				dialogEl.addEventListener('cancel', function (e) {
					e.preventDefault();
					if (param.closedBy !== 'none') closeDialog();
				});

				// The browser may still close the dialog natively (e.g. repeated Escape): a non-persistent dialog must not stay in DOM
				dialogEl.addEventListener('close', function () {
					if (!param.persist) dialogEl.remove();
				});

				// Backdrop click: only when the press started on the backdrop too, so that selecting text
				// inside the dialog and releasing the mouse outside does not close it
				if (param.isModal && param.closedBy === 'any') {
					let pressStartedOnBackdrop = false;
					dialogEl.addEventListener('mousedown', function (e) {
						pressStartedOnBackdrop = (e.target === dialogEl);
					});
					dialogEl.addEventListener('click', function (e) {
						if (e.target === dialogEl && pressStartedOnBackdrop) closeDialog();
						pressStartedOnBackdrop = false;
					});
				}

			});

		});
	});
}); // end event listener
