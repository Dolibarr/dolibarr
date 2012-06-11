<?php
/* Copyright (C) 2011-2012	Regis Houssin		<regis@dolibarr.fr>
 * Copyright (C) 2011-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/core/ajax/fileupload.php
 *       \brief      File to return Ajax response on file upload
 */

require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");


/**
 *	This class is used to manage file upload using ajax
 */
class FileUpload
{
	protected $options;
	protected $fk_element;
	protected $element;

	/**
	 * Constructor
	 *
	 * @param array		$options		Options array
	 * @param int		$fk_element		fk_element
	 * @param string	$element		element
	 */
	function __construct($options=null,$fk_element=null,$element=null)
	{
		global $db, $conf;
		global $object;

		$this->fk_element=$fk_element;
		$this->element=$element;

		$pathname=$filename=$element;
		if (preg_match('/^([^_]+)_([^_]+)/i',$element,$regs))
		{
			$pathname = $regs[1];
			$filename = $regs[2];
		}

		// For compatibility
		if ($element == 'propal') {
			$pathname = 'comm/propal';
			$dir_output=$conf->$element->dir_output;
		}
		elseif ($element == 'facture') {
			$pathname = 'compta/facture';
			$dir_output=$conf->$element->dir_output;
		}
		elseif ($element == 'project') {
			$element = $pathname = 'projet';
			$dir_output=$conf->$element->dir_output;
		}
		elseif ($element == 'fichinter') {
			$element='ficheinter';
			$dir_output=$conf->$element->dir_output;
		}
		elseif ($element == 'order_supplier') {
			$pathname = 'fourn'; $filename='fournisseur.commande';
			$dir_output=$conf->fournisseur->commande->dir_output;
		} else {
			$dir_output=$conf->$element->dir_output;
		}

		dol_include_once('/'.$pathname.'/class/'.$filename.'.class.php');

		$classname = ucfirst($filename);
		
		if ($element == 'order_supplier') {
			$classname = 'CommandeFournisseur';
		}
		
		$object = new $classname($db);

		$object->fetch($fk_element);
		$object->fetch_thirdparty();

		$this->options = array(
				'script_url' => $_SERVER['PHP_SELF'],
				'upload_dir' => $dir_output . '/' . $object->ref . '/',
				'upload_url' => DOL_URL_ROOT.'/document.php?modulepart='.$element.'&attachment=1&file=/'.$object->ref.'/',
				'param_name' => 'files',
				// Set the following option to 'POST', if your server does not support
				// DELETE requests. This is a parameter sent to the client:
				'delete_type' => 'DELETE',
				// The php.ini settings upload_max_filesize and post_max_size
				// take precedence over the following max_file_size setting:
				'max_file_size' => null,
				'min_file_size' => 1,
				'accept_file_types' => '/.+$/i',
				// The maximum number of files for the upload directory:
				'max_number_of_files' => null,
				// Image resolution restrictions:
				'max_width' => null,
				'max_height' => null,
				'min_width' => 1,
				'min_height' => 1,
				// Set the following option to false to enable resumable uploads:
				'discard_aborted_uploads' => true,
				'image_versions' => array(
						// Uncomment the following version to restrict the size of
						// uploaded images. You can also add additional versions with
						// their own upload directories:
						/*
		'large' => array(
				'upload_dir' => dirname($_SERVER['SCRIPT_FILENAME']).'/files/',
				'upload_url' => $this->getFullUrl().'/files/',
				'max_width' => 1920,
				'max_height' => 1200,
				'jpeg_quality' => 95
		),
		*/
						'thumbnail' => array(
								'upload_dir' => $dir_output . '/' . $object->ref . '/thumbs/',
								'upload_url' => DOL_URL_ROOT.'/document.php?modulepart='.$element.'&attachment=1&file=/'.$object->ref.'/thumbs/',
								'max_width' => 80,
								'max_height' => 80
						)
				)
		);
		if ($options) {
			$this->options = array_replace_recursive($this->options, $options);
		}
	}

	/**
	 *	Return full URL
	 *
	 *	@return	string			URL
	 */
	protected function getFullUrl()
	{
		$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
		return
		($https ? 'https://' : 'http://').
		(!empty($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'].'@' : '').
		(isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : ($_SERVER['SERVER_NAME'].
				($https && $_SERVER['SERVER_PORT'] === 443 ||
						$_SERVER['SERVER_PORT'] === 80 ? '' : ':'.$_SERVER['SERVER_PORT']))).
						substr($_SERVER['SCRIPT_NAME'],0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
	}

	/**
	 * Set delete url
	 *
	 * @param 	string	$file		Filename
	 * @return	void
	 */
	protected function setFileDeleteUrl($file)
	{
		$file->delete_url = $this->options['script_url']
		.'?file='.rawurlencode($file->name).'&fk_element='.$this->fk_element.'&element='.$this->element;
		$file->delete_type = $this->options['delete_type'];
		if ($file->delete_type !== 'DELETE') {
			$file->delete_url .= '&_method=DELETE';
		}
	}

	/**
	 * Enter description here ...
	 *
	 * @param	string		$file_name		Filename
	 * @return 	stdClass|NULL
	 */
	protected function getFileObject($file_name)
	{
		$file_path = $this->options['upload_dir'].$file_name;
		if (is_file($file_path) && $file_name[0] !== '.')
		{
			$file = new stdClass();
			$file->name = $file_name;
			$file->mime = dol_mimetype($file_name,'',2);
			$file->size = filesize($file_path);
			$file->url = $this->options['upload_url'].rawurlencode($file->name);
			foreach($this->options['image_versions'] as $version => $options) {
				if (is_file($options['upload_dir'].$file_name)) {
					$tmp=explode('.',$file->name);
					$file->{$version.'_url'} = $options['upload_url'].rawurlencode($tmp[0].'_mini.'.$tmp[1]);
				}
			}
			$this->setFileDeleteUrl($file);
			return $file;
		}
		return null;
	}

	/**
	 * Enter description here ...
	 *
	 * @return	void
	 */
	protected function getFileObjects()
	{
		return array_values(array_filter(array_map(array($this, 'getFileObject'), scandir($this->options['upload_dir']))));
	}

	/**
	 *  Create thumbs
	 *
	 *  @param	string	$file_name		Filename
	 *  @param	string	$options 		is array('max_width', 'max_height')
	 *  @return	void
	 */
	protected function createScaledImage($file_name, $options)
	{
		global $maxwidthmini, $maxheightmini;

		$file_path = $this->options['upload_dir'].$file_name;
		$new_file_path = $options['upload_dir'].$file_name;

		if (dol_mkdir($options['upload_dir']) >= 0)
		{
			list($img_width, $img_height) = @getimagesize($file_path);
			if (!$img_width || !$img_height) {
				return false;
			}

			$res=vignette($file_path,$maxwidthmini,$maxheightmini,'_mini');

			//return $success;
			if (preg_match('/error/i',$res)) return false;
			return true;
		}
		else
		{
			return false;
		}
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	string	$uploaded_file		Uploade file
	 * @param 	string	$file				File
	 * @param 	string	$error				Error
	 * @param	string	$index				Index
	 * @return unknown|string
	 */
	protected function validate($uploaded_file, $file, $error, $index)
	{
		if ($error) {
			$file->error = $error;
			return false;
		}
		if (!$file->name) {
			$file->error = 'missingFileName';
			return false;
		}
		if (!preg_match($this->options['accept_file_types'], $file->name)) {
			$file->error = 'acceptFileTypes';
			return false;
		}
		if ($uploaded_file && is_uploaded_file($uploaded_file)) {
			$file_size = filesize($uploaded_file);
		} else {
			$file_size = $_SERVER['CONTENT_LENGTH'];
		}
		if ($this->options['max_file_size'] && (
				$file_size > $this->options['max_file_size'] ||
				$file->size > $this->options['max_file_size'])
		) {
			$file->error = 'maxFileSize';
			return false;
		}
		if ($this->options['min_file_size'] &&
				$file_size < $this->options['min_file_size']) {
			$file->error = 'minFileSize';
			return false;
		}
		if (is_int($this->options['max_number_of_files']) && (
				count($this->getFileObjects()) >= $this->options['max_number_of_files'])
		) {
			$file->error = 'maxNumberOfFiles';
			return false;
		}
		list($img_width, $img_height) = @getimagesize($uploaded_file);
		if (is_int($img_width)) {
			if ($this->options['max_width'] && $img_width > $this->options['max_width'] ||
					$this->options['max_height'] && $img_height > $this->options['max_height']) {
				$file->error = 'maxResolution';
				return false;
			}
			if ($this->options['min_width'] && $img_width < $this->options['min_width'] ||
					$this->options['min_height'] && $img_height < $this->options['min_height']) {
				$file->error = 'minResolution';
				return false;
			}
		}
		return true;
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	int		$matches		???
	 * @return	string					???
	 */
	protected function upcountNameCallback($matches)
	{
		$index = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
		$ext = isset($matches[2]) ? $matches[2] : '';
		return ' ('.$index.')'.$ext;
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	string		$name		???
	 * @return	string					???
	 */
	protected function upcountName($name)
	{
		return preg_replace_callback('/(?:(?: \(([\d]+)\))?(\.[^.]+))?$/', array($this, 'upcountNameCallback'), $name, 1);
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	unknown_type $name		Filename
	 * @param 	unknown_type $type		???
	 * @param 	unknown_type $index		???
	 * @return	void
	 */
	protected function trimFileName($name, $type, $index)
	{
		// Remove path information and dots around the filename, to prevent uploading
		// into different directories or replacing hidden system files.
		// Also remove control characters and spaces (\x00..\x20) around the filename:
		$file_name = trim(basename(stripslashes($name)), ".\x00..\x20");
		// Add missing file extension for known image types:
		if (strpos($file_name, '.') === false &&
				preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
			$file_name .= '.'.$matches[1];
		}
		if ($this->options['discard_aborted_uploads'])
		{
			while(is_file($this->options['upload_dir'].$file_name))
			{
				$file_name = $this->upcountName($file_name);
			}
		}
		return $file_name;
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	unknown_type 	$file_path		???
	 * @return	int				Success or not
	 */
	protected function orientImage($file_path)
	{
		$exif = @exif_read_data($file_path);
		if ($exif === false) {
			return false;
		}
		$orientation = intval(@$exif['Orientation']);
		if (!in_array($orientation, array(3, 6, 8))) {
			return false;
		}
		$image = @imagecreatefromjpeg($file_path);
		switch ($orientation) {
			case 3:
				$image = @imagerotate($image, 180, 0);
				break;
			case 6:
				$image = @imagerotate($image, 270, 0);
				break;
			case 8:
				$image = @imagerotate($image, 90, 0);
				break;
			default:
				return false;
		}
		$success = imagejpeg($image, $file_path);
		// Free up memory (imagedestroy does not delete files):
		@imagedestroy($image);
		return $success;
	}

	/**
	 * Enter description here ...
	 *
	 * @param 	string		$uploaded_file		Uploade file
	 * @param 	string		$name				Name
	 * @param 	int			$size				Size
	 * @param 	string		$type				Type
	 * @param 	string		$error				Error
	 * @param	string		$index				Index
	 * @return stdClass
	 */
	protected function handleFileUpload($uploaded_file, $name, $size, $type, $error, $index)
	{
		$file = new stdClass();
		$file->name = $this->trimFileName($name, $type, $index);
		$file->mime = dol_mimetype($file->name,'',2);
		$file->size = intval($size);
		$file->type = $type;
		if ($this->validate($uploaded_file, $file, $error, $index) && dol_mkdir($this->options['upload_dir']) >= 0)
		{
			$file_path = $this->options['upload_dir'].$file->name;
			$append_file = !$this->options['discard_aborted_uploads'] && is_file($file_path) && $file->size > filesize($file_path);
			clearstatcache();
			if ($uploaded_file && is_uploaded_file($uploaded_file)) {
				// multipart/formdata uploads (POST method uploads)
				if ($append_file)
				{
					file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
				} else {
					dol_move_uploaded_file($uploaded_file, $file_path, 1);
				}
			}
			else
			{
				// Non-multipart uploads (PUT method support)
				file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
			}
			$file_size = filesize($file_path);
			if ($file_size === $file->size)
			{
				$file->url = $this->options['upload_url'].rawurlencode($file->name);
				foreach($this->options['image_versions'] as $version => $options)
				{
					if ($this->createScaledImage($file->name, $options))
					{
						$tmp=explode('.',$file->name);
						$file->{$version.'_url'} = $options['upload_url'].rawurlencode($tmp[0].'_mini.'.$tmp[1]);
					}
				}
			}
			else if ($this->options['discard_aborted_uploads'])
			{
				unlink($file_path);
				$file->error = 'abort';
			}
			$file->size = $file_size;
			$this->setFileDeleteUrl($file);
		}
		return $file;
	}

	/**
	 * Output data
	 *
	 * @return	void
	 */
	public function get()
	{
		$file_name = isset($_REQUEST['file']) ?
		basename(stripslashes($_REQUEST['file'])) : null;
		if ($file_name)
		{
			$info = $this->getFileObject($file_name);
		}
		else
		{
			$info = $this->getFileObjects();
		}
		header('Content-type: application/json');
		echo json_encode($info);
	}

	/**
	 * Output data
	 *
	 * @return	void
	 */
	public function post()
	{
		if (isset($_REQUEST['_method']) && $_REQUEST['_method'] === 'DELETE')
		{
			return $this->delete();
		}
		$upload = isset($_FILES[$this->options['param_name']]) ?
		$_FILES[$this->options['param_name']] : null;
		$info = array();
		if ($upload && is_array($upload['tmp_name']))
		{
			// param_name is an array identifier like "files[]",
			// $_FILES is a multi-dimensional array:
			foreach ($upload['tmp_name'] as $index => $value) {
                $info[] = $this->handleFileUpload(
                    $upload['tmp_name'][$index],
                    isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
                    isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
                    isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
                    $upload['error'][$index],
                    $index
                );
			}
		} elseif ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
			// param_name is a single object identifier like "file",
			// $_FILES is a one-dimensional array:
            $info[] = $this->handleFileUpload(
                isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
                isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : (isset($upload['name']) ? $upload['name'] : null),
                isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : (isset($upload['size']) ? $upload['size'] : null),
                isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : (isset($upload['type']) ? $upload['type'] : null),
                isset($upload['error']) ? $upload['error'] : null
            );
		}
		header('Vary: Accept');
		$json = json_encode($info);
		$redirect = isset($_REQUEST['redirect']) ?
		stripslashes($_REQUEST['redirect']) : null;
		if ($redirect) {
			header('Location: '.sprintf($redirect, rawurlencode($json)));
			return;
		}
		if (isset($_SERVER['HTTP_ACCEPT']) &&
				(strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
			header('Content-type: application/json');
		} else {
			header('Content-type: text/plain');
		}
		echo $json;
	}

	/**
	 * Delete uploaded file
	 *
	 * @return	void
	 */
	public function delete()
	{
		$file_name = isset($_REQUEST['file']) ?
		basename(stripslashes($_REQUEST['file'])) : null;
		$file_path = $this->options['upload_dir'].$file_name;
		$success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
		if ($success)
		{
			foreach($this->options['image_versions'] as $version => $options)
			{
				$file = $options['upload_dir'].$file_name;
				if (is_file($file))
				{
					unlink($file);
				}
			}
		}
		header('Content-type: application/json');
		echo json_encode($success);
	}
}
