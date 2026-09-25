document.addEventListener('Dolibarr:Init', function () {

	'use strict';

	// Track triggers already armed, to avoid stacking duplicate click listeners on repeated uiDialog() calls
	const armedTriggers = new WeakSet();

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
	 *                                           when the selector matches several elements.
	 *                           - dialogClass : CSS class(es) applied to the generated <dialog> element.
	 */
	Dolibarr.defineTool('uiDialog', async function(selector, param) {

		// --- Load language for messages ---
		await Dolibarr.tools.langs.load('main');
		await Dolibarr.tools.langs.load('uxdocumentation');

		// --- Default parameters ---
		const defaultParams = {
			dialogClass: 'dol-dialog',		// CSS class(es) applied to the generated <dialog> element
			dialogId: 'dol-dialog-id',		// Id of the generated <dialog> element (NOT a selector) - must be unique, auto-suffixed with index when the selector matches several triggers
			header: null,    				// Header config: { title, icon, iconColor } — null = no header
			align: 'center',				// Box alignment
			width: null,					// 'xs', 'lg', 'xl', 'xxl' or Override CSS by set xxx as integer or css size like 100vw or 500px
			height: 0,		 				// Box height - Override CSS - Does not work on align right
			closedBy: 'any', 				// 'any' | 'closerequest' | 'none'
			url: null,       				// Ajax url
			content: null,   				// Static HTML content (alternative to url)
			animation: true, 				// Enable open/close animations
			persist: true,  				// Keep dialog (and its loaded content) in DOM after close and reuse it as-is on reopen. Note: changing the trigger data-* between two openings does NOT re-fetch (cached on purpose). Set false to rebuild and reload the content on every open.
			footer: null,    				// Footer config: { showCancel, cancelLabel, showSubmit, submitLabel, submitFormId, borderTop }
			onSuccess: null, 				// Callback fired after dialog closes on AJAX form success
			onLoad: null,    				// Callback fired after dialog content is injected (url or content)
			isModal : true,					// Add backdrop
			confirmAction: false,			// Intercept trigger action (href/form) and execute it on confirm
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

		// --- Attach click listener on every matching trigger element ---
		const triggers = document.querySelectorAll(selector);
		if (!triggers.length) return;

		triggers.forEach(function (trigger, triggerIndex) {

			// --- Skip triggers already armed by a previous uiDialog() call (avoid stacking click listeners) ---
			if (armedTriggers.has(trigger)) return;
			armedTriggers.add(trigger);

			// --- Resolve a unique dialog id per trigger (suffix the index when several triggers share the selector) ---
			const dialogId = triggers.length > 1 ? param.dialogId + '-' + triggerIndex : param.dialogId;

			// --- Open Modal on click
			trigger.addEventListener('click', function () {

				// --- If persist: reuse the existing dialog without reloading ---
				const existingDialog = document.getElementById(dialogId);
				if (param.persist && existingDialog) {
					existingDialog.classList.remove('is-closing');
					existingDialog.showModal();
					return;
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
						const submitClass = 'dialog-btn dialog-btn-primary ' + (f.moreSubmitClass ? ' ' + f.moreSubmitClass : '');
						dialogHTML += '<button type="submit"' + formAttr + ' class="' + submitClass + '">' + f.submitLabel + '</button>';
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

					// Trigger initNewContent manually on the freshly injected DOM content
					Dolibarr.initNewContent(contentEl, true);

					const staticFooter = contentEl.querySelector('.dol-dialog-footer');
					if (staticFooter) {
						if (param.footer !== null) {
							staticFooter.remove();
						} else {
							dialogEl.appendChild(staticFooter);
						}
					}

					bindCloseButtons();

					bindAjaxForms();

					// Dispatch event and fire onLoad callback after static content injection.
					dialogEl.dispatchEvent(new CustomEvent('dol-dialog:loaded', { bubbles: true, detail: { dialogId: dialogId, triggerData: triggerData } }));
					if (param.onLoad) param.onLoad(dialogEl, triggerData);
				}

				// --- Load AJAX content if url is provided ---
				if (param.url) {
					const contentEl = dialogEl.querySelector('.dol-dialog-content');
					contentEl.innerHTML = '<div class="dol-dialog-spinner"><span></span></div>';

					const fetchUrl = new URL(param.url, window.location.origin);
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

							// Select 2
							if (typeof jQuery !== 'undefined' && jQuery.fn.select2) {
								var modal = dialogEl;
								var selects = modal.querySelectorAll('select:not(.select2-hidden-accessible)');

								selects.forEach(function(el) {
									jQuery(el).select2({
										dropdownParent: jQuery(modal)
									});
								});
            				}

							// Trigger initNewContent manually on the freshly injected DOM content
							Dolibarr.initNewContent(contentEl, true);

							// Move the AJAX footer only if no JS footer is defined
							const ajaxFooter = contentEl.querySelector('.dol-dialog-footer');
							if (ajaxFooter) {
								if (param.footer !== null) {
									// Footer JS prioritaire : supprimer le footer AJAX du contenu
									ajaxFooter.remove();
								} else {
									const jsFooter = dialogEl.querySelector(':scope > .dol-dialog-footer');
									if (jsFooter) jsFooter.replaceWith(ajaxFooter);
									else dialogEl.appendChild(ajaxFooter);
								}
							}

							// Closing buttons in loaded content
							bindCloseButtons();

							// AJAX forms
							bindAjaxForms();

							dialogEl.dispatchEvent(new CustomEvent('dol-dialog:loaded', { bubbles: true, detail: { dialogId: dialogId, triggerData: triggerData } }));
							if (param.onLoad) param.onLoad(dialogEl, triggerData);
						})
						.catch(function () { contentEl.innerHTML = '<span class="dol-dialog-error">Erreur lors du chargement.</span>'; });
				}

				// --- Show modal ---
				if (param.isModal) {
					dialogEl.showModal();
				} else {
					dialogEl.show();
				}


				// --- Close with or without animation, optional callback fired after close ---
				function closeDialog(callback) {
					if (!param.animation) {
						param.persist ? dialogEl.close() : dialogEl.remove();
						if (typeof callback === "function") callback();
						return;
					}
					dialogEl.classList.add('is-closing');
					dialogEl.addEventListener('animationend', function () {
						param.persist ? dialogEl.close() : dialogEl.remove();
						if (typeof callback === "function") callback();
					}, { once: true });
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
								errorEl.textContent = Dolibarr.tools.langs.trans('ErrorAjaxForm');
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

				// Close button (×)
				dialogEl.querySelector('.dol-dialog-close').addEventListener('click', function () { closeDialog(); });

				// Boutons data-dol-dialog-close du footer JS (sans AJAX)
				bindCloseButtons();

				// Escape key
				dialogEl.addEventListener('cancel', function (e) {
					e.preventDefault();
					closeDialog();
				});

				// Backdrop click
				if (param.isModal) {
					dialogEl.addEventListener('click', function (e) {
						if (e.target === dialogEl) closeDialog();
					});
				}

			});

		});
	});
}); // end event listener
