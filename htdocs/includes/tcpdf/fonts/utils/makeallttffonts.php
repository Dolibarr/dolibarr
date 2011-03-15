<?php
//============================================================+
// File name   : makeallttffonts.php
// Begin       : 2008-12-07
// Last Update : 2011-02-04
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
//    Copyright (C) 2004-2010  Nicola Asuni - Tecnick.com S.r.l.
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
					passthru('ttf2ufm.exe -a -F '.$fontfile);
				} else {
					// linux
					passthru('./ttf2ufm -a -F '.$fontfile);
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
