<?php
/* Copyright (C) 2011 Regis Houssin			<regis@dolibarr.fr>
 * Copyright (C) 2011 Laurent Destailleur	<eldy@users.sourceforge.net>
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

//if (! defined('NOREQUIREUSER'))  define('NOREQUIREUSER','1');
//if (! defined('NOREQUIREDB'))    define('NOREQUIREDB','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN'))  define('NOREQUIRETRAN','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');
if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL','1');
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1'); // If there is no menu to show
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1'); // If we don't need to load the html.form.class.php
//if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
//if (! defined("NOLOGIN"))        define("NOLOGIN",'1');       // If this page is public (can be called outside logged session)


require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/images.lib.php");

error_reporting(E_ALL | E_STRICT);

//print_r($_POST);
//print_r($_GET);
//print 'upload_dir='.GETPOST('upload_dir');

$fk_element = GETPOST('fk_element');
$element = GETPOST('element');


/**
 *       \file       htdocs/core/ajax/fileupload.php
 *       \brief      This class is used to manage file upload using ajax
 */
class UploadHandler
{
    private $_options;
    private $_fk_element;
    private $_element;


    /**
     * Constructor
     *
     * @param array		$options		Options array
     * @param int		$fk_element		fk_element
     * @param string	$element		element
     */
    function __construct($options=null,$fk_element=null,$element=null)
    {

    	global $conf;

    	$this->_fk_element=$fk_element;
    	$this->_element=$element;

        $this->_options = array(
            'script_url' => $_SERVER['PHP_SELF'],
            'upload_dir' => $conf->$element->dir_output . '/' . $fk_element . '/',
            'upload_url' => DOL_URL_ROOT.'/document.php?modulepart='.$element.'&attachment=1&file=/'.$fk_element.'/',
            'param_name' => 'files',
            // The php.ini settings upload_max_filesize and post_max_size
            // take precedence over the following max_file_size setting:
            'max_file_size' => null,
            'min_file_size' => 1,
            'accept_file_types' => '/.+$/i',
            'max_number_of_files' => null,
            'discard_aborted_uploads' => true,
            'image_versions' => array(
                // Uncomment the following version to restrict the size of
                // uploaded images. You can also add additional versions with
                // their own upload directories:
                /*
                'small' => array(
                    'upload_dir' => dirname(__FILE__).'/files/',
                    'upload_url' => dirname($_SERVER['PHP_SELF']).'/files/'
                ),
                */
                'thumbs' => array(
                    'upload_dir' => $conf->$element->dir_output . '/' . $fk_element . '/thumbs/',
                    'upload_url' => DOL_URL_ROOT.'/document.php?modulepart='.$element.'&attachment=1&file=/'.$fk_element.'/thumbs/'
                )
            )
        );
        if ($options) {
            $this->_options = array_merge_recursive($this->_options, $options);
        }
    }

    /**
     * Enter description here ...
     *
     * @param	string		$file_name		Filename
     * @return 	stdClass|NULL
     */
    private function get_file_object($file_name)
    {
        $file_path = $this->_options['upload_dir'].$file_name;
        if (is_file($file_path) && $file_name[0] !== '.')
        {
            $file = new stdClass();
            $file->name = $file_name;
            $file->mime = dol_mimetype($file_name,'',2);
            $file->size = filesize($file_path);
            $file->url = $this->_options['upload_url'].rawurlencode($file->name);
            foreach($this->_options['image_versions'] as $version => $options) {
                if (is_file($options['upload_dir'].$file_name)) {
                    $tmp=explode('.',$file->name);
                    $file->{$version.'_url'} = $options['upload_url'].rawurlencode($tmp[0].'_mini.'.$tmp[1]);
                }
            }
            $file->delete_url = $this->_options['script_url']
                .'?file='.rawurlencode($file->name).'&fk_element='.$this->_fk_element.'&element='.$this->_element;
            $file->delete_type = 'DELETE';
            return $file;
        }
        return null;
    }

    /**
     * Enter description here ...
     *
     * @return	void
     */
    private function get_file_objects()
    {
        return array_values(array_filter(array_map(array($this, 'get_file_object'), scandir($this->_options['upload_dir']))));
    }

    /**
     *  Create thumbs
     *
     *  @param	string	$file_name		Filename
     *  @param	string	$options 		is array('max_width', 'max_height')
     *  @return	void
     */
    private function create_scaled_image($file_name, $options)
    {
        global $maxwidthmini, $maxheightmini;
        $file_path = $this->_options['upload_dir'].$file_name;
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
     * @return unknown|string
     */
    private function has_error($uploaded_file, $file, $error)
    {
        if ($error) {
            return $error;
        }
        if (!preg_match($this->_options['accept_file_types'], $file->name)) {
            return 'acceptFileTypes';
        }
        if ($uploaded_file && is_uploaded_file($uploaded_file)) {
            $file_size = filesize($uploaded_file);
        } else {
            $file_size = $_SERVER['CONTENT_LENGTH'];
        }
        if ($this->_options['max_file_size'] && (
                $file_size > $this->_options['max_file_size'] ||
                $file->size > $this->_options['max_file_size'])
            ) {
            return 'maxFileSize';
        }
        if ($this->_options['min_file_size'] &&
            $file_size < $this->_options['min_file_size']) {
            return 'minFileSize';
        }
        if (is_int($this->_options['max_number_of_files']) && (
                count($this->get_file_objects()) >= $this->_options['max_number_of_files'])
            ) {
            return 'maxNumberOfFiles';
        }
        return $error;
    }

    /**
     * Enter description here ...
     *
     * @param 	string		$uploaded_file		Uploade file
     * @param 	string		$name				Name
     * @param 	int			$size				Size
     * @param 	string		$type				Type
     * @param 	string		$error				Error
     * @return stdClass
     */
    private function handle_file_upload($uploaded_file, $name, $size, $type, $error)
    {
        $file = new stdClass();
        $file->name = basename(stripslashes($name));
        $file->mime = dol_mimetype($file->name,'',2);
        $file->size = intval($size);
        $file->type = $type;
        $error = $this->has_error($uploaded_file, $file, $error);
        if (!$error && $file->name && dol_mkdir($this->_options['upload_dir']) >= 0) {
            if ($file->name[0] === '.') {
                $file->name = substr($file->name, 1);
            }
            $file_path = $this->_options['upload_dir'].$file->name;
            $append_file = is_file($file_path) && $file->size > filesize($file_path);
            clearstatcache();
            if ($uploaded_file && is_uploaded_file($uploaded_file)) {
                // multipart/formdata uploads (POST method uploads)
                if ($append_file) {
                    file_put_contents(
                        $file_path,
                        fopen($uploaded_file, 'r'),
                        FILE_APPEND
                    );
                } else {
                    // FIXME problem with trigger
                	dol_move_uploaded_file($uploaded_file, $file_path, 1, 0, 0, 1);
                }
            } else {
                // Non-multipart uploads (PUT method support)
                file_put_contents(
                    $file_path,
                    fopen('php://input', 'r'),
                    $append_file ? FILE_APPEND : 0
                );
            }
            $file_size = filesize($file_path);
            if ($file_size === $file->size) {
                $file->url = $this->_options['upload_url'].rawurlencode($file->name);
                foreach($this->_options['image_versions'] as $version => $options)
                {
                    if ($this->create_scaled_image($file->name, $options))
                    {
                        $tmp=explode('.',$file->name);
                        $file->{$version.'_url'} = $options['upload_url'].rawurlencode($tmp[0].'_mini.'.$tmp[1]);
                    }
                }
            } else if ($this->_options['discard_aborted_uploads']) {
                unlink($file_path);
                $file->error = 'abort';
            }
            $file->size = $file_size;
            $file->delete_url = $this->_options['script_url']
                .'?file='.rawurlencode($file->name).'&fk_element='.$this->_fk_element.'&element='.$this->_element;
            $file->delete_type = 'DELETE';
        } else {
            $file->error = $error;
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
        if ($file_name) {
            $info = $this->get_file_object($file_name);
        } else {
            $info = $this->get_file_objects();
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
        $upload = isset($_FILES[$this->_options['param_name']]) ?
            $_FILES[$this->_options['param_name']] : array(
                'tmp_name' => null,
                'name' => null,
                'size' => null,
                'type' => null,
                'error' => null
            );
        $info = array();
        if (is_array($upload['tmp_name'])) {
            foreach ($upload['tmp_name'] as $index => $value) {
                $info[] = $this->handle_file_upload(
                    $upload['tmp_name'][$index],
                    isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'][$index],
                    isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'][$index],
                    isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'][$index],
                    $upload['error'][$index]
                );
            }
        } else {
            $info[] = $this->handle_file_upload(
                $upload['tmp_name'],
                isset($_SERVER['HTTP_X_FILE_NAME']) ? $_SERVER['HTTP_X_FILE_NAME'] : $upload['name'],
                isset($_SERVER['HTTP_X_FILE_SIZE']) ? $_SERVER['HTTP_X_FILE_SIZE'] : $upload['size'],
                isset($_SERVER['HTTP_X_FILE_TYPE']) ? $_SERVER['HTTP_X_FILE_TYPE'] : $upload['type'],
                $upload['error']
            );
        }
        header('Vary: Accept');
        if (isset($_SERVER['HTTP_ACCEPT']) &&
            (strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)) {
            header('Content-type: application/json');
        } else {
            header('Content-type: text/plain');
        }
        echo json_encode($info);
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
        $file_path = $this->_options['upload_dir'].$file_name;
        $success = is_file($file_path) && $file_name[0] !== '.' && unlink($file_path);
        if ($success) {
            foreach($this->_options['image_versions'] as $version => $options) {
                $file = $options['upload_dir'].$file_name;
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }
        header('Content-type: application/json');
        echo json_encode($success);
    }
}



/*
 * View
 */

$upload_handler = new UploadHandler(null,$fk_element,$element);

header('Pragma: no-cache');
header('Cache-Control: private, no-cache');
header('Content-Disposition: inline; filename="files.json"');

switch ($_SERVER['REQUEST_METHOD']) {
    case 'HEAD':
    case 'GET':
        $upload_handler->get();
        break;
    case 'POST':
        $upload_handler->post();
        break;
    case 'DELETE':
        $upload_handler->delete();
        break;
    default:
        header('HTTP/1.0 405 Method Not Allowed');
        exit;
}


$db->close();

?>