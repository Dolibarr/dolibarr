/* jshint esversion: 6 */
/* jshint jquery: true */
'use strict';

/**
 * Objet global (avec un nom sans équivoque) pour éviter tout conflit de nommage avec d'autres scripts de Dolibarr ou de
 * modules externes.
 */
const LoanModule = {
	/* ———— fonctions métier ———— */
	/**
	 * Used on page loan/schedule.php to initialize dynamic update of the payment schedule preview
	 *
	 * @param {Object} context
	 * @return {void}
	 */
	initLoanSchedule(context) {
		/**
		 * Sends an ajax query to the back-end which should respond with a new (partial) amortization schedule, which is a
		 * list of installment objects, starting with the one that was passed (but which will be modified to take into
		 * account the new manual payment amonut). Returns the jQuery Ajax object (we can chain it with .done() etc.)
		 *
		 * @param {Object} installment  Initial data of the installment whose amount we want to enter manually
		 * @param {integer} installment.p    period number (starts with 1)
		 * @param {float}   installment.pmt  payment amount (=ppmt + ipmt)
		 * @param {float}   installment.ppmt principal amount
		 * @param {float}   installment.ipmt interest amount
		 * @param {float}   installment.pv   present value (balance before installment)
		 * @param {float}   installment.fv   future value (balance after installment)
		 * @param {float} manualPmt  Manually entered amount (will replace installment.pmt)
		 * @returns {JQuery.jqXHR}  JQuery ajax object
		 */
		const ajaxGetAmortizationSchedule = function (installment, manualPmt) {
			const loan = context['loan'];
			return $.ajax(
				{
					url: context['ajaxURL'],
					dataType: 'json',
					method: 'GET',
					data: {
						action: 'getAmortizationSchedule',
						loan: loan,
						installment: installment,
						manualPmt: manualPmt,
					}
				}
			);
		};

		/**
		 * Called after the ajax call in ajaxGetAmortizationSchedule succeeds: updates the rows (<tr>) of the table to
		 * replace their values (both the displayed value inside the <td> AND the data-* value attributes of the <tr>).
		 *
		 * @param installments
		 */
		const updateAmortizationSchedule = function (installments) {
			installments.forEach(function (installment) {
				const $tr = $('tr.installment[data-p=' + installment.p + ']');
				$tr.find('.ppmt .number').val(numFormat(installment.ppmt));
				$tr.find('.ipmt .number').val(numFormat(installment.ipmt));
				$tr.find('.pmt  .number').val(numFormat(installment.pmt));
				$tr.find('.fv   .number').val(numFormat(installment.fv));

				// update data attributes (data-p, data-pmt, etc.) on the <tr> tag so that
				// subsequent calculations use up-to-date figures
				for (const attrName of ['p', 'pmt', 'ipmt', 'ppmt', 'pv', 'fv']) {
					$tr.attr('data-' + attrName, installment[attrName]);
				}

				// visual feedback to highlight all modified rows
				const addHighlight = () => {
					$tr.addClass('transient-highlight');
					$tr.on('animationend', rmHighlight);
				};
				const rmHighlight = () => {
					$tr.removeClass('transient-highlight');
					$tr.off('animationend');
				};

				addHighlight();
			});
		};

		/**
		 * Use ajaxGetAmortizationSchedule to send an ajax query to `loan.ajax.php`, which returns the loan schedule
		 * (which is an array of installment data objects) starting with the installment whose payment was entered manually.
		 *
		 * When the ajax returns, the <tr> and its <td> are updated using updateAmortizationSchedule.
		 *
		 * Note: the calculation would also be possible in pure js but it is better to keep all the calculations in the same
		 * place, which is a PHP function, for consistency.
		 */
		const recomputeForManualPayment = function () {
			// TODO: vérifier que price2numjs fonctionne bien en 10.0
			const manualPmt = parseFloat(price2numjs($(this).val().replace(/[  ]/, '')));
			const $tr = $(this).closest('tr');
			const installment = {};
			// récupère data-p, data-pmt, data-ipmt etc. depuis le <tr>
			// et convertit chaque valeur en numérique
			for (const attrName of ['p', 'pmt', 'ipmt', 'ppmt', 'pv', 'fv']) {
				installment[attrName] = installmentAttrCast[attrName]($tr.attr('data-' + attrName));
			}

			if (Math.abs(installment.pmt - manualPmt) < 0.01) return false;

			ajaxGetAmortizationSchedule(installment, manualPmt)
				.done(function (data) {
					updateAmortizationSchedule(data.installments);
				});
			return true;
		};

		// data required to convert installment attributes to the correct type when they are passed as strings
		const installmentAttrCast = {
			p: (n) => parseInt(n, 10),
			pmt: parseFloat,
			ipmt: parseFloat,
			ppmt: parseFloat,
			pv: parseFloat,
			fv: parseFloat,
		};

		// initialize number formatter (because pricejs does not work in this version of Dolibarr)
		// if current Dolibarr locale not supported, fall back to American English numbers
		// TODO: these are arguably technical functions and as such could be moved out of this function (to separate business
		//       logic from 'helper' functions and make the 'helper' functions available elsewhere)
		const dolibarrLocale = context['MAIN_LANG_DEFAULT'].replace('_', '-'); // ex: 'de_AT' -> 'de-AT'
		const dolibarrLocaleNoReg = dolibarrLocale.replace(/(-.*)$/, ''); // ex: 'de-AT' -> 'de'
		const locale = Intl.NumberFormat.supportedLocalesOf(Intl.getCanonicalLocales([dolibarrLocale, dolibarrLocaleNoReg, 'en-US']))[0];
		const numFormatter = Intl.NumberFormat(locale, {
			minimumFractionDigits: context['nbDecimals'], maximumFractionDigits: context['nbDecimals']
		});

		// TODO: peut-être pas la peine, il vaut sans doute mieux indiquer en haut de page "les montants sont en {DEVISE}"
		const currency = context['loan']['currency'] || 'EUR';
		const currencyFormatter = Intl.NumberFormat(locale, {style: 'currency', currency: currency,});
		const numFormat = function (n, currency = false) {
			if (currency) return currencyFormatter.format(n);
			return numFormatter.format(n);
		};

		/**
		 * Main function
		 */
		$(() => {
			// lorsque le focus est perdu par un des input de montant d'échéance, on lance le recalcul
			$('form.loanschedule .pmt input').on('focusout', recomputeForManualPayment);


			// interception de l'envoi de formulaire: on annule l'envoi si appui sur "Entrée" pour recalcul
			$('form.loanschedule').on('submit', function (ev) {
				const $activeInput = $(document.activeElement);
				// lorsque l'envoi du formulaire est provoqué par la touche Entrée dans un des input de montant d'échéance,
				// si la valeur a changé, on lance le recalcul et on annule l'envoi du formulaire
				// si la valeur n'a pas changé, on envoie le formulaire
				if ($activeInput.prop('tagName') === 'INPUT' &&
					$activeInput.closest('td.pmt').length === 1) {
					// jQuery-style call: (we bind the active <input> element to `this`)
					if (recomputeForManualPayment.bind(document.activeElement)()) {
						ev.preventDefault();
						ev.stopPropagation();
						return false;
					}
				}

				// avant d'envoyer le formulaire, on le "nettoie" pour que les champs dont le back-end n'a pas besoin
				// ne soient pas envoyés (ça saturera moins `max_input_vars` pour les très gros échéanciers)
				// note: un champ `disabled` n'est pas envoyé

				$('form.loanschedule .pmt input').attr('disabled', '');
				return true; // pour lisibilité
			});
		});
	},

	/**
	 * Used on page loan/card.php
	 *
	 * @param {Object} context        Variables passed from PHP to js
	 * @param {Object} context.trans  Key-pair for translations
	 * @return {void}
	 */
	initLoanCard(context) {
		this.init(context);
		if (!context['hasEcheancier']) return;

		// Add confirm dialog before deleting the existing loan schedule
		$(() => {
			$('form[name="update"]').on('submit', (ev) => {
				if (confirm(this.langs.trans('ConfirmResetSchedule'))) {
					return true;
				} else {
					ev.preventDefault();
					ev.stopPropagation();
					return false;
				}
			});
		});
	},

	/* ———— fonctions techniques ———— */
	/**
	 * Équivalent (plus ou moins) du $langs->trans() de Dolibarr avec mécanisme de substitution.
	 *
	 * ⚠ Avant d'appeler langs.trans(), il faut appeler this.init(context), qui joue un rôle analogue au $langs->load()
	 * de Dolibarr.
	 *
	 * Le mécanisme de substitution émule imparfaitement sprintf(), les seules syntaxes reconnues sont:
	 * '%d'      => formatage entier
	 * '%s'      => formatage chaîne
	 * '%.0{n}f' => formatage nombre à virgule arrondi à {n} décimales
	 *
	 * Mais beaucoup d'autres chaînes de format valides en php ne sont pas émulées ici car trop
	 * compliqué (tout ce qui est hexadécimal, caractères de remplissage, etc.)
	 *
	 * @param {String}        key   translation key
	 * @param {String|Number} subst replacement for substitution placeholders
	 */
	langs: {
		trans(key, ...subst) {
			let tr = key;
			if (this.tab_translate && this.tab_translate[key]) {
				tr = this.tab_translate[key];
			}
			/* jshint ignore: start */
			// pourquoi désactiver jshint ici? ⇒ il ne connait pas les regexp avec negative lookbehind
			//                                   cf. https://github.com/jshint/jshint/issues/3328
			let i = 0;
			tr = tr.replace(/(?<!%)%(?:\.(.)(\d+))?([sdf])/g, (fullmatch, m1, m2, m3) => {
				let ret = subst[i++];
				if (m3 === 'f' && m1 && m2) {
					// c'est complètement faux, c'est un raccourci honteux, mais ça devrait aller
					let roundnum = parseInt(m2, 10);
					ret = parseFloat(ret).toFixed(roundnum).replace('.', this.DECIMALSEPARATOR);
				} else if (m3 === 'd') {
					ret = parseInt(ret, 10);
				}
				return ret;
			});
			/* jshint ignore: end */
			return tr;
		},
	},

	/**
	 * Initialisation des traductions
	 * @param context
	 */
	init(context) {
		this.langs.tab_translate = context.tab_translate;
	},
};
