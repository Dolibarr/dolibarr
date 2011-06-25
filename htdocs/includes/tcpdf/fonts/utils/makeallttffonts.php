<?php
//============================================================+
// File name   : makeallttffonts.php
// Begin       : 2008-12-07
// Last Update : 2011-05-31
//
// Description : Process all TTF files on current directory to
//               build TCPDF compatible font files.
//
// Author: Nicola Asuni
//
// (c) Copyright:
//               Nicola Asuni
//               Tecnick.com S.r.l.
//               Via della Pace, 11
//               09044 Quartucciu (CA)
//               ITALY
//               www.tecnick.com
//               info@tecnick.com
//
// License:
//    Copyright (C) 2004-2011  Nicola Asuni - Tecnick.com S.r.l.
//
// This file is part of TCPDF software library.
//
// TCPDF is free software: you can redistribute it and/or modify it
// under the terms of the GNU Lesser General Public License as
// published by the Free Software Foundation, either version 3 of the
// License, or (at your option) any later version.
//
// TCPDF is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU Lesser General Public License for more details.
//
// You should have received a copy of the GNU Lesser General Public License
// along with TCPDF.  If not, see <http://www.gnu.org/licenses/>.
//
// See LICENSE.TXT file for more information.
//============================================================+

/**
 * Process all TTF files on current directory to build TCPDF compatible font files.
 * @package com.tecnick.tcpdf
 * @author Nicola Asuni
 * @copyright Copyright &copy; 2004-2009, Nicola Asuni - Tecnick.com S.r.l. - ITALY - www.tecnick.com - info@tecnick.com
 * @license http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License
 * @link www.tecnick.com
 * @since 2008-12-07
 */

/*
OPTIONS FOR ttf2ufm
	--all-glyphs
	-a - include all glyphs, even those not in the encoding table
	--pfb
	-b - produce a compressed .pfb file
	--debug dbg_suboptions
	-d dbg_suboptions - debugging options, run ttf2pt1 -d? for help
	--encode
	-e - produce a fully encoded .pfa file
	--force-unicode
	-F - force use of Unicode encoding even if other MS encoding detected
	--generate suboptions
	-G suboptions - control the file generation, run ttf2pt1 -G? for help
	--language language
	-l language - convert Unicode to specified language, run ttf2pt1 -l? for list
	--language-map file
	-L file - convert Unicode according to encoding description file
	--limit <type>=<value>
	-m <type>=<value> - set maximal limit of given type to value, types:
	    h - maximal hint stack depth in the PostScript interpreter
	--processing suboptions
	-O suboptions - control outline processing, run ttf2pt1 -O? for help
	--parser name
	-p name - use specific front-end parser, run ttf2pt1 -p? for list
	--uid id
	-u id - use this UniqueID, -u A means autogeneration
	--vertical-autoscale size
	-v size - scale the font to make uppercase letters >size/1000 high
	--version
	-V - print ttf2pt1 version number
	--warning number
	-W number - set the level of permitted warnings (0 - disable)
	Obsolete options (will be removed in future releases):
		--afm
		-A - write the .afm file to STDOUT instead of the font, now -GA
		-f - don't try to guess the value of the ForceBold hint, now -Ob
		-h - disable autogeneration of hints, now -Oh
		-H - disable hint substitution, now -Ou
		-o - disable outline optimization, now -Oo
		-s - disable outline smoothing, now -Os
		-t - disable auto-scaling to 1000x1000 standard matrix, now -Ot
		-w - correct the glyph widths (use only for buggy fonts), now -OW
*/

// read directory for files (only TTF and OTF files).
$handle = opendir('.');
while ($file = readdir($handle)) {
	$path_parts = pathinfo($file);
	if (isset($path_parts['extension'])) {
		$fontfile = $path_parts['basename'];
		$filename = $path_parts['filename'];
		$extension = strtolower($path_parts['extension']);
		if (($extension === 'ttf') OR ($extension === 'otf')) {
			if (!file_exists($filename.'.ufm')) {
				if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
					// windows
					passthru('ttf2ufm.exe -a -F -O bhuosTwV '.$fontfile);
				} else {
					// linux
					passthru('./ttf2ufm -a -F -O bhuosTwV '.$fontfile);
				}
			}
			$cmd = 'php -q makefont.php '.$fontfile.' '.$filename.'.ufm'; // unicode file
			passthru($cmd);
		}
	}
}
closedir($handle);

//============================================================+
// END OF FILE
//============================================================+
