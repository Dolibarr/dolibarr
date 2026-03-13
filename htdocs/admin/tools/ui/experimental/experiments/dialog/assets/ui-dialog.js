document.addEventListener('Dolibarr:Init', function () {

	'use strict';

	Dolibarr.defineTool('uiDialog', async function(selector, param) {

		// --- Load language for messages ---
		await Dolibarr.tools.langs.load('main');
		await Dolibarr.tools.langs.load('uxdocumentation');

		// --- Default parameters ---
		const defaultParams = {
			dialogClass: 'dol-dialog',		// Dialog Class
			dialogId: 'dol-dialog-id',		// Dialog ID, should be unique
			title: '',						// Title of dialog
			icon: '',						// FontAwesome Class (e.g 'fas fa-user')
			iconColor: '',  				// CSS color value (e.g #3b89a8)
			align: 'center',				// Box alignment
			width: null,					// 'xs', 'lg', 'xl', 'xxl' or Override CSS by set xxx as interger or css size like 100vw or 500px
			height: 0,		 				// Box height - Overrride CSS - Do not work on align right
			closedBy: 'any', 				// 'any' | 'closerequest' | 'none'
			url: null,       				// Ajax url
			content: null,   				// Static HTML content (alternative to url)
			animation: true, 				// Enable open/close animations
			persist: true,  				// Keep dialog in DOM after close (avoid reloading content)
			footer: null,    				// Footer config: { showCancel, cancelLabel, showSubmit, submitLabel, submitFormId, borderTop }
			onSuccess: null, 				// Callback fired after dialog closes on AJAX form success
			onLoad: null,    				// Callback fired after dialog content is injected (url or content)
			isModal : true					// Add backdrop
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

		// --- Attach click listener on the trigger element ---
		const trigger = document.querySelector(selector);
		if (!trigger) return;

		// --- Open Modal on click
		trigger.addEventListener('click', function () {

			// --- If persist: reuse the existing dialog without reloading ---
			const existingDialog = document.getElementById(param.dialogId);
			if (param.persist && existingDialog) {
				existingDialog.classList.remove('is-closing');
				existingDialog.showModal();
				return;
			}

			// --- Collect data attributes from trigger element ---
			const triggerData = {...trigger.dataset};

			// --- Construction of the dialog ---
			let dialogClass = defaultParams.dialogClass;
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
			dialogEl.id = param.dialogId;
			dialogEl.classList.add(...dialogClass.split(' '));
			if (!param.isModal) dialogEl.classList.add('no-backdrop');
			dialogEl.cssText = style;

			let dialogHTML = '';
			if (param.title) {
				dialogHTML += `
                    <div class="dol-dialog-header">
                        <h2 class="dol-dialog-title">
                            ${param.icon ? `<i class="dol-dialog-icon ${param.icon}" ${param.iconColor ? ' style="color:' + param.iconColor + '"' : ''} ></i>` : ''}
							${param.title}
                        </h2>
                        <button class="dol-dialog-close">&times;</button>
                    </div>`;
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

				const staticFooter = contentEl.querySelector('.dol-dialog-footer');
				if (staticFooter) {
					if (param.footer !== null) {
						staticFooter.remove();
					} else {
						dialogEl.appendChild(staticFooter);
					}
				}

				dialogEl.querySelectorAll('[data-dol-dialog-close]').forEach(function (btn) {
					btn.addEventListener('click', closeDialog);
				});

				bindAjaxForms();

				// Dispatch event and fire onLoad callback after static content injection.
				dialogEl.dispatchEvent(new CustomEvent('dol-dialog:loaded', { bubbles: true, detail: { dialogId: param.dialogId, triggerData: triggerData } }));
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
					.then(function (response) { return response.text(); })
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
						dialogEl.querySelectorAll('[data-dol-dialog-close]').forEach(function (btn) {
							btn.addEventListener('click', closeDialog);
						});

						// AJAX forms
						bindAjaxForms();

						dialogEl.dispatchEvent(new CustomEvent('dol-dialog:loaded', { bubbles: true, detail: { dialogId: param.dialogId, triggerData: triggerData } }));
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
							if (data.success) {
								closeDialog(param.onSuccess ? function() { param.onSuccess(data); } : null);
							} else {
								const errorEl = document.createElement('div');
								errorEl.className = 'dol-dialog-form-error';
								errorEl.textContent = data.message || Dolibarr.tools.langs.trans('Error');
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

			// Close button (×)
			dialogEl.querySelector('.dol-dialog-close').addEventListener('click', closeDialog);

			// Boutons data-dol-dialog-close du footer JS (sans AJAX)
			dialogEl.querySelectorAll('[data-dol-dialog-close]').forEach(function (btn) {
				btn.addEventListener('click', closeDialog);
			});

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
}); // end event listener
