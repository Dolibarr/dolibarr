<?php
/**
 * PHPExcel
 *
 * Copyright (c) 2006 - 2012 PHPExcel
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation; either
 * version 2.1 of the License, or (at your option) any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @category	PHPExcel
 * @package		PHPExcel_Writer
 * @copyright	Copyright (c) 2006 - 2012 PHPExcel (http://www.codeplex.com/PHPExcel)
 * @license		http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt	LGPL
 * @version		1.7.8, 2012-10-12
 */


/**
 * PHPExcel_Writer_PDF
 *
 * @category	PHPExcel
 * @package		PHPExcel_Writer
 * @copyright	Copyright (c) 2006 - 2012 PHPExcel (http://www.codeplex.com/PHPExcel)
 */
class PHPExcel_Writer_PDF {

    private $_renderer = NULL;

	/**
	 * Create a new PHPExcel_Writer_PDF
	 *
	 * @param 	PHPExcel	$phpExcel	PHPExcel object
	 */
	public function __construct(PHPExcel $phpExcel) {

		$pdfLibraryName = PHPExcel_Settings::getPdfRendererName();
		if (is_null($pdfLibraryName)) {
			throw new Exception("PDF Rendering library has not been defined.");
		}

		$pdfLibraryPath = PHPExcel_Settings::getPdfRendererPath();
		if (is_null($pdfLibraryName)) {
			throw new Exception("PDF Rendering library path has not been defined.");
		}
		$includePath = str_replace('\\','/',get_include_path());
		$rendererPath = str_replace('\\','/',$pdfLibraryPath);
		if (strpos($rendererPath,$includePath) === false) {
			set_include_path(get_include_path() . PATH_SEPARATOR . $pdfLibraryPath);
		}

		$rendererName = 'PHPExcel_Writer_PDF_'.$pdfLibraryName;
		$this->_renderer = new $rendererName($phpExcel);
	}


    public function __call($name, $arguments)
    {
        if ($this->_renderer === NULL) {
			throw new Exception("PDF Renderer has not been defined.");
        }

        return call_user_func_array(array($this->_renderer,$name),$arguments);
    }

}
