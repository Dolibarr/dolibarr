<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2012 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file		htdocs/core/lib/security.lib.php
 *  \ingroup    core
 *  \brief		Set of function used for dolibarr security (common function included into filefunc.inc.php)
 *  			Warning, this file must not depends on other library files, except function.lib.php
 *  			because it is used at low code level.
 */


/**
 *	Encode a string with base 64 algorithm + specific change
 *	Code of this function is useless and we should use base64_encode only instead
 *
 *	@param   string		$chain		string to encode
 *	@return  string					encoded string
 */
function dol_encode($chain)
{
    $strlength=dol_strlen($chain);
	for ($i=0; $i < $strlength; $i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))+17);
	}

	$string_coded = base64_encode(implode("",$output_tab));
	return $string_coded;
}

/**
 *	Decode a base 64 encoded + specific string.
 *  This function is called by filefunc.inc.php at each page call.
 *	Code of this function is useless and we should use base64_decode only instead
 *
 *	@param   string		$chain		string to decode
 *	@return  string					decoded string
 */
function dol_decode($chain)
{
	$chain = base64_decode($chain);

	$strlength=dol_strlen($chain);
	for($i=0; $i < $strlength;$i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))-17);
	}

	$string_decoded = implode("",$output_tab);
	return $string_decoded;
}


/**
 * 	Returns a hash of a string
 *
 * 	@param 		string		$chain		String to hash
 * 	@param		int			$type		Type of hash (0:md5, 1:sha1, 2:sha1+md5)
 * 	@return		string					Hash of string
 */
function dol_hash($chain,$type=0)
{
	if ($type == 1) return sha1($chain);
	else if ($type == 2) return sha1(md5($chain));
	else return md5($chain);
}


/**
 *	Check permissions of a user to show a page and an object. Check read permission.
 * 	If GETPOST('action') defined, we also check write and delete permission.
 *
 *	@param	User	$user      	  	User to check
 *	@param  string	$features	    Features to check (in most cases, it's module name. Examples: 'societe', 'contact', 'produit|service', ...)
 *	@param  int		$objectid      	Object ID if we want to check permission on a particular record (optionnal)
 *	@param  string	$dbtablename    'TableName&SharedElement' with Tablename is table where object is stored, SharedElement is key to define where to check entity. Not used if objectid is null (optionnal)
 *	@param  string	$feature2		Feature to check, second level of permission (optionnal)
 *  @param  string	$dbt_keyfield   Field name for socid foreign key if not fk_soc (optionnal)
 *  @param  string	$dbt_select     Field name for select if not rowid (optionnal)
 *  @param	Canvas	$objcanvas		Object canvas
 * 	@return	int						Always 1, die process if not allowed
 */
function restrictedArea($user, $features, $objectid=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid', $objcanvas=null)
{
    global $db, $conf;

    //dol_syslog("functions.lib:restrictedArea $feature, $objectid, $dbtablename,$feature2,$dbt_socfield,$dbt_select");
    //print "user_id=".$user->id.", features=".$features.", feature2=".$feature2.", objectid=".$objectid;
    //print ", dbtablename=".$dbtablename.", dbt_socfield=".$dbt_keyfield.", dbt_select=".$dbt_select;
    //print ", perm: ".$features."->".$feature2."=".$user->rights->$features->$feature2->lire."<br>";

    // If we use canvas, we try to use function that overlod restrictarea if provided with canvas
    if (is_object($objcanvas))
    {
        if (method_exists($objcanvas->control,'restrictedArea')) return $objcanvas->control->restrictedArea($user,$features,$objectid,$dbtablename,$feature2,$dbt_keyfield,$dbt_select);
    }

    if ($dbt_select != 'rowid') $objectid = "'".$objectid."'";

    // More features to check
    $features = explode("&",$features);

    // More parameters
    list($dbtablename, $sharedelement) = explode('&', $dbtablename);

    // Check read permission from module
    // TODO Replace "feature" param into caller by first level of permission
    $readok=1;
    foreach ($features as $feature)
    {
        if ($feature == 'societe')
        {
            if (! $user->rights->societe->lire && ! $user->rights->fournisseur->lire) $readok=0;
        }
        else if ($feature == 'contact')
        {
            if (! $user->rights->societe->contact->lire) $readok=0;
        }
        else if ($feature == 'produit|service')
        {
            if (! $user->rights->produit->lire && ! $user->rights->service->lire) $readok=0;
        }
        else if ($feature == 'prelevement')
        {
            if (! $user->rights->prelevement->bons->lire) $readok=0;
        }
        else if ($feature == 'commande_fournisseur')
        {
            if (! $user->rights->fournisseur->commande->lire) $readok=0;
        }
        else if ($feature == 'cheque')
        {
            if (! $user->rights->banque->cheque) $readok=0;
        }
        else if ($feature == 'projet')
        {
            if (! $user->rights->projet->lire && ! $user->rights->projet->all->lire) $readok=0;
        }
        else if (! empty($feature2))	// This should be used for future changes
        {
            if (empty($user->rights->$feature->$feature2->lire)
            && empty($user->rights->$feature->$feature2->read)) $readok=0;
        }
        else if (! empty($feature) && ($feature!='user' && $feature!='usergroup'))		// This is for old permissions
        {
            if (empty($user->rights->$feature->lire)
            && empty($user->rights->$feature->read)
            && empty($user->rights->$feature->run)) $readok=0;
        }
    }

    if (! $readok) accessforbidden();
    //print "Read access is ok";

    // Check write permission from module
    $createok=1;
    if (GETPOST("action")  == 'create')
    {
        foreach ($features as $feature)
        {
            if ($feature == 'contact')
            {
                if (! $user->rights->societe->contact->creer) $createok=0;
            }
            else if ($feature == 'produit|service')
            {
                if (! $user->rights->produit->creer && ! $user->rights->service->creer) $createok=0;
            }
            else if ($feature == 'prelevement')
            {
                if (! $user->rights->prelevement->bons->creer) $createok=0;
            }
            else if ($feature == 'commande_fournisseur')
            {
                if (! $user->rights->fournisseur->commande->creer) $createok=0;
            }
            else if ($feature == 'banque')
            {
                if (! $user->rights->banque->modifier) $createok=0;
            }
            else if ($feature == 'cheque')
            {
                if (! $user->rights->banque->cheque) $createok=0;
            }
            else if (! empty($feature2))	// This should be used for future changes
            {
                if (empty($user->rights->$feature->$feature2->creer)
                && empty($user->rights->$feature->$feature2->write)) $createok=0;
            }
            else if (! empty($feature))		// This is for old permissions
            {
                //print '<br>feature='.$feature.' creer='.$user->rights->$feature->creer.' write='.$user->rights->$feature->write;
                if (empty($user->rights->$feature->creer)
                && empty($user->rights->$feature->write)) $createok=0;
            }
        }

        if (! $createok) accessforbidden();
        //print "Write access is ok";
    }

    // Check create user permission
    $createuserok=1;
    if (GETPOST("action") == 'confirm_create_user' && GETPOST("confirm") == 'yes')
    {
        if (! $user->rights->user->user->creer) $createuserok=0;

        if (! $createuserok) accessforbidden();
        //print "Create user access is ok";
    }

    // Check delete permission from module
    $deleteok=1;
    if ((GETPOST("action")  == 'confirm_delete' && GETPOST("confirm") == 'yes') || GETPOST("action")  == 'delete')
    {
        foreach ($features as $feature)
        {
            if ($feature == 'contact')
            {
                if (! $user->rights->societe->contact->supprimer) $deleteok=0;
            }
            else if ($feature == 'produit|service')
            {
                if (! $user->rights->produit->supprimer && ! $user->rights->service->supprimer) $deleteok=0;
            }
            else if ($feature == 'commande_fournisseur')
            {
                if (! $user->rights->fournisseur->commande->supprimer) $deleteok=0;
            }
            else if ($feature == 'banque')
            {
                if (! $user->rights->banque->modifier) $deleteok=0;
            }
            else if ($feature == 'cheque')
            {
                if (! $user->rights->banque->cheque) $deleteok=0;
            }
            else if ($feature == 'ecm')
            {
                if (! $user->rights->ecm->upload) $deleteok=0;
            }
            else if ($feature == 'ftp')
            {
                if (! $user->rights->ftp->write) $deleteok=0;
            }
            else if (! empty($feature2))	// This should be used for future changes
            {
                if (empty($user->rights->$feature->$feature2->supprimer)
                && empty($user->rights->$feature->$feature2->delete)) $deleteok=0;
            }
            else if (! empty($feature))		// This is for old permissions
            {
                //print '<br>feature='.$feature.' creer='.$user->rights->$feature->supprimer.' write='.$user->rights->$feature->delete;
                if (empty($user->rights->$feature->supprimer)
                && empty($user->rights->$feature->delete)) $deleteok=0;
            }
        }

        //print "Delete access is ko";
        if (! $deleteok) accessforbidden();
        //print "Delete access is ok";
    }

    // If we have a particular object to check permissions on, we check this object
    // is linked to a company allowed to $user.
    if (! empty($objectid) && $objectid > 0)
    {
        foreach ($features as $feature)
        {
            $sql='';

            $check = array('banque','user','usergroup','produit','service','produit|service','categorie'); // Test on entity only (Objects with no link to company)
            $checksoc = array('societe');	 // Test for societe object
            $checkother = array('contact');	 // Test on entity and link to societe. Allowed if link is empty (Ex: contacts...).
            $checkproject = array('projet'); // Test for project object
            $nocheck = array('barcode','stock','fournisseur');	// No test
            $checkdefault = 'all other not already defined'; // Test on entity and link to third party. Not allowed if link is empty (Ex: invoice, orders...).

            // If dbtable not defined, we use same name for table than module name
            if (empty($dbtablename)) $dbtablename = $feature;

            // Check permission for object with entity
            if (in_array($feature,$check))
            {
                $sql = "SELECT dbt.".$dbt_select;
                $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                $sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
                if (($feature == 'user' || $feature == 'usergroup') && ! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
                {
                    $sql.= " AND dbt.entity IS NOT NULL";
                }
                else
                {
                    $sql.= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
                }
            }
            else if (in_array($feature,$checksoc))
            {
                // If external user: Check permission for external users
                if ($user->societe_id > 0)
                {
                    if ($user->societe_id <> $objectid) accessforbidden();
                }
                // If internal user: Check permission for internal users that are restricted on their objects
                else if (! $user->rights->societe->client->voir)
                {
                    $sql = "SELECT sc.fk_soc";
                    $sql.= " FROM (".MAIN_DB_PREFIX."societe_commerciaux as sc";
                    $sql.= ", ".MAIN_DB_PREFIX."societe as s)";
                    $sql.= " WHERE sc.fk_soc = ".$objectid;
                    $sql.= " AND sc.fk_user = ".$user->id;
                    $sql.= " AND sc.fk_soc = s.rowid";
                    $sql.= " AND s.entity IN (".getEntity($sharedelement, 1).")";
                }
                // If multicompany and internal users with all permissions, check user is in correct entity
                else if (! empty($conf->multicompany->enabled))
                {
                    $sql = "SELECT s.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
                    $sql.= " WHERE s.rowid = ".$objectid;
                    $sql.= " AND s.entity IN (".getEntity($sharedelement, 1).")";
                }
            }
            else if (in_array($feature,$checkother))
            {
                // If external user: Check permission for external users
                if ($user->societe_id > 0)
                {
                    $sql = "SELECT dbt.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND dbt.fk_soc = ".$user->societe_id;
                }
                // If internal user: Check permission for internal users that are restricted on their objects
                else if (! $user->rights->societe->client->voir)
                {
                    $sql = "SELECT dbt.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON dbt.fk_soc = sc.fk_soc AND sc.fk_user = '".$user->id."'";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND (dbt.fk_soc IS NULL OR sc.fk_soc IS NOT NULL)";	// Contact not linked to a company or to a company of user
                    $sql.= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
                }
                // If multicompany and internal users with all permissions, check user is in correct entity
                else if (! empty($conf->multicompany->enabled))
                {
                    $sql = "SELECT dbt.rowid";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
                }
            }
            else if (in_array($feature,$checkproject))
            {
                if (! $user->rights->projet->all->lire)
                {
                    include_once(DOL_DOCUMENT_ROOT."/projet/class/project.class.php");
                    $projectstatic=new Project($db);
                    $tmps=$projectstatic->getProjectsAuthorizedForUser($user,0,1,0);
                    $tmparray=explode(',',$tmps);
                    if (! in_array($objectid,$tmparray)) accessforbidden();
                }
                else
                {
                	$sql = "SELECT dbt.".$dbt_select;
                	$sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                	$sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
                	$sql.= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
                }
            }
            else if (! in_array($feature,$nocheck))	// By default we check with link to third party
            {
                // If external user: Check permission for external users
                if ($user->societe_id > 0)
                {
                    $sql = "SELECT dbt.".$dbt_keyfield;
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.rowid = ".$objectid;
                    $sql.= " AND dbt.".$dbt_keyfield." = ".$user->societe_id;
                }
                // If internal user: Check permission for internal users that are restricted on their objects
                else if (! $user->rights->societe->client->voir)
                {
                    $sql = "SELECT sc.fk_soc";
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= ", ".MAIN_DB_PREFIX."societe as s";
                    $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
                    $sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
                    $sql.= " AND sc.fk_soc = dbt.".$dbt_keyfield;
                    $sql.= " AND dbt.".$dbt_keyfield." = s.rowid";
                    $sql.= " AND s.entity IN (".getEntity($sharedelement, 1).")";
                    $sql.= " AND sc.fk_user = ".$user->id;
                }
                // If multicompany and internal users with all permissions, check user is in correct entity
                else if (! empty($conf->multicompany->enabled))
                {
                    $sql = "SELECT dbt.".$dbt_select;
                    $sql.= " FROM ".MAIN_DB_PREFIX.$dbtablename." as dbt";
                    $sql.= " WHERE dbt.".$dbt_select." = ".$objectid;
                    $sql.= " AND dbt.entity IN (".getEntity($sharedelement, 1).")";
                }
            }

            //print $sql."<br>";
            if ($sql)
            {
                $resql=$db->query($sql);
                if ($resql)
                {
                    if ($db->num_rows($resql) == 0)	accessforbidden();
                }
                else
                {
                    dol_syslog("security.lib:restrictedArea sql=".$sql, LOG_ERR);
                    accessforbidden();
                }
            }
        }
    }

    return 1;
}


/**
 *	Show a message to say access is forbidden and stop program
 *	Calling this function terminate execution of PHP.
 *
 *	@param	string	$message			    Force error message
 *	@param	int		$printheader		    Show header before
 *  @param  int		$printfooter         Show footer after
 *  @param  int		$showonlymessage     Show only message parameter. Otherwise add more information.
 *  @return	void
 */
function accessforbidden($message='',$printheader=1,$printfooter=1,$showonlymessage=0)
{
    global $conf, $db, $user, $langs;
    if (! is_object($langs))
    {
        include_once(DOL_DOCUMENT_ROOT.'/core/class/translate.class.php');
        $langs=new Translate('',$conf);
    }

    $langs->load("errors");

    if ($printheader)
    {
        if (function_exists("llxHeader")) llxHeader('');
        else if (function_exists("llxHeaderVierge")) llxHeaderVierge('');
    }
    print '<div class="error">';
    if (! $message) print $langs->trans("ErrorForbidden");
    else print $message;
    print '</div>';
    print '<br>';
    if (empty($showonlymessage))
    {
        if ($user->login)
        {
            print $langs->trans("CurrentLogin").': <font class="error">'.$user->login.'</font><br>';
            print $langs->trans("ErrorForbidden2",$langs->trans("Home"),$langs->trans("Users"));
        }
        else
        {
            print $langs->trans("ErrorForbidden3");
        }
    }
    if ($printfooter && function_exists("llxFooter")) llxFooter();
    exit(0);
}

?>