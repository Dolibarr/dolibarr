
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

// Calcul et affichage en temps reel des informations sur le produit en cours
function modif() {

	var prix_unit = parseFloat ( document.getElementById('frmQte').txtPrixUnit.value );
	var qte = parseFloat ( document.getElementById('frmQte').txtQte.value );
	var _index = parseFloat ( document.getElementById('frmQte').selTva.selectedIndex );
	var tva = parseFloat ( document.getElementById('frmQte').selTva.options[_index].text );
	var remise = parseInt ( document.getElementById('frmQte').txtRemise.value );
	var stock = document.getElementById('frmQte').txtStock.value;

// 		// On s'assure que la quantitee tapee ne depasse pas le stock
// 		if ( qte > stock ) {
//
// 			qte = stock;
// 			document.getElementById('frmQte').txtQte.value = qte;
//
// 		}
//
// 		if ( qte < 1 ) {
//
// 			qte = 1;
// 			document.getElementById('frmQte').txtQte.value = qte;
//
// 		}
//
// 		if ( !stock || stock <= 0 ) {
//
// 			qte = 0;
// 			document.getElementById('frmQte').txtQte.value = qte;
//
// 		}

	// Calcul du total HT, sans remise
	var total_ht = Math.round ( (prix_unit * qte) * 100 ) / 100;

	// Calcul du montant de la remise, apres s'etre assure que cette derniere ne soit pas negative
	if ( remise <= 0 ) {

		document.getElementById('frmQte').txtRemise.value = 0;
		montant_remise = 0;

	} else {

		var montant_remise = total_ht * remise / 100;

	}

	// Recalcul du montant total, avec la remise
	var total = Math.round ( (total_ht - montant_remise) *100 ) / 100;

	// Affichage du resultat dans le formulaire
	document.getElementById('frmQte').txtTotal.value = total.toFixed(2);

}

// Affecte la source de la requete (liste deroulante ou champ texte 'ref') au champ cache
function setSource(aSrc) {

	document.getElementById('frmFacturation').hdnSource.value = aSrc;
	document.getElementById('frmFacturation').submit();

}

// Verification de la coherence des informations saisies dans le formulaire de choix du nombre d'articles
function verifSaisie() {

	if ( document.getElementById('frmQte').txtQte.value ) {

		return true;

	} else {

		document.getElementById('frmQte').txtQte.focus();
		return false;

	}

}

// Verification de la coherence des informations saisies dans le formulaire de calcul de la difference
function verifDifference() {

	var du = parseFloat ( document.getElementById('frmDifference').txtDu.value );
	var encaisse = parseFloat ( document.getElementById('frmDifference').txtEncaisse.value );

	if (encaisse > du) {

		resultat = Math.round ( (encaisse - du) * 100 ) / 100;
		document.getElementById('frmDifference').txtRendu.value = resultat.toFixed(2);

	} else if (encaisse == du) {

		document.getElementById('frmDifference').txtRendu.value = '0';

	} else {

		document.getElementById('frmDifference').txtRendu.value = '-';

	}

}

// Affecte le moyen de paiement (ESP, CB ou CHQ) au champ cache en fonction du bouton clique
function verifClic(aChoix) {

	document.getElementById('frmDifference').hdnChoix.value = aChoix;

}

// Determination du moyen de paiement, et validation du formulaire si les donnees sont coherentes
function verifReglement() {

	var choix = document.getElementById('frmDifference').hdnChoix.value;
	var du = parseFloat (document.getElementById('frmDifference').txtDu.value);
	var encaisse = parseFloat (document.getElementById('frmDifference').txtEncaisse.value);

	if ( du > 0 ) {

		if ( choix == 'ESP' ) {

			if ( encaisse != 0 && encaisse >= du ) {

				return true;

			} else {

				document.getElementById('frmDifference').txtEncaisse.select();
				document.getElementById('frmDifference').txtEncaisse.focus();
				return false;

			}

		} else if ( choix == 'DIF' ) {

			if ( document.getElementById('frmDifference').txtDatePaiement.value ) {

				return true;

			} else {

				document.getElementById('frmDifference').txtDatePaiement.select();
				document.getElementById('frmDifference').txtDatePaiement.focus();
				return false;

			}

		} else {

			return true;

		}

	} else {

		return false;

	}
}
