<?php
/* Copyright (C) 2011-2022	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2011-2023	Laurent Destailleur	<eldy@users.sourceforge.net>
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
 *       \file      htdocs/core/class/fileupload.class.php
 *       \brief     File to return the ajax response of core/ajax/fileupload.php for common file upload.
 *       			Security is check by the ajax component.
 *       			For large files, see flowjs-server.php
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';


/**
 *	This class is used to manage file upload using ajax
 */
class FileUpload
{
	public $options;
	protected $fk_element;
	protected $element;

	/**
	 * Constructor.
	 * This set ->$options
	 *
	 * @param array		$options		Options array
	 * @param int		$fk_element		ID of element
	 * @param string	$element		Code of element
	 */
	public function __construct($options = null, $fk_element = null, $element = null)
	{
		global $db;
		global $hookmanager;

		$hookmanager->initHooks(array('fileupload'));

		$element_prop = getElementProperties($element);
		//var_dump($element_prop);

		$this->fk_element = $fk_element;
		$this->element = $element;

		$pathname = str_replace('/class', '', $element_prop['classpath']);
		$filename = dol_sanitizeFileName($element_prop['classfile']);
		$dir_output = dol_sanitizePathName($element_prop['dir_output']);

		//print 'fileupload.class.php: element='.$element.' pathname='.$pathname.' filename='.$filename.' dir_output='.$dir_output."\n";

		if (empty($dir_output)) {
			setEventMessage('The element '.$element.' is not supported for uploading file. dir_output is unknown.', 'errors');
			throw new Exception('The element '.$element.' is not supported for uploading file. dir_output is unknown.');
		}

		// If pathname and filename are null then we can still upload files if we have specified upload_dir on $options
		if ($pathname !== null && $filename !== null) {
			// Get object from its id and type
			$object = fetchObjectByElement($fk_element, $element);

			$object_ref = dol_sanitizeFileName($object->ref);

			// Special cases to forge $object_ref used to forge $upload_dir
			if ($element == 'invoice_supplier') {
				$object_ref = get_exdir($object->id, 2, 0, 0, $object, 'invoice_supplier').$object_ref;
			} elseif ($element == 'project_task') {
				$parentForeignKey = 'fk_project';
				$parentClass = 'Project';
				$parentElement = 'projet';
				$parentObject = 'project';

				dol_include_once('/'.$parentElement.'/class/'.$parentObject.'.class.php');
				$parent = new $parentClass($db);
				$parent->fetch($object->$parentForeignKey);
				if (!empty($parent->socid)) {
					$parent->fetch_thirdparty();
				}
				$object->$parentObject = clone $parent;

				$object_ref = dol_sanitizeFileName($object->project->ref).'/'.$object_ref;
			}
		}

		$this->options = array(
			'script_url' => $_SERVER['PHP_SELF'],
			'upload_dir' => $dir_output.'/'.$object_ref.'/',
			'upload_url' => DOL_URL_ROOT.'/document.php?modulepart='.$element.'&attachment=1&file=/'.$object_ref.'/',
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
					'upload_dir' => $dir_output.'/'.$object_ref.'/thumbs/',
					'upload_url' => DOL_URL_ROOT.'/document.php?modulepart='.urlencode($element).'&attachment=1&file='.urlencode('/'.$object_ref.'/thumbs/'),
					'max_width' => 80,
					'max_height' => 80
				)
			)
		);

		global $action;

		$hookmanager->executeHooks(
			'overrideUploadOptions',
			array(
				'options' => &$options,
				'element' => $element
			),
			$object,
			$action
		);

		if ($options) {
			$this->options = array_replace_recursive($this->options, $options);
		}

		// At this point we should have a valid upload_dir in options
		//if ($pathname === null && $filename === null) { // OR or AND???
		if ($pathname === null || $filename === null) {
			if (!array_key_exists("upload_dir", $this->options)) {
				setEventMessage('If $fk_element = null or $element = null you must specify upload_dir on $options', 'errors');
				throw new Exception('If $fk_element = null or $element = null you must specify upload_dir on $options');
			} elseif (!is_dir($this->options['upload_dir'])) {
				setEventMessage('The directory '.$this->options['upload_dir'].' doesn\'t exists', 'errors');
				throw new Exception('The directory '.$this->options['upload_dir'].' doesn\'t exists');
			} elseif (!is_writable($this->options['upload_dir'])) {
				setEventMessage('The directory '.$this->options['upload_dir'].' is not writable', 'errors');
				throw new Exception('The directory '.$this->options['upload_dir'].' is not writable');
			}
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
						substr($_SERVER['SCRIPT_NAME'], 0, strrpos($_SERVER['SCRIPT_NAME'], '/'));
	}

	/**
	 * Set delete url
	 *
	 * @param 	object	$file		Filename
	 * @return	void
	 */
	protected function setFileDeleteUrl($file)
	{
		$file->delete_url = $this->options['script_url'].'?file='.urlencode((string) ($file->name)).'&fk_element='.urlencode((string) ($this->fk_element)).'&element='.urlencode((string) ($this->element));
		$file->delete_type = $this->options['delete_type'];
		if ($file->delete_type !== 'DELETE') {
			$file->delete_url .= '&_method=DELETE';
		}
	}

	/**
	 * getFileObject
	 *
	 * @param	string		$file_name		Filename
	 * @return 	stdClass|null
	 */
	protected function getFileObject($file_name)
	{
		$file_path = $this->options['upload_dir'].dol_sanitizeFileName($file_name);

		if (dol_is_file($file_path) && $file_name[0] !== '.') {
			$file = new stdClass();
			$file->name = $file_name;
			$file->mime = dol_mimetype($file_name, '', 2);
			$file->size = filesize($file_path);
			$file->url = $this->options['upload_url'].urlencode($file->name);

			foreach ($this->options['image_versions'] as $version => $options) {
				if (dol_is_file($options['upload_dir'].$file_name)) {
					$tmp = explode('.', $file->name);

					// We save the path of mini file into file->... (seems not used)
					$keyforfile = $version.'_url';
					$file->$keyforfile = $options['upload_url'].urlencode($tmp[0].'_mini.'.$tmp[1]);
				}
			}
			$this->setFileDeleteUrl($file);
			return $file;
		}
		return null;
	}

	/**
	 * getFileObjects
	 *
	 * @return	array	Array of objects
	 */
	protected function getFileObjects()
	{
		return array_values(array_filter(array_map(array($this, 'getFileObject'), scandir($this->options['upload_dir']))));
	}

	/**
	 *  Create thumbs of a file uploaded.
	 *
	 *  @param	string	$file_name		Filename
	 *  @param	string	$options 		is array('max_width', 'max_height')
	 *  @return	boolean
	 */
	protected function createScaledImage($file_name, $options)
	{
		global $maxwidthmini, $maxheightmini, $maxwidthsmall, $maxheightsmall;

		$file_path = $this->options['upload_dir'].$file_name;
		$new_file_path = $options['upload_dir'].$file_name;

		if (dol_mkdir($options['upload_dir']) >= 0) {
			list($img_width, $img_height) = @getimagesize($file_path);
			if (!$img_width || !$img_height) {
				return false;
			}

			$res = vignette($file_path, $maxwidthmini, $maxheightmini, '_mini'); // We don't use ->addThumbs here because there is no object
			if (preg_match('/error/i', $res)) {
				return false;
			}

			$res = vignette($file_path, $maxwidthsmall, $maxheightsmall, '_small'); // We don't use ->addThumbs here because there is no object
			if (preg_match('/error/i', $res)) {
				return false;
			}

			return true;
		} else {
			return false;
		}
	}

	/**
	 * Make validation on an uploaded file
	 *
	 * @param 	string	$uploaded_file		Upload file
	 * @param 	object	$file				File
	 * @param 	string	$error				Error
	 * @param	string	$index				Index
	 * @return  boolean                     True if OK, False if KO
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
			$file_size = dol_filesize($uploaded_file);
		} else {
			$file_size = $_SERVER['CONTENT_LENGTH'];
		}
		if ($this->options['max_file_size'] && (
			$file_size > $this->options['max_file_size'] ||
				$file->size > $this->options['max_file_size']
		)
		) {
			$file->error = 'maxFileSize';
			return false;
		}
		if ($this->options['min_file_size'] &&
				$file_size < $this->options['min_file_size']) {
			$file->error = 'minFileSize';
			return false;
		}
		if (is_numeric($this->options['max_number_of_files']) && (
			count($this->getFileObjects()) >= $this->options['max_number_of_files']
		)
		) {
			$file->error = 'maxNumberOfFiles';
			return false;
		}
		list($img_width, $img_height) = @getimagesize($uploaded_file);
		if (is_numeric($img_width)) {
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
	 * trimFileName
	 *
	 * @param 	string $name		Filename
	 * @param 	string $type		???
	 * @param 	string $index		???
	 * @return	string
	 */
	protected function trimFileName($name, $type, $index)
	{
		// Remove path information and dots around the filename, to prevent uploading
		// into different directories or replacing hidden system files.
		$file_name = basename(dol_sanitizeFileName($name));
		// Add missing file extension for known image types:
		$matches = array();
		if (strpos($file_name, '.') === false && preg_match('/^image\/(gif|jpe?g|png)/', $type, $matches)) {
			$file_name .= '.'.$matches[1];
		}
		if ($this->options['discard_aborted_uploads']) {
			while (dol_is_file($this->options['upload_dir'].$file_name)) {
				$file_name = $this->upcountName($file_name);
			}
		}
		return $file_name;
	}

	/**
	 * handleFileUpload.
	 * Validate data, move the uploaded file then create the thumbs if this is an image.
	 *
	 * @param 	string		$uploaded_file		Upload file
	 * @param 	string		$name				Name
	 * @param 	int			$size				Size
	 * @param 	string		$type				Type
	 * @param 	string		$error				Error
	 * @param	string		$index				Index
	 * @return stdClass|null
	 */
	protected function handleFileUpload($uploaded_file, $name, $size, $type, $error, $index)
	{
		$file = new stdClass();
		$file->name = $this->trimFileName($name, $type, $index);
		$file->mime = dol_mimetype($file->name, '', 2);
		$file->size = intval($size);
		$file->type = $type;

		// Sanitize to avoid stream execution when calling file_size(). Not that this is a second security because
		// most streams are already disabled by stream_wrapper_unregister() in filefunc.inc.php
		$uploaded_file = preg_replace('/\s*(http|ftp)s?:/i', '', $uploaded_file);
		$uploaded_file = realpath($uploaded_file);	// A hack to be sure the file point to an existing file on disk (and is not a SSRF attack)

		$validate = $this->validate($uploaded_file, $file, $error, $index);

		if ($validate) {
			if (dol_mkdir($this->options['upload_dir']) >= 0) {
				$file_path = dol_sanitizePathName($this->options['upload_dir']).dol_sanitizeFileName($file->name);
				$append_file = !$this->options['discard_aborted_uploads'] && dol_is_file($file_path) && $file->size > dol_filesize($file_path);

				clearstatcache();

				if ($uploaded_file && is_uploaded_file($uploaded_file)) {
					// multipart/formdata uploads (POST method uploads)
					if ($append_file) {
						file_put_contents($file_path, fopen($uploaded_file, 'r'), FILE_APPEND);
					} else {
						$result = dol_move_uploaded_file($uploaded_file, $file_path, 1, 0, 0, 0, 'userfile');
					}
				} else {
					// Non-multipart uploads (PUT method support)
					file_put_contents($file_path, fopen('php://input', 'r'), $append_file ? FILE_APPEND : 0);
				}
				$file_size = dol_filesize($file_path);
				if ($file_size === $file->size) {
					$file->url = $this->options['upload_url'].urlencode($file->name);
					foreach ($this->options['image_versions'] as $version => $options) {
						if ($this->createScaledImage($file->name, $options)) {	// Creation of thumbs mini and small is ok
							$tmp = explode('.', $file->name);

							// We save the path of mini file into file->... (seems not used)
							$keyforfile = $version.'_url';
							$file->$keyforfile = $options['upload_url'].urlencode($tmp[0].'_mini.'.$tmp[1]);
						}
					}
				} elseif ($this->options['discard_aborted_uploads']) {
					unlink($file_path);
					$file->error = 'abort';
				}
				$file->size = $file_size;
				$this->setFileDeleteUrl($file);
			} else {
				$file->error = 'failedtocreatedestdir';
			}
		} else {
			// should not happen
		}

		return $file;
	}

	/**
	 * Output data
	 *
	 * @return	void
	 */
	/*public function get()
	{
		$file_name = isset($_REQUEST['file']) ? basename(stripslashes($_REQUEST['file'])) : null;
		if ($file_name) {
			$info = $this->getFileObject($file_name);
		} else {
			$info = $this->getFileObjects();
		}

		header('Content-type: application/json');
		echo json_encode($info);
	}
	*/

	/**
	 * Output data
	 *
	 * @return	int			0 if OK, nb of error if errors
	 */
	public function post()
	{
		$error = 0;

		$upload = isset($_FILES[$this->options['param_name']]) ? $_FILES[$this->options['param_name']] : null;

		$info = array();
		if ($upload && is_array($upload['tmp_name'])) {
			// param_name is an array identifier like "files[]",
			// $_FILES is a multi-dimensional array:
			foreach ($upload['tmp_name'] as $index => $value) {
				$tmpres = $this->handleFileUpload(
					$upload['tmp_name'][$index],
					isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
					isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
					isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
					$upload['error'][$index],
					$index
				);
				if (!empty($tmpres->error)) {
					$error++;
				}
				$info[] = $tmpres;
			}
		} elseif ($upload || isset($_SERVER['HTTP_X_FILE_NAME'])) {
			// param_name is a single object identifier like "file",
			// $_FILES is a one-dimensional array:
			$tmpres = $this->handleFileUpload(
				isset($upload['tmp_name']) ? $upload['tmp_name'] : null,
				isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : (isset($upload['name']) ? $upload['name'] : null),
				isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : (isset($upload['size']) ? $upload['size'] : null),
				isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : (isset($upload['type']) ? $upload['type'] : null),
				isset($upload['error']) ? $upload['error'] : null,
				0
			);
			if (!empty($tmpres->error)) {
				$error++;
			}
			$info[] = $tmpres;
		}

		header('Vary: Accept');
		$json = json_encode($info);

		/* disabled. Param redirect seems not used
		$redirect = isset($_REQUEST['redirect']) ? stripslashes($_REQUEST['redirect']) : null;
		if ($redirect) {
			header('Location: '.sprintf($redirect, urlencode($json)));
			return;
		}
		*/

		if (isset($_SERVER['HTTP_ACCEPT']) && (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
			header('Content-type: application/json');
		} else {
			header('Content-type: text/plain');
		}
		echo $json;

		return $error;
	}

	/**
	 * Delete uploaded file
	 *
	 * @param	string	$file	File
	 * @return	int
	 */
	/*
	public function delete($file)
	{
		$file_name = $file ? basename($file) : null;
		$file_path = $this->options['upload_dir'].dol_sanitizeFileName($file_name);
		$success = dol_is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
		if ($success) {
			foreach ($this->options['image_versions'] as $version => $options) {
				$file = $options['upload_dir'].$file_name;
				if (dol_is_file($file)) {
					unlink($file);
				}
			}
		}
		// Return result in json format
		header('Content-type: application/json');
		echo json_encode($success);

		return 0;
	}
	*/
}
