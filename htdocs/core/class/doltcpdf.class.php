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
 *  \file       htdocs/core/class/doltcpdf.class.php
 *  \ingroup    core
 *  \brief      Dolibarr subclass of TCPDF
 */

/**
 * TCPDF always writes a "Powered by TCPDF" text and link at the end of the
 * document (property $tcpdflink, set to true in its constructor). That
 * property is protected and TCPDF exposes no setter for it, so this
 * subclass is the only way to turn it off. The LGPLv3 license bundled with
 * TCPDF does not require this attribution in generated documents; the
 * option defaults to the unchanged, historical behaviour.
 */
class DolTCPDF extends TCPDF
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
