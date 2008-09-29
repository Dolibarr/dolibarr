<?php
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

	// Création aléatoire de chaines de caractères de longueur $taille passée en argument
	function rand_chaine ($taille) {

		$tableau = '9632587410wqaxszcdevfrbgtnhyjukilompMPLOKIJUNHYBGTVFRCDEXSZWQA';
		$chaine = '';

		for ( $i = 0; $i < $taille; $i++ ) {

			$indice = rand (0, 61);
			$chaine .= $tableau[$indice];

		}

		return ($chaine);

	}

	// Vérification du format d'une adresse email passée en argument
	// Retour : 0 = pas d'erreur, 1 = format invalide
	function verif_email ($email) {

		$test_email1 = explode ('@',$email);
		$test_email2 = explode ('.',$test_email1[1]);
		if ( !$test_email1[0] | !$test_email2[0] | !$test_email2[1] ) {

			return (1);

		} else {

			return (0);

		}

	}

	// Vérification du format d'une url (avec http://) email passée en argument
	// 0 = pas d'erreur, 1 = format invalide
	function verif_url ($url) {

		$test_url1 = explode ('//',$url);
		$test_url2 = explode ('.',$test_url1[1]);
		if ( $test_url1[0] != 'http:' | !$test_url2[0] | !$test_url2[1] | !$test_url2[2] ) {

			return (1);

		} else {

			return (0);

		}

	}

	// Vérifie que la chaine passée en argument ne comporte que des chiffres
	// 0 = pas d'erreur, 1 = format invalide
	function verif_num ($num) {

		$err = 0;
		$masque = '^[0-9]+$';

		if ( ereg ($masque,$num) ) {

			return (0);

		} else {

			return (1);

		}

	}

	// Supprime tous les accents de la chaîne passée en argument
	function suppr_accents ($chaine) {

		return( strtr( $chaine,
			"ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",
			"AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn"
		) );

	}

?>
