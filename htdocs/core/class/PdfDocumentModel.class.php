<?php

/* Copyright (C) 2014   Marcos García   <marcosgdf@gmail.com>
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

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';

/**
 * Class PdfDocumentModel
 *
 * Every ModelePDF* class must extend from it
 */
abstract class PdfDocumentModel extends CommonDocGenerator
{

	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public abstract function __construct(DoliDB $db);

	/**
	 * Return a string with full address formated
	 *
	 * @param Translate $outputlangs Output langs object
	 * @param Societe $sourcecompany Source company object
	 * @param Societe|string $targetcompany Target company object
	 * @param Contact|string $targetcontact Target contact object
	 * @param int $usecontact Use contact instead of company
	 * @param string $mode Address type ('source', 'target', 'targetwithdetails')
	 * @return string String with full address
	 */
	protected function buildAddress(Translate $outputlangs, Societe $sourcecompany, $targetcompany='', $targetcontact='', $usecontact=0, $mode='source')
	{
		global $conf;

		$stringaddress = '';

		if ($mode == 'source' && ! is_object($sourcecompany)) return -1;
		if ($mode == 'target' && ! is_object($targetcompany)) return -1;

		if (! empty($sourcecompany->state_id) && empty($sourcecompany->departement)) {
			//TODO: Deprecated
			$sourcecompany->departement=getState($sourcecompany->state_id);
		}
		if (! empty($sourcecompany->state_id) && empty($sourcecompany->state)) {
			$sourcecompany->state=getState($sourcecompany->state_id);
		}
		if (! empty($targetcompany->state_id) && empty($targetcompany->departement)) {
			$targetcompany->departement=getState($targetcompany->state_id);
		}

		if ($mode == 'source')
		{
			$withCountry = 0;
			if (!empty($sourcecompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) $withCountry = 1;

			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourcecompany, $withCountry, "\n", $outputlangs))."\n";

			if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS))
			{
				// Phone
				if ($sourcecompany->phone) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
				// Fax
				if ($sourcecompany->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
				// EMail
				if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
				// Web
				if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
			}
		}

		if ($mode == 'target' || $mode == 'targetwithdetails')
		{
			if ($usecontact)
			{
				$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs,1));

				if (!empty($targetcontact->address)) {
					$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
				}else {
					$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
				}
				// Country
				if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
					$stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code))."\n";
				}
				else if (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
					$stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";
				}

				if (! empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails')
				{
					// Phone
					if (! empty($targetcontact->phone_pro) || ! empty($targetcontact->phone_mobile)) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ";
					}
					if (! empty($targetcontact->phone_pro)) {
						$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_pro);
					}
					if (! empty($targetcontact->phone_pro) && ! empty($targetcontact->phone_mobile)) {
						$stringaddress .= " / ";
					}
					if (! empty($targetcontact->phone_mobile)) {
						$stringaddress .= $outputlangs->convToOutputCharset($targetcontact->phone_mobile);
					}
					// Fax
					if ($targetcontact->fax) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
					}
					// EMail
					if ($targetcontact->email) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
					}
					// Web
					if ($targetcontact->url) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
					}
				}
			}
			else
			{
				$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
				// Country
				if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) {
					$stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";
				}

				if (! empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS) || $mode == 'targetwithdetails')
				{
					// Phone
					if (! empty($targetcompany->phone) || ! empty($targetcompany->phone_mobile)) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ";
					}
					if (! empty($targetcompany->phone)) {
						$stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone);
					}
					if (! empty($targetcompany->phone) && ! empty($targetcompany->phone_mobile)) {
						$stringaddress .= " / ";
					}
					if (! empty($targetcompany->phone_mobile)) {
						$stringaddress .= $outputlangs->convToOutputCharset($targetcompany->phone_mobile);
					}
					// Fax
					if ($targetcompany->fax) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
					}
					// EMail
					if ($targetcompany->email) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
					}
					// Web
					if ($targetcompany->url) {
						$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
					}
				}
			}

			// Intra VAT
			if (empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS))
			{
				if ($targetcompany->tva_intra) {
					$stringaddress.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
				}
			}

			// Professionnal Ids
			if (! empty($conf->global->MAIN_PROFID1_IN_ADDRESS) && ! empty($targetcompany->idprof1))
			{
				$tmp=$outputlangs->transcountrynoentities("ProfId1",$targetcompany->country_code);
				if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
				$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
			}
			if (! empty($conf->global->MAIN_PROFID2_IN_ADDRESS) && ! empty($targetcompany->idprof2))
			{
				$tmp=$outputlangs->transcountrynoentities("ProfId2",$targetcompany->country_code);
				if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
				$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
			}
			if (! empty($conf->global->MAIN_PROFID3_IN_ADDRESS) && ! empty($targetcompany->idprof3))
			{
				$tmp=$outputlangs->transcountrynoentities("ProfId3",$targetcompany->country_code);
				if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
				$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
			}
			if (! empty($conf->global->MAIN_PROFID4_IN_ADDRESS) && ! empty($targetcompany->idprof4))
			{
				$tmp=$outputlangs->transcountrynoentities("ProfId4",$targetcompany->country_code);
				if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
				$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
			}
		}

		return $stringaddress;
	}

}