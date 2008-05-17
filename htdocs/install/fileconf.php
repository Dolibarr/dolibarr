<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org> 
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2004      Sebastien DiCintio   <sdicintio@ressource-toi.org>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/install/fileconf.php
        \ingroup    install
        \brief      Demande les infos qui constituerons le contenu du fichier conf.php. Ce fichier sera remplie � l'�tape suivante
        \version    $Id$
*/
include_once("./inc.php");


$err=0;

$setuplang=isset($_POST["selectlang"])?$_POST["selectlang"]:(isset($_GET["selectlang"])?$_GET["selectlang"]:'auto');
$langs->setDefaultLang($setuplang);

$langs->load("install");

// Init "forced values" to nothing. "forced values" are used after an doliwamp install wizard.
if (! isset($force_install_type))              $force_install_type='';
if (! isset($force_install_port))              $force_install_port='';
if (! isset($force_install_database))          $force_install_database='';
if (! isset($force_install_createdatabase))    $force_install_createdatabase='';
if (! isset($force_install_databaselogin))     $force_install_databaselogin='';
if (! isset($force_install_databasepass))      $force_install_databasepass='';
if (! isset($force_install_databaserootlogin)) $force_install_databaserootlogin='';
if (! isset($force_install_databaserootpass))  $force_install_databaserootpass='';
if (file_exists("./install.forced.php")) include_once("./install.forced.php");

dolibarr_install_syslog("Fileconf: Entering fileconf.php page");


/*
*	View
*/

pHeader($langs->trans("ConfigurationFile"),"etape0");

if ($force_install_message_doliwamp)
{
	print $langs->trans("KeepDefaultValues").'<br>';	
}


print '<table border="0" cellpadding="1" cellspacing="0">';

?>
<tr>
<td colspan="3" class="label" align="center"><h3>
<?php echo $langs->trans("Main"); ?>
</h3></td></tr>
<?php

print '<tr>';
print '<td valign="top" class="label"><b>';
print $langs->trans("WebPagesDirectory");
print "</b></td>";

if(! isset($dolibarr_main_url_root) || strlen($dolibarr_main_url_root) == 0)
{
    //print "x".$_SERVER["SCRIPT_FILENAME"]." y".$_SERVER["DOCUMENT_ROOT"];

    // Si le php fonctionne en CGI, alors SCRIPT_FILENAME vaut le path du php et
    // ce n'est pas ce qu'on veut. Dans ce cas, on propose $_SERVER["DOCUMENT_ROOT"]
    if (eregi('^php$',$_SERVER["SCRIPT_FILENAME"]) || eregi('[\\\/]php$',$_SERVER["SCRIPT_FILENAME"]) || eregi('php\.exe$',$_SERVER["SCRIPT_FILENAME"]))
    {
        $dolibarr_main_document_root=$_SERVER["DOCUMENT_ROOT"];

        if (! eregi('[\/\\]dolibarr[\/\\]htdocs$',$dolibarr_main_document_root))
        {
            $dolibarr_main_document_root.="/dolibarr/htdocs";
        }
    }
    else
    {
        $dolibarr_main_document_root = substr($_SERVER["SCRIPT_FILENAME"],0,strlen($_SERVER["SCRIPT_FILENAME"]) - 21);
        // Nettoyage du path propose
        // Gere les chemins windows avec double "\"
        $dolibarr_main_document_root = str_replace('\\\\','/',$dolibarr_main_document_root);

        // Supprime les slash ou antislash de fins
        $dolibarr_main_document_root = ereg_replace('[\\\/]+$','',$dolibarr_main_document_root);
    }
}
//echo $PMA_MYSQL_INT_VERSION;
?>
<td  class="label" valign="top"><input type="text" size="60" value="<?php print $dolibarr_main_document_root; ?>" name="main_dir">
</td><td class="comment">
<?php
print $langs->trans("WithNoSlashAtTheEnd")."<br>";
print $langs->trans("Examples").":<br>";
?>
<li>/var/www/dolibarr/htdocs</li>
<li>C:/wwwroot/dolibarr/htdocs</li>
</td>
</tr>

<tr>
<td valign="top" class="label"><b>
<?php print $langs->trans("DocumentsDirectory"); ?>
</b></td>
<?php 
if (empty($dolibarr_main_data_root))
{
    // Si le repertoire documents non defini, on en propose un par defaut
    if (empty($force_install_main_data_root))
	{
		$dolibarr_main_data_root=ereg_replace("/htdocs$","",$dolibarr_main_document_root);
		$dolibarr_main_data_root.="/documents";
	}
	else
	{
		$dolibarr_main_data_root=$force_install_main_data_root;
	}
}
?>
<td class="label" valign="top"><input type="text" size="60" value="<?php print $dolibarr_main_data_root; ?>" name="main_data_dir">
</td><td class="comment">
<?php
print $langs->trans("WithNoSlashAtTheEnd")."<br>";
print $langs->trans("DirectoryRecommendation")."<br>";
print $langs->trans("Examples").":<br>";
?>
<li>/var/dolibarr_documents</li>
<li>C:/My Documents/dolibarr/</li>
</td>
</tr>

<tr>
<td valign="top" class="label"><b>
<?php echo $langs->trans("URLRoot"); ?>
</b></td><td valign="top" class="label"><input type="text" size="60" name="main_url" value="
<?php 
if (isset($main_url) && $main_url)
  $dolibarr_main_url_root=$main_url;
if (! isset($dolibarr_main_url_root) || strlen($dolibarr_main_url_root) == 0)
{
	# If defined (Ex: Apache sous Linux)
	if (isset($_SERVER["SCRIPT_URI"])) {
		$dolibarr_main_url_root=$_SERVER["SCRIPT_URI"];
	}
	# If defined (Ex: Apache sous Caudium)
	elseif (isset($_SERVER["SERVER_URL"]) && isset($_SERVER["DOCUMENT_URI"])) {
		$dolibarr_main_url_root=$_SERVER["SERVER_URL"].$_SERVER["DOCUMENT_URI"];
	}
	# Si SCRIPT_URI, SERVER_URL, DOCUMENT_URI non d�fini (Ex: Apache 2.0.44 pour Windows)
	else
	{										
		$proto='http';
		if (! empty($_SERVER["HTTP_HOST"])) $serverport=$_SERVER["HTTP_HOST"];
		else $serverport=$_SERVER["SERVER_NAME"];
		$dolibarr_main_url_root=$proto."://".$serverport.$_SERVER["SCRIPT_NAME"];
	}
	# Clean proposed URL
	$dolibarr_main_url_root = ereg_replace('\/fileconf\.php$','',$dolibarr_main_url_root);	# Supprime le /fileconf.php
	$dolibarr_main_url_root = ereg_replace('\/$','',$dolibarr_main_url_root);				# Supprime le /
	$dolibarr_main_url_root = ereg_replace('\/index\.php$','',$dolibarr_main_url_root);		# Supprime le /index.php
	$dolibarr_main_url_root = ereg_replace('\/install$','',$dolibarr_main_url_root);		# Supprime le /install
}

print $dolibarr_main_url_root;
?>">
</td><td class="comment">
<?php
print $langs->trans("Examples").":<br>";
?>
<li>http://localhost/</li>
<li>http://www.myserver.com:8180/dolibarr</li>
</tr>

<!-- Dolibarr database -->

<tr>
<td colspan="3" class="label" align="center"><br><h3>
<?php echo $langs->trans("DolibarrDatabase"); ?>
</h3></td>
</tr>
<?php
if (!isset($dolibarr_main_db_host))
{
$dolibarr_main_db_host = "localhost";
}
?>
<tr>
<!-- moi-->
<td valign="top" class="label"><b>
<?php echo $langs->trans("DriverType"); ?>
</b></td>

<td class="label">
<?php

$defaultype=! empty($dolibarr_main_db_type)?$dolibarr_main_db_type:($force_install_type?$force_install_type:'mysql');

// Scan les drivers
$dir=DOL_DOCUMENT_ROOT.'/lib/databases';
$handle=opendir($dir);
$modules = array();
$nbok = $nbko = 0;
$option='';

while (($file = readdir($handle))!==false)
{
    if (is_readable($dir."/".$file) && eregi('^(.*)\.lib\.php',$file,$reg))
    {
        $type=$reg[1];

		// Version min de la base
		$versionbasemin=array();
		if ($type=='mysql')  { $versionbasemin=array(3,1,0); $testfunction='mysql_connect'; }
		if ($type=='mysqli') { $versionbasemin=array(4,1,0); $testfunction='mysqli_connect'; }
		if ($type=='pgsql')  { $versionbasemin=array(8,1,0); $testfunction='pg_connect'; }
		if ($type=='mssql')  { $versionbasemin=array(2000);  $testfunction='mssql_connect'; }
		
		// Remarques
		$note='';
		if ($type=='mysql') 	$note='(Mysql >= '.versiontostring($versionbasemin).')';
		if ($type=='mysqli') 	$note='(Mysql >= '.versiontostring($versionbasemin).')';
		if ($type=='pgsql') 	$note='(Postgresql >= '.versiontostring($versionbasemin).')';
		if ($type=='mssql') 	$note='(SQL Server >= '.versiontostring($versionbasemin).')';

		// Affiche ligne dans liste
		$option.='<option value="'.$type.'"'.($defaultype == $type?' selected':'');
		if (! function_exists($testfunction)) $option.=' disabled="disabled"';
		$option.='>';
		$option.=$type.'&nbsp; &nbsp;';
		if ($note) $option.=' '.$note;
		// Experimental
		if ($type=='pgsql')     $option.=' '.$langs->trans("Experimental");
		elseif ($type=='mssql') $option.=' '.$langs->trans("Experimental");
		// No available
		elseif (! function_exists($testfunction)) $option.=' - '.$langs->trans("FunctionNotAvailableInThisPHP");
		$option.='</option>';
    }
}

?>
<select name='db_type'>
<?php echo $option ?>
</select>
&nbsp;
</td>

<td class="comment">
<?php echo $langs->trans("DatabaseType"); ?>
</td>

</tr>

<tr>
<td valign="top" class="label"><b>
<?php echo $langs->trans("Server"); ?>
</b></td>
<td valign="top" class="label"><input type="text" name="db_host" value="<?php print (! empty($dolibarr_main_db_host))?$dolibarr_main_db_host:'localhost'; ?>">
<input type="hidden" name="base" value="">
</td>
<td class="comment">
<?php echo $langs->trans("ServerAddressDescription"); ?>
</td>

</tr>

<tr>
<td valign="top" class="label">
<?php echo $langs->trans("Port"); ?>
</td>
<td valign="top" class="label"><input type="text" name="db_port" value="<?php print (! empty($dolibarr_main_db_port))?$dolibarr_main_db_port:$force_install_port; ?>">
<input type="hidden" name="base" value="">
</td>
<td class="comment">
<?php echo $langs->trans("ServerPortDescription"); ?>
</td>

</tr>

<tr>
<td class="label" valign="top"><b>
<?php echo $langs->trans("DatabaseName"); ?>
</b></td>

<td class="label" valign="top"><input type="text" name="db_name" value="<?php echo (! empty($dolibarr_main_db_name))?$dolibarr_main_db_name:$force_install_database; ?>"></td>
<td class="comment">
<?php echo $langs->trans("DatabaseName"); ?>
</td>
</tr>

<tr>
<td class="label" valign="top">
<?php echo $langs->trans("CreateDatabase"); ?>
</td>

<td class="label" valign="top"><input type="checkbox" name="db_create_database"<?php if ($force_install_createdatabase) print ' checked="on"'; ?>></td>
<td class="comment">
<?php echo $langs->trans("CheckToCreateDatabase"); ?>
</td>
</tr>

<tr class="bg1">
<td class="label" valign="top">
<b><?php echo $langs->trans("Login"); ?></b>
</td>
<td class="label" valign="top"><input type="text" name="db_user" value="<?php print (! empty($dolibarr_main_db_user))?$dolibarr_main_db_user:$force_install_databaselogin; ?>"></td>
<td class="comment">
<?php echo $langs->trans("AdminLogin"); ?>
</td>
</tr>

<tr>
<td class="label" valign="top">
<b><?php echo $langs->trans("Password"); ?></b>
</td>
<td class="label" valign="top"><input type="password" name="db_pass" value="<?php print (! empty($dolibarr_main_db_pass))?$dolibarr_main_db_pass:$force_install_databasepass; ?>"></td>
<td class="comment">
<?php echo $langs->trans("AdminPassword"); ?>
</td>
</tr>

<tr>
<td class="label" valign="top">
<?php echo $langs->trans("CreateUser"); ?>
</td>

<td class="label" valign="top"><input type="checkbox" name="db_create_user"<?php if ($force_install_createuser) print ' checked="on"'; ?>></td>
<td class="comment">
<?php echo $langs->trans("CheckToCreateUser"); ?>
</td>
</tr>


<!-- Super access -->

<tr>
<td colspan="3" class="label" align="center"><br><h3>
<?php echo $langs->trans("DatabaseSuperUserAccess"); ?>
</h3></td></tr>

<tr>
<td class="label" valign="top">
<?php echo $langs->trans("Login"); ?>
</td>
<td class="label" valign="top"><input type="text" name="db_user_root" value="<?php print (! empty($db_user_root))?$db_user_root:$force_install_databaserootlogin; ?>"></td>
<td class="label"><div class="comment">
<?php echo $langs->trans("DatabaseRootLoginDescription"); ?>
</div>
</td>
</tr>

<tr>
<td class="label" valign="top">
<?php echo $langs->trans("Password"); ?>
</td>
<td class="label" valign="top"><input type="password" name="db_pass_root" value="<?php print (! empty($db_pass_root))?$db_pass_root:$force_install_databaserootpass; ?>"></td>
<td class="label"><div class="comment">
<?php echo $langs->trans("KeepEmptyIfNoPassword"); ?>
</div>
</td>
</tr>

</table>

<?php

pFooter($err,$setuplang);

?>
