<?php
/**
 * Smarty plugin
 * @package Smarty
 * @subpackage plugins
 */

/**
* Smarty function plugin
* Requires PHP >= 4.3.0
* -------------------------------------------------------------
* Type:     function
* Name:     fckeditor
* Version:  1.0
* Author:   Auguria info@auguria.net
* Purpose:  Creates a FCKeditor, a very powerful textarea replacement.
* -------------------------------------------------------------
* @param InstanceName Editor instance name (form field name)
* @param Value optional data that control will start with, default is taken from the javascript file
* @param Width optional width (css units)
* @param Height optional height (css units)
* @param ToolbarSet optional what toolbar to use from configuration
* @param CheckBrowser optional check the browser compatibility when rendering the editor
* @param DisplayErrors optional show error messages on errors while rendering the editor
* @param DocumentRoot document root of application
* @param DocumentURLRoot document url root of application
*
* Default values for optional parameters (except BasePath) are taken from fckeditor.js.
*
* All other parameters used in the function will be put into the configuration section,
* CustomConfigurationsPath is useful for example.
* See http://wiki.fckeditor.net/Developer%27s_Guide/Configuration/Configurations_File for more configuration info.
*/


function smarty_function_fckeditor($params, &$smarty)
{
   if(!isset($params['InstanceName']) || empty($params['InstanceName']))
   {
      $smarty->trigger_error('fckeditor: required parameter "InstanceName" missing');
   }

   static $base_arguments = array();
   static $config_arguments = array();

   // Test if editor has been loaded before
   if(!count($base_arguments)) $init = TRUE;
   else $init = FALSE;
   
   // BasePath must be specified once.
   if(isset($params['DocumentRoot']))
   {
      $base_arguments['DocumentRoot'] = $params['DocumentRoot'];
   }
   else if(empty($base_arguments['DocumentRoot']))
   {
      $base_arguments['DocumentRoot'] = '';
   }

   $base_arguments['InstanceName'] = $params['InstanceName'];

  
   if(isset($params['CheckBrowser'])) $base_arguments['CheckBrowser'] = $params['CheckBrowser'];
   if(isset($params['DisplayErrors'])) $base_arguments['DisplayErrors'] = $params['DisplayErrors'];
   
   require_once($params['DocumentRoot']."/includes/fckeditor/fckeditor.php");
	$editor = new FCKeditor( $params['InstanceName']);
	$editor->BasePath = $params['DocumentUrlRoot'].'/includes/fckeditor/' ;
	$editor->Value	= $params['Value'];
	$editor->Height   =  $params['Height'];
	$editor->ToolbarSet = $params['ToolbarSet'];
	$editor->Config['AutoDetectLanguage'] = 'true';
	$editor->Config['ToolbarLocation'] = $params['ToolbarLocation'] ? $params['ToolbarLocation'] : 'In';
	$editor->Config['ToolbarStartExpanded'] = $params['ToolbarStartExpanded'];

   // Use all other parameters for the config array (replace if needed)
   $modulepart='fckeditor';
	$editor->Config['UserFilesPath'] = '/viewimage.php?modulepart='.$modulepart.'&file=';
	$editor->Config['UserFilesAbsolutePath'] = $params['DocumentUrlRoot'].'/'.$modulepart.'/' ;

    $editor->Config['LinkBrowser']=($params['LinkBrowser']?'true':'false');
    $editor->Config['ImageBrowser']=($params['ImageBrowser']?'true':'false');

    	if ($params['Theme'] && file_exists($params['DocumentRoot'].'/theme/'.$params['Theme']))
    	{
    		$editor->Config['CustomConfigurationsPath'] = $params['DocumentUrlRoot'].'/theme/'.$params['Theme'].'/fckeditor/fckconfig.js';
    		$editor->Config['SkinPath'] = $params['DocumentUrlRoot'].'/theme/'.$params['Theme'].'/fckeditor/';
		  }

   $editor->Create();
   return "";
}

/* vim: set expandtab: */

?> 