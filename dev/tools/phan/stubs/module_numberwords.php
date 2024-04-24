<?php
/* Copyright (C) 2009 Laurent Destailleur         <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */
/**
 *	\file			htdocs/core/modules/substitutions/functions_numberwords.lib.php
 *	\brief			A set of functions for Dolibarr
 *					This file contains functions for plugin numberwords.
 */
/**
 * 		Function called to complete substitution array (before generating on ODT, or a personalized email)
 * 		functions xxx_completesubstitutionarray are called by make_substitutions() if file
 * 		is inside directory htdocs/core/substitutions
 *
 *		@param	array		$substitutionarray	Array with substitution key=>val
 *		@param	Translate	$outlangs			Output langs
 *		@param	Object		$object				Object to use to get values
 * 		@return	void							The entry parameter $substitutionarray is modified
 */
function numberwords_completesubstitutionarray(&$substitutionarray, $outlangs, $object)
{
}
/**
 *  Return full text translated to language label for a key. Store key-label in a cache.
 *
 *	@param		Translate	$outlangs	Language for output
 * 	@param		int		    $number		Number to encode in full text
 *  @param      string	    $isamount	''=it's just a number, '1'=It's an amount (default currency), 'currencycode'=It's an amount (foreign currency)
 *  @return     string				    Label translated in UTF8 (but without entities)
 * 									    10 if setDefaultLang was en_US => ten
 * 									    123 if setDefaultLang was fr_FR => cent vingt trois
 */
function numberwords_getLabelFromNumber($outlangs, $number, $isamount = '')
{
}
