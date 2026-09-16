<?php
/* Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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

/**
 *  \file       htdocs/core/class/doltcpdi.class.php
 *  \ingroup    core
 *  \brief      Dolibarr subclass of TCPDI
 */

/**
 * Same reasoning as DolTCPDF: TCPDI inherits TCPDF's protected $tcpdflink
 * property (set to true in TCPDF's constructor) and TCPDF exposes no
 * setter for it, so this subclass is the only way to turn it off for the
 * TCPDI instances used to merge/edit existing PDFs.
 *
 * @see DolTCPDF
 */
class DolTCPDI extends TCPDI
{
	/**
	 *  Constructor
	 *
	 *  @param	string	$orientation	Page orientation
	 *  @param	string	$unit			User measure unit
	 *  @param	mixed	$format			The format used for pages
	 *  @param	bool	$unicode		TRUE means that the input text is unicode
	 *  @param	string	$encoding		Charset encoding
	 *  @param	bool	$diskcache		If TRUE reduce the RAM memory usage by caching temporary data on filesystem
	 *  @param	int		$pdfa			If not false, set the document to PDF/A mode and the specified version: 1 for "1-B", 2 for "2-B", 3 for "3-B"
	 */
	public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4', $unicode = true, $encoding = 'UTF-8', $diskcache = false, $pdfa = false)
	{
		parent::__construct($orientation, $unit, $format, $unicode, $encoding, $diskcache, $pdfa);

		if (getDolGlobalInt('MAIN_PDF_DISABLE_TCPDF_LINK')) {
			$this->tcpdflink = false;
		}
	}
}
