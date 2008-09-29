
/* Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

// Instanciation et initialisation de l'objet xmlhttprequest
function file (fichier) {

	// Instanciation de l'objet pour Mozilla, Konqueror, Opera, Safari, etc ...
	if (window.XMLHttpRequest) {

		xhr_object = new XMLHttpRequest ();

	// ... ou pour IE
	} else if (window.ActiveXObject) {

		xhr_object = new ActiveXObject ("Microsoft.XMLHTTP");

	} else {

		return (false);

	}

	xhr_object.open ("GET", fichier, false);
	xhr_object.send (null);

	if (xhr_object.readyState == 4) {

		return (xhr_object.responseText);

	} else {

		return (false);

	}

}


// Affichage des données aTexte dans le bloc identifié par aId
function afficheDonnees (aId, aTexte) {

	document.getElementById(aId).innerHTML = aTexte;

}


// aCible : id du bloc de destination; aCode : argument à passer à la page php chargée du traitement et de l'affichage
function verifResultat (aCible, aCode) {

	if (aCode != '') {

		if (texte = file ('facturation_dhtml.php?code='+escape(aCode))) {

			afficheDonnees (aCible, texte);

		} else

			afficheDonnees (aCible, '');

	}

}


// Change dynamiquement la classe de l'élément ayant l'id aIdElement pour aClasse
function setStyle (aIdElement, aClasse) {

	aIdElement.className = aClasse;

}













