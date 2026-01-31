/**
 * IndexAdjustmentWizard - AJAX Wizard Controller
 *
 * Handles step navigation, AJAX calls, DOM manipulation
 * for both "contract" and "lines" wizard modes.
 *
 * @file       js/indexadjustment_wizard.js
 * @ingroup    indexadjustment
 * @author     Florian Hödl <florian@hoedl.co>
 */

var IndexAdjustmentWizard = function(config) {
	this.config = jQuery.extend({
		ajaxUrl: '',
		token: '',
		mode: 'contract',  // "contract" or "lines"
		cardUrl: '',
		listUrl: '',
		canExecute: false,
		currencySymbol: '',
		langs: {}
	}, config);

	this.state = {
		currentStep: 1,
		adjustmentId: 0,
		adjustmentRef: '',
		adjustmentPercent: 0,
		fkSoc: 0,
		contracts: [],
		lines: {},         // For lines mode: {contractId: [lines...]}
		linesLoaded: {},   // Track which contracts have lines loaded
		preview: null,
		executing: false   // Prevent double-submission
	};

	this.init();
};

IndexAdjustmentWizard.prototype = {

	init: function() {
		this.bindEvents();
	},

	// ------------------------------------------
	// AJAX helper
	// ------------------------------------------
	ajax: function(wizardaction, data, successCb, errorCb) {
		var self = this;
		data = data || {};
		data.wizardaction = wizardaction;
		data.token = this.config.token;

		jQuery.ajax({
			url: this.config.ajaxUrl,
			type: 'POST',
			data: data,
			dataType: 'json',
			timeout: 60000, // 60 seconds for long operations
			success: function(r) {
				if (r.success) {
					if (successCb) successCb(r);
				} else {
					self.showError(r.message || self.config.langs.AjaxError);
					if (errorCb) errorCb(r);
				}
			},
			error: function(xhr, status) {
				self.hideLoading();
				var msg = status === 'timeout' ? 'Request timed out. Please try again.' : self.config.langs.AjaxError;
				self.showError(msg);
				if (errorCb) errorCb({success: false, message: msg});
			}
		});
	},

	// ------------------------------------------
	// Event bindings
	// ------------------------------------------
	bindEvents: function() {
		var self = this;

		// Step 1 -> Step 2: Next button
		jQuery('#btn-next-step1').on('click', function(e) {
			e.preventDefault();
			self.step1ToStep2();
		});

		// Step 2 -> Step 3: Preview button
		jQuery('#btn-preview').on('click', function(e) {
			e.preventDefault();
			self.step2ToStep3();
		});

		// Step 3: Execute button
		jQuery('#btn-execute').on('click', function(e) {
			e.preventDefault();
			self.executeAdjustment();
		});

		// Back buttons
		jQuery('#btn-back-step2').on('click', function(e) {
			e.preventDefault();
			self.goToStep(1);
		});
		jQuery('#btn-back-step3').on('click', function(e) {
			e.preventDefault();
			self.goToStep(2);
		});

		// Select All / Deselect All
		jQuery('#btn-select-all').on('click', function(e) {
			e.preventDefault();
			self.selectAll(true);
		});
		jQuery('#btn-deselect-all').on('click', function(e) {
			e.preventDefault();
			self.selectAll(false);
		});
	},

	// ------------------------------------------
	// Step navigation
	// ------------------------------------------
	goToStep: function(step) {
		jQuery('.wizard-step-content').hide();
		jQuery('#wizard-step-' + step).show();
		this.state.currentStep = step;
		this.updateStepIndicator(step);
	},

	updateStepIndicator: function(currentStep) {
		jQuery('.wizard-step-badge').each(function() {
			var stepNum = jQuery(this).data('step');
			jQuery(this).removeClass('badge-status4 badge-primary badge-secondary');
			if (stepNum < currentStep) {
				jQuery(this).addClass('badge-status4');
				jQuery(this).find('.step-icon').html('<span class="fas fa-check"></span> ');
			} else if (stepNum === currentStep) {
				jQuery(this).addClass('badge-primary');
				jQuery(this).find('.step-icon').html('');
			} else {
				jQuery(this).addClass('badge-secondary');
				jQuery(this).find('.step-icon').html('');
			}
		});
	},

	// ------------------------------------------
	// Step 1 -> Step 2
	// ------------------------------------------
	step1ToStep2: function() {
		var self = this;

		// Validate form
		var label = jQuery('#wizard-label').val().trim();
		var percent = jQuery('#wizard-percent').val().trim();

		if (!label) {
			this.showError(this.config.langs.ValidationErrorLabel);
			return;
		}
		if (!percent || isNaN(parseFloat(percent.replace(',', '.')))) {
			this.showError(this.config.langs.ValidationErrorPercent);
			return;
		}

		this.clearError();
		this.setButtonLoading('#btn-next-step1', this.config.langs.CreatingAdjustment);

		// Collect form date fields (Dolibarr selectDate generates fields named prefix+day/month/year)
		var data = {
			label: label,
			adjustment_percent: percent,
			adjustment_datemonth: jQuery('[name="adjustment_datemonth"]').val() || jQuery('#adjustment_datemonth').val(),
			adjustment_dateday: jQuery('[name="adjustment_dateday"]').val() || jQuery('#adjustment_dateday').val(),
			adjustment_dateyear: jQuery('[name="adjustment_dateyear"]').val() || jQuery('#adjustment_dateyear').val(),
			fk_soc: jQuery('[name="fk_soc"]').val() || jQuery('#fk_soc').val() || 0
		};

		this.ajax('create', data, function(r) {
			console.log('Create response:', r);
			self.state.adjustmentId = r.id;
			self.state.adjustmentRef = r.ref;
			self.state.adjustmentPercent = parseFloat(percent.replace(',', '.'));
			self.state.fkSoc = data.fk_soc;
			console.log('State after create:', self.state);

			// Now fetch contracts
			self.fetchContracts();
		}, function() {
			self.resetButton('#btn-next-step1');
		});
	},

	fetchContracts: function() {
		var self = this;

		this.ajax('fetch_contracts', {fk_soc: this.state.fkSoc}, function(r) {
			self.state.contracts = r.contracts;
			self.renderContractTable(r.contracts);
			self.resetButton('#btn-next-step1');
			self.goToStep(2);
		}, function() {
			self.resetButton('#btn-next-step1');
		});
	},

	renderContractTable: function(contracts) {
		var self = this;
		var $container = jQuery('#wizard-contracts-container');
		$container.empty();

		if (!contracts || contracts.length === 0) {
			$container.html('<div class="warning">' + this.config.langs.NoContractsFound + '</div>');
			jQuery('#btn-preview').hide();
			return;
		}

		jQuery('#btn-preview').show();

		var html = '<table class="noborder centpercent">';
		html += '<tr class="liste_titre">';

		if (this.config.mode === 'lines') {
			html += '<th width="30"></th>';
		}

		html += '<th class="center" width="30"><input type="checkbox" id="wizard-checkall" checked></th>';
		html += '<th>' + this.config.langs.Contract + '</th>';
		html += '<th>' + this.config.langs.ThirdParty + '</th>';
		html += '<th>' + this.config.langs.RefCustomer + '</th>';
		html += '<th class="right">' + this.config.langs.ActiveLines + '</th>';
		html += '</tr>';

		for (var i = 0; i < contracts.length; i++) {
			var c = contracts[i];
			html += '<tr class="oddeven">';

			if (this.config.mode === 'lines') {
				html += '<td class="center"><a href="#" class="toggle-contract" data-contract="' + c.id + '">';
				html += '<span class="fas fa-chevron-right expand-icon"></span></a></td>';
			}

			html += '<td class="center"><input type="checkbox" class="wizard-contract-cb" data-contract="' + c.id + '" value="' + c.id + '" checked></td>';
			html += '<td><a href="' + c.url + '" target="_blank">' + self.escapeHtml(c.ref) + '</a></td>';
			html += '<td>' + self.escapeHtml(c.socname) + '</td>';
			html += '<td>' + self.escapeHtml(c.ref_customer || '') + '</td>';
			html += '<td class="right">' + c.active_lines + '</td>';
			html += '</tr>';

			if (this.config.mode === 'lines') {
				html += '<tr class="contract-lines-row" id="contract-lines-' + c.id + '" style="display:none;">';
				html += '<td colspan="6" class="paddingleft">';
				html += '<div class="contract-lines-content" id="lines-content-' + c.id + '"></div>';
				html += '</td></tr>';
			}
		}

		html += '</table>';
		$container.html(html);

		// Update counter
		this.updateSelectionCount();

		// Bind header checkbox
		jQuery('#wizard-checkall').on('change', function() {
			var checked = jQuery(this).prop('checked');
			jQuery('.wizard-contract-cb').prop('checked', checked);
			if (self.config.mode === 'lines') {
				jQuery('.wizard-line-cb').prop('checked', checked);
			}
			self.updateSelectionCount();
		});

		// Bind contract checkboxes
		jQuery('.wizard-contract-cb').on('change', function() {
			var contractId = jQuery(this).data('contract');
			if (self.config.mode === 'lines') {
				jQuery('.wizard-line-cb[data-contract="' + contractId + '"]').prop('checked', jQuery(this).prop('checked'));
			}
			self.updateSelectionCount();
		});

		// Bind expand/collapse for lines mode
		if (this.config.mode === 'lines') {
			jQuery('.toggle-contract').on('click', function(e) {
				e.preventDefault();
				var contractId = jQuery(this).data('contract');
				self.toggleContractLines(contractId, jQuery(this));
			});
		}
	},

	toggleContractLines: function(contractId, $toggle) {
		var self = this;
		var $row = jQuery('#contract-lines-' + contractId);
		var $icon = $toggle.find('.expand-icon');

		if ($row.is(':visible')) {
			$row.hide();
			$icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
			return;
		}

		// If lines already loaded, just show
		if (this.state.linesLoaded[contractId]) {
			$row.show();
			$icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
			return;
		}

		// Show loading spinner
		$icon.removeClass('fa-chevron-right').addClass('fa-spinner fa-spin');

		this.ajax('fetch_lines', {contract_id: contractId}, function(r) {
			self.state.lines[contractId] = r.lines;
			self.state.linesLoaded[contractId] = true;
			self.renderContractLines(contractId, r.lines);
			$row.show();
			$icon.removeClass('fa-spinner fa-spin').addClass('fa-chevron-down');
		}, function() {
			$icon.removeClass('fa-spinner fa-spin').addClass('fa-chevron-right');
		});
	},

	renderContractLines: function(contractId, lines) {
		var self = this;
		var $content = jQuery('#lines-content-' + contractId);
		var contractChecked = jQuery('.wizard-contract-cb[data-contract="' + contractId + '"]').prop('checked');

		var html = '<table class="noborder centpercent">';
		html += '<tr class="liste_titre">';
		html += '<th width="30"></th>';
		html += '<th>' + this.config.langs.Product + '</th>';
		html += '<th class="right">' + this.config.langs.UnitPrice + '</th>';
		html += '<th class="right">' + this.config.langs.Qty + '</th>';
		html += '</tr>';

		for (var i = 0; i < lines.length; i++) {
			var l = lines[i];
			html += '<tr class="oddeven">';
			html += '<td class="center"><input type="checkbox" class="wizard-line-cb" data-contract="' + contractId + '" data-line="' + l.id + '" ' + (contractChecked ? 'checked' : '') + '></td>';
			html += '<td><span class="fas fa-angle-right opacitymedium paddingright"></span> ' + self.escapeHtml(l.product_label || l.product_ref || '-') + '</td>';
			html += '<td class="right">' + self.formatPrice(l.subprice) + '</td>';
			html += '<td class="right">' + l.qty + '</td>';
			html += '</tr>';
		}

		html += '</table>';
		$content.html(html);

		// Bind line checkboxes
		$content.find('.wizard-line-cb').on('change', function() {
			self.updateContractCheckbox(contractId);
			self.updateSelectionCount();
		});
	},

	updateContractCheckbox: function(contractId) {
		var $lineCbs = jQuery('.wizard-line-cb[data-contract="' + contractId + '"]');
		var $contractCb = jQuery('.wizard-contract-cb[data-contract="' + contractId + '"]');
		var total = $lineCbs.length;
		var checked = $lineCbs.filter(':checked').length;

		$contractCb.prop('checked', checked > 0);
		$contractCb.prop('indeterminate', checked > 0 && checked < total);
	},

	selectAll: function(checked) {
		jQuery('.wizard-contract-cb').prop('checked', checked).prop('indeterminate', false);
		jQuery('.wizard-line-cb').prop('checked', checked);
		jQuery('#wizard-checkall').prop('checked', checked).prop('indeterminate', false);
		this.updateSelectionCount();
	},

	updateSelectionCount: function() {
		var total, selected;
		if (this.config.mode === 'lines') {
			// Count loaded lines, fall back to contract count
			var $lineCbs = jQuery('.wizard-line-cb');
			if ($lineCbs.length > 0) {
				total = $lineCbs.length;
				selected = $lineCbs.filter(':checked').length;
			} else {
				total = jQuery('.wizard-contract-cb').length;
				selected = jQuery('.wizard-contract-cb:checked').length;
			}
		} else {
			total = jQuery('.wizard-contract-cb').length;
			selected = jQuery('.wizard-contract-cb:checked').length;
		}
		var text = this.config.langs.SelectedCount.replace('%s', selected).replace('%s', total);
		jQuery('#wizard-selection-count').text(text);

		// Update header checkbox
		var $header = jQuery('#wizard-checkall');
		$header.prop('checked', selected > 0);
		$header.prop('indeterminate', selected > 0 && selected < total);
	},

	// ------------------------------------------
	// Step 2 -> Step 3 (Preview)
	// ------------------------------------------
	step2ToStep3: function() {
		var self = this;

		var selectedData = this.getSelectedData();
		if (!selectedData) {
			this.showError(this.config.langs.ValidationErrorNoSelection);
			return;
		}

		this.clearError();
		this.setButtonLoading('#btn-preview', this.config.langs.LoadingPreview);

		var action = this.config.mode === 'lines' ? 'preview_lines' : 'preview';
		var data = {
			id: this.state.adjustmentId,
			percent: this.state.adjustmentPercent
		};

		if (this.config.mode === 'lines') {
			data.selected_lines = selectedData;
		} else {
			data.contract_ids = selectedData;
		}

		this.ajax(action, data, function(r) {
			self.state.preview = r.preview;
			self.renderPreview(r.preview);
			self.resetButton('#btn-preview');
			self.goToStep(3);
		}, function() {
			self.resetButton('#btn-preview');
		});
	},

	getSelectedData: function() {
		if (this.config.mode === 'lines') {
			// Build {contractId: [lineId, ...]} from loaded lines, or contract-level
			var self = this;
			var selected = {};
			var hasAny = false;

			// Collect checked line-level checkboxes (from expanded contracts)
			jQuery('.wizard-line-cb:checked').each(function() {
				var contractId = jQuery(this).data('contract');
				var lineId = jQuery(this).data('line');
				if (!selected[contractId]) selected[contractId] = [];
				selected[contractId].push(lineId);
				hasAny = true;
			});

			// For checked contracts whose lines were NOT expanded, send sentinel
			// value 0. PHP filters out 0 (lineId > 0 check) then fetches all
			// active lines for that contract.
			jQuery('.wizard-contract-cb:checked').each(function() {
				var contractId = jQuery(this).data('contract');
				if (!selected[contractId] && !self.state.linesLoaded[contractId]) {
					selected[contractId] = [0];
					hasAny = true;
				}
			});

			return hasAny ? selected : null;
		} else {
			// Contract mode: just return array of contract IDs
			var ids = [];
			jQuery('.wizard-contract-cb:checked').each(function() {
				ids.push(jQuery(this).val());
			});
			return ids.length > 0 ? ids : null;
		}
	},

	renderPreview: function(preview) {
		var self = this;
		var $container = jQuery('#wizard-preview-container');
		$container.empty();

		// Summary box
		var diffClass = preview.totals.total_diff >= 0 ? 'amountremaintopay' : 'amountpaymentcomplete';
		var diffSign = preview.totals.total_diff >= 0 ? '+' : '';

		var html = '<div class="info marginbottomonly">';
		html += '<strong>' + this.config.langs.TotalContracts + ':</strong> ' + preview.totals.total_contracts + ' &nbsp; | &nbsp; ';
		html += '<strong>' + this.config.langs.TotalLines + ':</strong> ' + preview.totals.total_lines;
		html += '</div>';

		// Totals table
		html += '<table class="noborder centpercent">';
		html += '<tr class="liste_titre"><th colspan="2">' + this.config.langs.Summary + '</th></tr>';
		html += '<tr class="oddeven"><td>' + this.config.langs.TotalHTBefore + '</td>';
		html += '<td class="right">' + this.formatPrice(preview.totals.total_ht_before) + ' ' + this.config.currencySymbol + '</td></tr>';
		html += '<tr class="oddeven"><td>' + this.config.langs.TotalHTAfter + '</td>';
		html += '<td class="right">' + this.formatPrice(preview.totals.total_ht_after) + ' ' + this.config.currencySymbol + '</td></tr>';
		html += '<tr class="oddeven"><td><strong>' + this.config.langs.PriceDiff + '</strong></td>';
		html += '<td class="right"><strong><span class="' + diffClass + '">' + diffSign + this.formatPrice(preview.totals.total_diff) + ' ' + this.config.currencySymbol + '</span></strong></td></tr>';
		html += '</table>';

		// Detail table
		html += '<br><table class="noborder centpercent">';
		html += '<tr class="liste_titre">';
		html += '<th width="30"></th>';
		html += '<th>' + this.config.langs.Contract + ' / ' + this.config.langs.Product + '</th>';
		html += '<th class="right">' + this.config.langs.SubpriceBefore + '</th>';
		html += '<th class="right">' + this.config.langs.SubpriceAfter + '</th>';
		html += '<th class="right">' + this.config.langs.PriceDiff + '</th>';
		html += '</tr>';

		for (var ci = 0; ci < preview.contracts.length; ci++) {
			var contract = preview.contracts[ci];
			var cDiff = contract.totals.total_diff;
			var cDiffClass = cDiff >= 0 ? 'amountremaintopay' : 'amountpaymentcomplete';
			var cDiffSign = cDiff >= 0 ? '+' : '';
			var contractLink = this.getContractLink(contract.id);

			html += '<tr class="oddeven">';
			html += '<td class="center"><a href="#" class="toggle-preview-lines" data-idx="' + ci + '"><span class="fas fa-chevron-right preview-expand-icon"></span></a></td>';
			html += '<td><strong>' + contractLink + '</strong> <span class="opacitymedium">(' + contract.lines.length + ' ' + this.config.langs.Lines + ')</span></td>';
			html += '<td class="right">' + this.formatPrice(contract.totals.total_ht_before) + '</td>';
			html += '<td class="right">' + this.formatPrice(contract.totals.total_ht_after) + '</td>';
			html += '<td class="right"><span class="' + cDiffClass + '">' + cDiffSign + this.formatPrice(cDiff) + '</span></td>';
			html += '</tr>';

			for (var li = 0; li < contract.lines.length; li++) {
				var line = contract.lines[li];
				var lDiff = line.price_diff;
				var lDiffClass = lDiff >= 0 ? 'amountremaintopay' : 'amountpaymentcomplete';
				var lDiffSign = lDiff >= 0 ? '+' : '';

				html += '<tr class="oddeven preview-detail-row preview-lines-' + ci + '" style="display:none;">';
				html += '<td></td>';
				html += '<td class="paddingleft">&nbsp;&nbsp;&nbsp;<span class="fas fa-angle-right opacitymedium paddingright"></span> ' + self.escapeHtml(line.product_label || line.product_ref || '-') + '</td>';
				html += '<td class="right">' + this.formatPrice(line.subprice_before) + '</td>';
				html += '<td class="right">' + this.formatPrice(line.subprice_after) + '</td>';
				html += '<td class="right"><span class="' + lDiffClass + '">' + lDiffSign + this.formatPrice(lDiff) + '</span></td>';
				html += '</tr>';
			}
		}

		html += '</table>';
		$container.html(html);

		// Bind expand/collapse in preview
		jQuery('.toggle-preview-lines').on('click', function(e) {
			e.preventDefault();
			var idx = jQuery(this).data('idx');
			var $rows = jQuery('.preview-lines-' + idx);
			var $icon = jQuery(this).find('.preview-expand-icon');
			if ($rows.first().is(':visible')) {
				$rows.hide();
				$icon.removeClass('fa-chevron-down').addClass('fa-chevron-right');
			} else {
				$rows.show();
				$icon.removeClass('fa-chevron-right').addClass('fa-chevron-down');
			}
		});

		// Execute button permission
		if (!this.config.canExecute) {
			jQuery('#btn-execute').addClass('butActionRefused').off('click')
				.attr('title', this.config.langs.NotAllowed || 'Not allowed');
		}
	},

	getContractRef: function(contractId) {
		for (var i = 0; i < this.state.contracts.length; i++) {
			if (this.state.contracts[i].id == contractId) {
				return this.state.contracts[i].ref;
			}
		}
		return '#' + contractId;
	},

	getContractLink: function(contractId) {
		for (var i = 0; i < this.state.contracts.length; i++) {
			if (this.state.contracts[i].id == contractId) {
				var c = this.state.contracts[i];
				return '<a class="refurl" href="' + c.url + '" target="_blank">' + this.escapeHtml(c.ref) + '</a>';
			}
		}
		return '#' + contractId;
	},

	// ------------------------------------------
	// Execute
	// ------------------------------------------
	executeAdjustment: function() {
		var self = this;

		// Prevent double-submission
		if (this.state.executing) return;
		if (!this.config.canExecute) return;

		// Confirmation
		if (!confirm(this.config.langs.ConfirmExecuteIndexAdjustment)) {
			return;
		}

		this.state.executing = true;
		this.setButtonLoading('#btn-execute', this.config.langs.ExecutingAdjustment);

		// Block UI
		if (typeof dolBlockUI === 'function') {
			dolBlockUI();
		}

		var action = this.config.mode === 'lines' ? 'execute_lines' : 'execute';
		var data = {
			id: this.state.adjustmentId
		};
		console.log('Execute with state:', this.state, 'id:', this.state.adjustmentId);

		var selectedData = this.getSelectedData();
		if (this.config.mode === 'lines') {
			data.selected_lines = selectedData;
		} else {
			data.contract_ids = selectedData;
		}

		this.ajax(action, data, function(r) {
			if (typeof jQuery.unblockUI === 'function') {
				jQuery.unblockUI();
			}
			// Show success and redirect
			self.showSuccess(self.config.langs.ExecuteSuccess);
			setTimeout(function() {
				window.location.href = r.redirect;
			}, 1500);
		}, function() {
			if (typeof jQuery.unblockUI === 'function') {
				jQuery.unblockUI();
			}
			self.state.executing = false;
			self.resetButton('#btn-execute');
		});
	},

	// ------------------------------------------
	// UI helpers
	// ------------------------------------------
	setButtonLoading: function(selector, text) {
		var $btn = jQuery(selector);
		$btn.data('original-html', $btn.html());
		$btn.html('<span class="fas fa-spinner fa-spin"></span> ' + (text || '...'));
		$btn.addClass('butActionRefused').css('pointer-events', 'none');
	},

	resetButton: function(selector) {
		var $btn = jQuery(selector);
		var original = $btn.data('original-html');
		if (original) {
			$btn.html(original);
		}
		$btn.removeClass('butActionRefused').css('pointer-events', '');
	},

	showError: function(message) {
		jQuery('#wizard-error').html('<div class="error">' + this.escapeHtml(message) + '</div>').show();
	},

	showSuccess: function(message) {
		jQuery('#wizard-error').html('<div class="ok">' + this.escapeHtml(message) + '</div>').show();
	},

	clearError: function() {
		jQuery('#wizard-error').empty().hide();
	},

	hideLoading: function() {
		// Generic loading state cleanup
	},

	formatPrice: function(value) {
		if (value === null || value === undefined) return '0.00';
		return parseFloat(value).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
	},

	escapeHtml: function(str) {
		if (!str) return '';
		var div = document.createElement('div');
		div.appendChild(document.createTextNode(str));
		return div.innerHTML;
	}
};
