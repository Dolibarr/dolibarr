<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/install/repair.php
 *      \brief      Run repair script
 */

include_once 'inc.php';
if (file_exists($conffile)) include_once $conffile;
require_once $dolibarr_main_document_root.'/core/lib/admin.lib.php';
require_once $dolibarr_main_document_root.'/core/class/extrafields.class.php';
require_once 'lib/repair.lib.php';

$grant_query='';
$step = 2;
$ok = 0;


// Cette page peut etre longue. On augmente le delai autorise.
// Ne fonctionne que si on est pas en safe_mode.
$err=error_reporting();
error_reporting(0);
@set_time_limit(120);
error_reporting($err);

$setuplang=GETPOST("selectlang",'',3)?GETPOST("selectlang",'',3):'auto';
$langs->setDefaultLang($setuplang);
$versionfrom=GETPOST("versionfrom",'',3)?GETPOST("versionfrom",'',3):(empty($argv[1])?'':$argv[1]);
$versionto=GETPOST("versionto",'',3)?GETPOST("versionto",'',3):(empty($argv[2])?'':$argv[2]);

$langs->load("admin");
$langs->load("install");
$langs->load("other");

if ($dolibarr_main_db_type == "mysqli") $choix=1;
if ($dolibarr_main_db_type == "pgsql") $choix=2;
if ($dolibarr_main_db_type == "mssql") $choix=3;


dolibarr_install_syslog("--- repair: entering upgrade.php page");
if (! is_object($conf)) dolibarr_install_syslog("repair: conf file not initialized", LOG_ERR);


/*
 * View
*/

pHeader('',"upgrade2",GETPOST('action'));

$actiondone=0;

// Action to launch the repair script
$actiondone=1;

print '<h3>'.$langs->trans("Repair").'</h3>';

print 'Option restore_thirdparties_logos is '.(GETPOST('restore_thirdparties_logos')?GETPOST('restore_thirdparties_logos'):'0').'<br>'."\n";
print 'Option clean_linked_elements is '.(GETPOST('clean_linked_elements')?GETPOST('clean_linked_elements'):'0').'<br>'."\n";
print 'Option clean_orphelin_dir (0 or \'test\' or \'confirmed\') is '.(GETPOST('clean_orphelin_dir')?GETPOST('clean_orphelin_dir'):'0').'<br>'."\n";
print 'Option clean_product_stock_batch (0 or \'test\' or \'confirmed\') is '.(GETPOST('clean_product_stock_batch')?GETPOST('clean_product_stock_batch'):'0').'<br>'."\n";
print 'Option set_empty_time_spent_amount (0 or \'test\' or \'confirmed\') is '.(GETPOST('set_empty_time_spent_amount')?GETPOST('set_empty_time_spent_amount'):'0').'<br>'."\n";
print '<br>';

print '<table cellspacing="0" cellpadding="1" border="0" width="100%">';
$error=0;

// If password is encoded, we decode it
if (preg_match('/crypted:/i',$dolibarr_main_db_pass) || ! empty($dolibarr_main_db_encrypted_pass))
{
    require_once $dolibarr_main_document_root.'/core/lib/security.lib.php';
    if (preg_match('/crypted:/i',$dolibarr_main_db_pass))
    {
        $dolibarr_main_db_pass = preg_replace('/crypted:/i', '', $dolibarr_main_db_pass);
        $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_pass);
        $dolibarr_main_db_encrypted_pass = $dolibarr_main_db_pass;	// We need to set this as it is used to know the password was initially crypted
    }
    else $dolibarr_main_db_pass = dol_decode($dolibarr_main_db_encrypted_pass);
}

// $conf is already instancied inside inc.php
$conf->db->type = $dolibarr_main_db_type;
$conf->db->host = $dolibarr_main_db_host;
$conf->db->port = $dolibarr_main_db_port;
$conf->db->name = $dolibarr_main_db_name;
$conf->db->user = $dolibarr_main_db_user;
$conf->db->pass = $dolibarr_main_db_pass;

// For encryption
$conf->db->dolibarr_main_db_encryption	= isset($dolibarr_main_db_encryption)?$dolibarr_main_db_encryption:'';
$conf->db->dolibarr_main_db_cryptkey	= isset($dolibarr_main_db_cryptkey)?$dolibarr_main_db_cryptkey:'';

$db=getDoliDBInstance($conf->db->type,$conf->db->host,$conf->db->user,$conf->db->pass,$conf->db->name,$conf->db->port);

if ($db->connected)
{
    print '<tr><td class="nowrap">';
    print $langs->trans("ServerConnection")." : $dolibarr_main_db_host</td><td align=\"right\">".$langs->trans("OK")."</td></tr>";
    dolibarr_install_syslog("repair: " . $langs->transnoentities("ServerConnection") . ": " . $dolibarr_main_db_host . $langs->transnoentities("OK"));
    $ok = 1;
}
else
{
    print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->transnoentities("Error")."</td></tr>";
    dolibarr_install_syslog("repair: " . $langs->transnoentities("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name));
    $ok = 0;
}

if ($ok)
{
    if($db->database_selected)
    {
        print '<tr><td class="nowrap">';
        print $langs->trans("DatabaseConnection")." : ".$dolibarr_main_db_name."</td><td align=\"right\">".$langs->trans("OK")."</td></tr>";
        dolibarr_install_syslog("repair: database connection successful: " . $dolibarr_main_db_name);
        $ok=1;
    }
    else
    {
        print "<tr><td>".$langs->trans("ErrorFailedToConnectToDatabase",$dolibarr_main_db_name)."</td><td align=\"right\">".$langs->trans("Error")."</td></tr>";
        dolibarr_install_syslog("repair: " . $langs->transnoentities("ErrorFailedToConnectToDatabase", $dolibarr_main_db_name));
        $ok=0;
    }
}

// Show database version
if ($ok)
{
    $version=$db->getVersion();
    $versionarray=$db->getVersionArray();
    print '<tr><td>'.$langs->trans("ServerVersion").'</td>';
    print '<td align="right">'.$version.'</td></tr>';
    dolibarr_install_syslog("repair: " . $langs->transnoentities("ServerVersion") . ": " . $version);
    //print '<td align="right">'.join('.',$versionarray).'</td></tr>';
}

// Show wait message
print '<tr><td colspan="2">'.$langs->trans("PleaseBePatient").'<br><br></td></tr>';
flush();


/* Start action here */


// run_sql: Run repair SQL file
if ($ok)
{
    $dir = "mysql/migration/";

    $filelist=array();
    $i = 0;
    $ok = 0;

    // Recupere list fichier
    $filesindir=array();
    $handle=opendir($dir);
    if (is_resource($handle))
    {
        while (($file = readdir($handle))!==false)
        {
            if (preg_match('/\.sql$/i',$file)) $filesindir[]=$file;
        }
    }
    sort($filesindir);

    foreach($filesindir as $file)
    {
        if (preg_match('/repair/i',$file))
        {
            $filelist[]=$file;
        }
    }

    // Loop on each file
    foreach($filelist as $file)
    {
        print '<tr><td class="nowrap">*** ';
        print $langs->trans("Script").'</td><td align="right">'.$file.'</td></tr>';

        $name = substr($file, 0, dol_strlen($file) - 4);

        // Run sql script
        $ok=run_sql($dir.$file, 0, '', 1);
    }
}


// sync_extrafields: Search list of fields declared and list of fields created into databases, then create fields missing

if ($ok)
{
	$extrafields=new ExtraFields($db);
	$listofmodulesextra=array('societe'=>'societe','adherent'=>'adherent','product'=>'product',
				'socpeople'=>'socpeople', 'commande'=>'commande', 'facture'=>'facture',
				'commande_fournisseur'=>'commande_fournisseur', 'actioncomm'=>'actioncomm',
				'adherent_type'=>'adherent_type','user'=>'user','projet'=>'projet', 'projet_task'=>'projet_task');
	print '<tr><td colspan="2"><br>*** Check fields into extra table structure match table of definition. If not add column into table</td></tr>';
	foreach($listofmodulesextra as $tablename => $elementtype)
	{
	    // Get list of fields
	    $tableextra=MAIN_DB_PREFIX.$tablename.'_extrafields';

	    // Define $arrayoffieldsdesc
	    $arrayoffieldsdesc=$extrafields->fetch_name_optionals_label($elementtype);

	    // Define $arrayoffieldsfound
	    $arrayoffieldsfound=array();
	    $resql=$db->DDLDescTable($tableextra);
	    if ($resql)
	    {
	        print '<tr><td>Check availability of extra field for '.$tableextra."<br>\n";
	        $i=0;
	        while($obj=$db->fetch_object($resql))
	        {
	            $fieldname=$fieldtype='';
	            if (preg_match('/mysql/',$db->type))
	            {
	                $fieldname=$obj->Field;
	                $fieldtype=$obj->Type;
	            }
	            else
	            {
	                $fieldname = isset($obj->Key)?$obj->Key:$obj->attname;
	                $fieldtype = isset($obj->Type)?$obj->Type:'varchar';
	            }

	            if (empty($fieldname)) continue;
	            if (in_array($fieldname,array('rowid','tms','fk_object','import_key'))) continue;
	            $arrayoffieldsfound[$fieldname]=array('type'=>$fieldtype);
	        }

	        // If it does not match, we create fields
	        foreach($arrayoffieldsdesc as $code => $label)
	        {
	            if (! in_array($code,array_keys($arrayoffieldsfound)))
	            {
	                print 'Found field '.$code.' declared into '.MAIN_DB_PREFIX.'extrafields table but not found into desc of table '.$tableextra." -> ";
	                $type=$extrafields->attribute_type[$code]; $length=$extrafields->attribute_size[$code]; $attribute=''; $default=''; $extra=''; $null='null';
	                
           			if ($type=='boolean') {
        				$typedb='int';
        				$lengthdb='1';
        			} elseif($type=='price') {
        				$typedb='double';
        				$lengthdb='24,8';
        			} elseif($type=='phone') {
        				$typedb='varchar';
        				$lengthdb='20';
        			}elseif($type=='mail') {
        				$typedb='varchar';
        				$lengthdb='128';
        			} elseif (($type=='select') || ($type=='sellist') || ($type=='radio') ||($type=='checkbox') ||($type=='chkbxlst')){
        				$typedb='text';
        				$lengthdb='';
        			} elseif ($type=='link') {
        				$typedb='int';
        				$lengthdb='11';
        			} else {
        				$typedb=$type;
        				$lengthdb=$length;
        			}
	                
	                $field_desc=array(
	                	'type'=>$typedb,
	                	'value'=>$lengthdb,
	                	'attribute'=>$attribute,
	                	'default'=>$default,
	                	'extra'=>$extra,
	                	'null'=>$null
	                );
	                //var_dump($field_desc);exit;

	                $result=$db->DDLAddField($tableextra,$code,$field_desc,"");
	                if ($result < 0)
	                {
	                    print "KO ".$db->lasterror."<br>\n";
	                }
	                else
	                {
	                    print "OK<br>\n";
	                }
	            }
	        }

	        print "</td><td>&nbsp;</td></tr>\n";
	    }
	}
}



// clean_data_ecm_dir: Clean data into ecm_directories table
if ($ok)
{
	clean_data_ecm_directories();
}



/* From here, actions need a parameter */



// clean_linked_elements: Check and clean linked elements
if ($ok && GETPOST('restore_thirdparties_logos'))
{
	//$exts=array('gif','png','jpg');

	$ext='';
	
	print '<tr><td colspan="2"><br>*** Restore thirdparties logo<br>';
	//foreach($exts as $ext)
	//{
		$sql="SELECT s.rowid, s.nom as name, s.logo FROM ".MAIN_DB_PREFIX."societe as s ORDER BY s.nom";
		$resql=$db->query($sql);
		if ($resql)
		{
			$num=$db->num_rows($resql);
			$i=0;

			while($i < $num)
			{
				$obj=$db->fetch_object($resql);

				/*
				$name=preg_replace('/é/','',$obj->name);
				$name=preg_replace('/ /','_',$name);
				$name=preg_replace('/\'/','',$name);
				*/

				$tmp=explode('.',$obj->logo);
				$name=$tmp[0];
				if (isset($tmp[1])) $ext='.'.$tmp[1];

				if (! empty($name))
				{
					$filetotest=$dolibarr_main_data_root.'/societe/logos/'.$name.$ext;
					$filetotestsmall=$dolibarr_main_data_root.'/societe/logos/thumbs/'.$name.$ext;
					$exists=dol_is_file($filetotest);
					print 'Check thirdparty '.$obj->rowid.' name='.$obj->name.' logo='.$obj->logo.' file '.$filetotest." exists=".$exists."<br>\n";
					if ($exists)
					{
						$filetarget=$dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos/'.$name.$ext;
						$filetargetsmall=$dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos/thumbs/'.$name.'_small'.$ext;
						$existt=dol_is_file($filetarget);
						if (! $existt)
						{
							dol_mkdir($dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos');

							print "  &nbsp; &nbsp; &nbsp; -> Copy file ".$filetotest." -> ".$filetarget."<br>\n";
							dol_copy($filetotest, $filetarget, '', 0);
						}

						$existtt=dol_is_file($filetargetsmall);
						if (! $existtt)
						{
							dol_mkdir($dolibarr_main_data_root.'/societe/'.$obj->rowid.'/logos/thumbs');

							print "  &nbsp; &nbsp; &nbsp; -> Copy file ".$filetotestsmall." -> ".$filetargetsmall."<br>\n";
							dol_copy($filetotestsmall, $filetargetsmall, '', 0);
						}
					}
				}

				$i++;
			}
		}
		else
		{
			$ok=0;
			dol_print_error($db);
		}

	print '</td></tr>';
	//}
}


// clean_linked_elements: Check and clean linked elements
if ($ok && GETPOST('clean_linked_elements'))
{
    print '<tr><td colspan="2"><br>*** Check table of linked elements and delete orphelins links</td></tr>';
	// propal => order
	print '<tr><td colspan="2">'.checkLinkedElements('propal', 'commande')."</td></tr>\n";

	// propal => invoice
	print '<tr><td colspan="2">'.checkLinkedElements('propal', 'facture')."</td></tr>\n";

	// order => invoice
	print '<tr><td colspan="2">'.checkLinkedElements('commande', 'facture')."</td></tr>\n";

	// order => shipping
	print '<tr><td colspan="2">'.checkLinkedElements('commande', 'shipping')."</td></tr>\n";

	// shipping => delivery
	print '<tr><td colspan="2">'.checkLinkedElements('shipping', 'delivery')."</td></tr>\n";

	// order_supplier => invoice_supplier
	print '<tr><td colspan="2">'.checkLinkedElements('order_supplier', 'invoice_supplier')."</td></tr>\n";
}


// clean_orphelin_dir: Run purge of directory
if ($ok && GETPOST('clean_orphelin_dir'))
{
    $conf->setValues($db);

    $listmodulepart=array('company','invoice','invoice_supplier','propal','order','order_supplier','contract','tax');
    foreach ($listmodulepart as $modulepart)
    {
        $filearray=array();
        $upload_dir = isset($conf->$modulepart->dir_output)?$conf->$modulepart->dir_output:'';
        if ($modulepart == 'company') $upload_dir = $conf->societe->dir_output; // TODO change for multicompany sharing
        if ($modulepart == 'invoice') $upload_dir = $conf->facture->dir_output;
        if ($modulepart == 'invoice_supplier') $upload_dir = $conf->fournisseur->facture->dir_output;
        if ($modulepart == 'order') $upload_dir = $conf->commande->dir_output;
        if ($modulepart == 'order_supplier') $upload_dir = $conf->fournisseur->commande->dir_output;
        if ($modulepart == 'contract') $upload_dir = $conf->contrat->dir_output;

        if (empty($upload_dir)) continue;

        print '<tr><td colspan="2"><br>*** Clean orphelins files into files '.$upload_dir.'</td></tr>';

        $filearray=dol_dir_list($upload_dir,"files",1,'',array('^SPECIMEN\.pdf$','^\.','(\.meta|_preview\.png)$','^temp$','^payments$','^CVS$','^thumbs$'),'',SORT_DESC,1,true);

        // To show ref or specific information according to view to show (defined by $module)
        if ($modulepart == 'company')
        {
            include_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
            $object_instance=new Societe($db);
        }
        if ($modulepart == 'invoice')
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
            $object_instance=new Facture($db);
        }
        else if ($modulepart == 'invoice_supplier')
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
            $object_instance=new FactureFournisseur($db);
        }
        else if ($modulepart == 'propal')
        {
            include_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
            $object_instance=new Propal($db);
        }
        else if ($modulepart == 'order')
        {
            include_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
            $object_instance=new Commande($db);
        }
        else if ($modulepart == 'order_supplier')
        {
            include_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
            $object_instance=new CommandeFournisseur($db);
        }
        else if ($modulepart == 'contract')
        {
            include_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
            $object_instance=new Contrat($db);
        }
        else if ($modulepart == 'tax')
        {
            include_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
            $object_instance=new ChargeSociales($db);
        }

        $var=true;
        foreach($filearray as $key => $file)
        {
            if (!is_dir($file['name'])
            && $file['name'] != '.'
            && $file['name'] != '..'
            && $file['name'] != 'CVS'
            )
            {
                // Define relative path used to store the file
                $relativefile=preg_replace('/'.preg_quote($upload_dir.'/','/').'/','',$file['fullname']);

                //var_dump($file);
                $id=0; $ref=''; $object_instance->id=0; $object_instance->ref=''; $label='';

                // To show ref or specific information according to view to show (defined by $module)
                if ($modulepart == 'invoice')          {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'invoice_supplier') {
                    preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=empty($reg[1])?'':$reg[1];
                }
                if ($modulepart == 'propal')           {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'order')            {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'order_supplier')   {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'contract')         {
                    preg_match('/(.*)\/[^\/]+$/',$relativefile,$reg);  $ref=$reg[1];
                }
                if ($modulepart == 'tax')              {
                    preg_match('/(\d+)\/[^\/]+$/',$relativefile,$reg); $id=$reg[1];
                }

                if ($id || $ref)
                {
                    //print 'Fetch '.$id.' or '.$ref.'<br>';
                    $result=$object_instance->fetch($id,$ref);
                    //print $result.'<br>';
                    if ($result == 0)    // Not found but no error
                    {
                        // Clean of orphelins directories are done into repair.php
                        print '<tr><td colspan="2">';
                        print 'Delete orphelins file '.$file['fullname'].'<br>';
                        if (GETPOST('clean_orphelin_dir') == 'confirmed')
                        {
                            dol_delete_file($file['fullname'],1,1,1);
                            dol_delete_dir(dirname($file['fullname']),1);
                        }
                        print "</td></tr>";
                    }
                    else if ($result < 0) print 'Error in '.get_class($object_instance).'.fetch of id'.$id.' ref='.$ref.', result='.$result.'<br>';
                }
            }
        }
    }
}

// clean_linked_elements: Check and clean linked elements
if ($ok && GETPOST('clean_product_stock_batch'))
{
    $methodtofix=GETPOST('methodtofix')?GETPOST('methodtofix'):'updatestock';
    
    print '<tr><td colspan="2"><br>*** Clean table product_batch, methodtofix='.$methodtofix.' (possible values: updatestock or updatebatch)</td></tr>';
    
    $sql ="SELECT p.rowid, p.ref, p.tobatch, ps.rowid as psrowid, ps.fk_entrepot, ps.reel, SUM(pb.qty) as reelbatch";
    $sql.=" FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_stock as ps LEFT JOIN ".MAIN_DB_PREFIX."product_batch as pb ON ps.rowid = pb.fk_product_stock";
    $sql.=" WHERE p.rowid = ps.fk_product";
    $sql.=" AND p.tobatch = 1";
    $sql.=" GROUP BY p.rowid, p.ref, p.tobatch, ps.rowid, ps.fk_entrepot, ps.reel";
    $sql.=" HAVING reel != SUM(pb.qty) or SUM(pb.qty) IS NULL";
    print $sql;
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        
        if ($num)
        {
            $i = 0;
            while ($i < $num)
            {
                $obj=$db->fetch_object($resql);
                print '<tr><td>Product '.$obj->rowid.'-'.$obj->ref.' in warehose '.$obj->fk_entrepot.' -> '.$obj->psrowid.': '.$obj->reel.' (product_stock.reel) != '.($obj->reelbatch?$obj->reelbatch:'0').' (sum product_batch)';
                
                // Fix
                if ($obj->reel != $obj->reelbatch)
                {
                    if ($methodtofix == 'updatebatch')
                    {
                        // Method 1
                        print ' -> Insert qty '.($obj->reel - $obj->reelbatch).' with lot 000000 linked to fk_product_stock='.$obj->psrowid;
                        if (GETPOST('clean_product_stock_batch') == 'confirmed')
                        {
                            $sql2 ="INSERT INTO ".MAIN_DB_PREFIX."product_batch(fk_product_stock, batch, qty)";
                            $sql2.="VALUES(".$obj->psrowid.", '000000', ".($obj->reel - $obj->reelbatch).")";
                            $resql2=$db->query($sql2);
                            if (! $resql2)
                            {
                                // TODO If it fails, we must make update
                                //$sql2 ="UPDATE ".MAIN_DB_PREFIX."product_batch";
                                //$sql2.=" SET ".$obj->psrowid.", '000000', ".($obj->reel - $obj->reelbatch).")";
                                //$sql2.=" WHERE fk_product_stock = ".$obj->psrowid"
                            }
                        }
                    }
                    if ($methodtofix == 'updatestock')
                    {
                        // Method 2
                        print ' -> Update qty of product_stock with qty = '.($obj->reelbatch?$obj->reelbatch:'0').' for ps.rowid = '.$obj->psrowid;
                        if (GETPOST('clean_product_stock_batch') == 'confirmed')
                        {
                            $error=0;
                            
                            $db->begin();
                            
                            $sql2 ="UPDATE ".MAIN_DB_PREFIX."product_stock";
                            $sql2.=" SET reel = ".($obj->reelbatch?$obj->reelbatch:'0')." WHERE rowid = ".$obj->psrowid;
                            $resql2=$db->query($sql2);
                            if ($resql2)
                            {
                                // We update product_stock, so we must field stock into product too.
                                $sql3='UPDATE llx_product p SET p.stock= (SELECT SUM(ps.reel) FROM llx_product_stock ps WHERE ps.fk_product = p.rowid)';
                                $resql3=$db->query($sql3);
                                if (! $resql3) 
                                {
                                    $error++;
                                    dol_print_error($db);
                                }
                            }
                            else 
                            {
                                $error++;
                                dol_print_error($db);
                            }
                            
                            if (!$error) $db->commit();
                            else $db->rollback();
                        }
                    }
                }
                
                print'</td></tr>';
            
                $i++;
            }
        }
        else
        {
            print '<tr><td colspan="2">Nothing to do</td></tr>';
        }
    }
    else
    {
        dol_print_error($db);
    }
}


// clean_linked_elements: Check and clean linked elements
if ($ok && GETPOST('clean_product_stock_negative_if_batch'))
{
    print '<tr><td colspan="2"><br>Clean table product_batch, methodtofix='.$methodtofix.' (possible values: updatestock or updatebatch)</td></tr>';

    $sql ="SELECT p.rowid, p.ref, p.tobatch, ps.rowid as psrowid, ps.fk_entrepot, ps.reel, SUM(pb.qty) as reelbatch";
    $sql.=" FROM ".MAIN_DB_PREFIX."product as p, ".MAIN_DB_PREFIX."product_stock as ps, ".MAIN_DB_PREFIX."product_batch as pb";
    $sql.=" WHERE p.rowid = ps.fk_product AND ps.rowid = pb.fk_product_stock";
    $sql.=" AND p.tobatch = 1";
    $sql.=" GROUP BY p.rowid, p.ref, p.tobatch, ps.rowid, ps.fk_entrepot, ps.reel";
    $sql.=" HAVING reel != SUM(pb.qty)";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);

        if ($num)
        {
            $i = 0;
            while ($i < $num)
            {
                $obj=$db->fetch_object($resql);
                print '<tr><td>'.$obj->rowid.'-'.$obj->ref.'-'.$obj->fk_entrepot.' -> '.$obj->psrowid.': '.$obj->reel.' != '.$obj->reelbatch;

            }
        }
    }
}

// clean_linked_elements: Check and clean linked elements
if ($ok && GETPOST('set_empty_time_spent_amount'))
{
    print '<tr><td colspan="2"><br>*** Set value of time spent without amount</td></tr>';

    $sql ="SELECT COUNT(ptt.rowid) as nb, u.rowid as user_id, u.login, u.thm as user_thm";
    $sql.=" FROM ".MAIN_DB_PREFIX."projet_task_time as ptt, ".MAIN_DB_PREFIX."user as u";
    $sql.=" WHERE ptt.fk_user = u.rowid";
    $sql.=" AND ptt.thm IS NULL and u.thm > 0";
    $sql.=" GROUP BY u.rowid, u.login, u.thm";
    
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);

        if ($num)
        {
            $i = 0;
            while ($i < $num)
            {
                $obj=$db->fetch_object($resql);
                print '<tr><td>'.$obj->login.'-'.$obj->user_id.' ('.$obj->nb.' lines to fix) -> '.$obj->user_thm;

                $db->begin();

                if (GETPOST('set_empty_time_spent_amount') == 'confirmed')
                {
                    $sql2 ="UPDATE ".MAIN_DB_PREFIX."projet_task_time";
                    $sql2.=" SET thm = ".$obj->user_thm." WHERE thm IS NULL AND fk_user = ".$obj->user_id;
                    $resql2=$db->query($sql2);
                    if (! $resql2)
                    {
                        $error++;
                        dol_print_error($db);
                    }
                }
                
                if (!$error) $db->commit();
                else $db->rollback();

                print'</td></tr>';

                if ($error) break;
                
                $i++;
            }
        }
        else
        {
            print '<tr><td>No time spent with empty line on users with a hourly rate defined</td></tr>';
        }
    }
    else
    {
        dol_print_error($db);
    }


}




print '</table>';



if (empty($actiondone))
{
    print '<div class="error">'.$langs->trans("ErrorWrongParameters").'</div>';
}


print '<div class="center" style="padding-top: 10px"><a href="../index.php?mainmenu=home&leftmenu=home'.(isset($_POST["login"])?'&username='.urlencode($_POST["login"]):'').'">';
print $langs->trans("GoToDolibarr");
print '</a></div>';

dolibarr_install_syslog("--- repair: end");
pFooter(1,$setuplang);

if ($db->connected) $db->close();

// Return code if ran from command line
if (! $ok && isset($argv[1])) exit(1);
