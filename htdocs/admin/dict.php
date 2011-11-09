<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2010 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2011      Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2011      Remy Younes          <ryounes@gmail.com>
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
 *	    \file       htdocs/admin/dict.php
 *		\ingroup    setup
 *		\brief      Page to administer data tables
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formadmin.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");

$langs->load("errors");
$langs->load("admin");
$langs->load("companies");

$action=GETPOST('action')?GETPOST('action'):'view';

if (!$user->admin) accessforbidden();

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"),'switch_off');
$actl[1] = img_picto($langs->trans("Activated"),'switch_on');

$listoffset=GETPOST('listoffset');
$listlimit=GETPOST('listlimit')>0?GETPOST('listlimit'):1000;
$active = 1;

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');
$page = GETPOST("page",'int');
if ($page == -1) { $page = 0 ; }
$offset = $listlimit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical object to manage hooks of thirdparties. Note that conf->hooks_modules contains array array
include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
$hookmanager=new HookManager($db);
$hookmanager->callHooks(array('admin'));

// Thi page is a generic page to edit dictionnaries
// Put here delacaration of dictionnaries properties

// Sort order to show dictionnary (0 is space). All other dictionnaries (added by modules) will be at end of this.
$taborder=array(9,0,4,3,2,0,1,8,19,16,0,5,11,0,6,0,10,12,13,0,14,0,7,17,0,22,20,18,21,0,15);

// Name of SQL tables of dictionnaries
$tabname[1] = MAIN_DB_PREFIX."c_forme_juridique";
$tabname[2] = MAIN_DB_PREFIX."c_departements";
$tabname[3] = MAIN_DB_PREFIX."c_regions";
$tabname[4] = MAIN_DB_PREFIX."c_pays";
$tabname[5] = MAIN_DB_PREFIX."c_civilite";
$tabname[6] = MAIN_DB_PREFIX."c_actioncomm";
$tabname[7] = MAIN_DB_PREFIX."c_chargesociales";
$tabname[8] = MAIN_DB_PREFIX."c_typent";
$tabname[9] = MAIN_DB_PREFIX."c_currencies";
$tabname[10]= MAIN_DB_PREFIX."c_tva";
$tabname[11]= MAIN_DB_PREFIX."c_type_contact";
$tabname[12]= MAIN_DB_PREFIX."c_payment_term";
$tabname[13]= MAIN_DB_PREFIX."c_paiement";
$tabname[14]= MAIN_DB_PREFIX."c_ecotaxe";
$tabname[15]= MAIN_DB_PREFIX."c_paper_format";
$tabname[16]= MAIN_DB_PREFIX."c_prospectlevel";
$tabname[17]= MAIN_DB_PREFIX."c_type_fees";
$tabname[18]= MAIN_DB_PREFIX."c_shipment_mode";
$tabname[19]= MAIN_DB_PREFIX."c_effectif";
$tabname[20]= MAIN_DB_PREFIX."c_input_method";
$tabname[21]= MAIN_DB_PREFIX."c_availability";
$tabname[22]= MAIN_DB_PREFIX."c_input_reason";

// Dictionary labels
$tablib[1] = "DictionnaryCompanyJuridicalType";
$tablib[2] = "DictionnaryCanton";
$tablib[3] = "DictionnaryRegion";
$tablib[4] = "DictionnaryCountry";
$tablib[5] = "DictionnaryCivility";
$tablib[6] = "DictionnaryActions";
$tablib[7] = "DictionnarySocialContributions";
$tablib[8] = "DictionnaryCompanyType";
$tablib[9] = "DictionnaryCurrency";
$tablib[10]= "DictionnaryVAT";
$tablib[11]= "DictionnaryTypeContact";
$tablib[12]= "DictionnaryPaymentConditions";
$tablib[13]= "DictionnaryPaymentModes";
$tablib[14]= "DictionnaryEcotaxe";
$tablib[15]= "DictionnaryPaperFormat";
$tablib[16]= "DictionnaryProspectLevel";
$tablib[17]= "DictionnaryFees";
$tablib[18]= "DictionnarySendingMethods";
$tablib[19]= "DictionnaryStaff";
$tablib[20]= "DictionnaryOrderMethods";
$tablib[21]= "DictionnaryAvailability";
$tablib[22]= "DictionnarySource";

// Requete pour extraction des donnees des dictionnaires
$tabsql[1] = "SELECT f.rowid as rowid, f.code, f.libelle, p.code as pays_code, p.libelle as pays, f.active FROM ".MAIN_DB_PREFIX."c_forme_juridique as f, ".MAIN_DB_PREFIX."c_pays as p WHERE f.fk_pays=p.rowid";
$tabsql[2] = "SELECT d.rowid as rowid, d.code_departement as code, d.nom as libelle, d.fk_region as region_id, r.nom as region, p.code as pays_code, p.libelle as pays, d.active FROM ".MAIN_DB_PREFIX."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid and r.active=1 and p.active=1";
$tabsql[3] = "SELECT r.rowid as rowid, code_region as code, nom as libelle, r.fk_pays as pays_id, p.code as pays_code, p.libelle as pays, r.active FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p WHERE r.fk_pays=p.rowid and p.active=1";
$tabsql[4] = "SELECT rowid   as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_pays";
$tabsql[5] = "SELECT c.rowid as rowid, c.code as code, c.civilite AS libelle, c.active FROM ".MAIN_DB_PREFIX."c_civilite AS c";
$tabsql[6] = "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.type, a.active, a.module, a.position FROM ".MAIN_DB_PREFIX."c_actioncomm AS a";
$tabsql[7] = "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.deductible, p.code as pays_code, p.libelle as pays, a.fk_pays as pays_id, a.active FROM ".MAIN_DB_PREFIX."c_chargesociales AS a, ".MAIN_DB_PREFIX."c_pays as p WHERE a.fk_pays=p.rowid and p.active=1";
$tabsql[8] = "SELECT id      as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_typent";
//$tabsql[9] = "SELECT code, code_iso, label as libelle, symbole, active FROM ".MAIN_DB_PREFIX."c_currencies";
$tabsql[9] = "SELECT code, code_iso, label as libelle, active FROM ".MAIN_DB_PREFIX."c_currencies";
$tabsql[10]= "SELECT t.rowid, t.taux, t.localtax1, t.localtax2, p.libelle as pays, p.code as pays_code, t.fk_pays as pays_id, t.recuperableonly, t.note, t.active, t.accountancy_code FROM ".MAIN_DB_PREFIX."c_tva as t, ".MAIN_DB_PREFIX."c_pays as p WHERE t.fk_pays=p.rowid";
$tabsql[11]= "SELECT t.rowid as rowid, element, source, code, libelle, active FROM ".MAIN_DB_PREFIX."c_type_contact AS t";
$tabsql[12]= "SELECT c.rowid as rowid, code, sortorder, c.libelle, c.libelle_facture, nbjour, fdm, decalage, active FROM ".MAIN_DB_PREFIX.'c_payment_term AS c';
$tabsql[13]= "SELECT id      as rowid, code, c.libelle, type, active FROM ".MAIN_DB_PREFIX."c_paiement AS c";
$tabsql[14]= "SELECT e.rowid as rowid, e.code as code, e.libelle, e.price, e.organization, e.fk_pays as pays_id, p.code as pays_code, p.libelle as pays, e.active FROM ".MAIN_DB_PREFIX."c_ecotaxe AS e, ".MAIN_DB_PREFIX."c_pays as p WHERE e.fk_pays=p.rowid and p.active=1";
$tabsql[15]= "SELECT rowid   as rowid, code, label as libelle, width, height, unit, active FROM ".MAIN_DB_PREFIX."c_paper_format";
$tabsql[16]= "SELECT code, label as libelle, active FROM ".MAIN_DB_PREFIX."c_prospectlevel";
$tabsql[17]= "SELECT id      as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_type_fees";
$tabsql[18]= "SELECT rowid   as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_shipment_mode";
$tabsql[19]= "SELECT id      as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_effectif";
$tabsql[20]= "SELECT rowid   as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_input_method";
$tabsql[21]= "SELECT c.rowid as rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_availability AS c";
$tabsql[22]= "SELECT rowid   as rowid, code, label, active FROM ".MAIN_DB_PREFIX."c_input_reason";

// Critere de tri du dictionnaire
$tabsqlsort[1] ="pays ASC, code ASC";
$tabsqlsort[2] ="pays ASC, code ASC";
$tabsqlsort[3] ="pays ASC, code ASC";
$tabsqlsort[4] ="code ASC";
$tabsqlsort[5] ="libelle ASC";
$tabsqlsort[6] ="a.type ASC, a.module, a.position, a.code ASC";
$tabsqlsort[7] ="pays ASC, code ASC, a.libelle ASC";
$tabsqlsort[8] ="libelle ASC";
$tabsqlsort[9] ="code ASC";
$tabsqlsort[10]="pays ASC, taux ASC, recuperableonly ASC, localtax1 ASC, localtax2 ASC";
$tabsqlsort[11]="element ASC, source ASC, code ASC";
$tabsqlsort[12]="sortorder ASC, code ASC";
$tabsqlsort[13]="code ASC";
$tabsqlsort[14]="pays ASC, e.organization ASC, code ASC";
$tabsqlsort[15]="rowid ASC";
$tabsqlsort[16]="sortorder ASC";
$tabsqlsort[17]="code ASC";
$tabsqlsort[18]="code ASC, libelle ASC";
$tabsqlsort[19]="id ASC";
$tabsqlsort[20]="code ASC, libelle ASC";
$tabsqlsort[21]="code ASC, label ASC";
$tabsqlsort[22]="code ASC, label ASC";

// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield[1] = "code,libelle,pays";
$tabfield[2] = "code,libelle,region_id,region,pays";   // "code,libelle,region,pays_code-pays"
$tabfield[3] = "code,libelle,pays_id,pays";
$tabfield[4] = "code,libelle";
$tabfield[5] = "code,libelle";
$tabfield[6] = "code,libelle,type,position";
$tabfield[7] = "code,libelle,pays_id,pays,deductible";
$tabfield[8] = "code,libelle";
//$tabfield[9] = "code,code_iso,libelle,symbole";
$tabfield[9] = "code,code_iso,libelle";
$tabfield[10]= "pays_id,pays,taux,recuperableonly,localtax1,localtax2,accountancy_code,note";
$tabfield[11]= "element,source,code,libelle";
$tabfield[12]= "code,libelle,libelle_facture,nbjour,fdm,decalage";
$tabfield[13]= "code,libelle,type";
$tabfield[14]= "code,libelle,price,organization,pays_id,pays";
$tabfield[15]= "code,libelle,width,height,unit";
$tabfield[16]= "code,libelle";
$tabfield[17]= "code,libelle";
$tabfield[18]= "code,libelle";
$tabfield[19]= "code,libelle";
$tabfield[20]= "code,libelle";
$tabfield[21]= "code,label";
$tabfield[22]= "code,label";

// Nom des champs d'edition pour modification d'un enregistrement
$tabfieldvalue[1] = "code,libelle,pays";
$tabfieldvalue[2] = "code,libelle,region";   // "code,libelle,region"
$tabfieldvalue[3] = "code,libelle,pays";
$tabfieldvalue[4] = "code,libelle";
$tabfieldvalue[5] = "code,libelle";
$tabfieldvalue[6] = "code,libelle,type,position";
$tabfieldvalue[7] = "code,libelle,pays,deductible";
$tabfieldvalue[8] = "code,libelle";
//$tabfieldvalue[9] = "code,code_iso,libelle,symbole";
$tabfieldvalue[9] = "code,code_iso,libelle";
$tabfieldvalue[10]= "pays,taux,recuperableonly,localtax1,localtax2,accountancy_code,note";
$tabfieldvalue[11]= "element,source,code,libelle";
$tabfieldvalue[12]= "code,libelle,libelle_facture,nbjour,fdm,decalage";
$tabfieldvalue[13]= "code,libelle,type";
$tabfieldvalue[14]= "code,libelle,price,organization,pays";
$tabfieldvalue[15]= "code,libelle,width,height,unit";
$tabfieldvalue[16]= "code,libelle";
$tabfieldvalue[17]= "code,libelle";
$tabfieldvalue[18]= "code,libelle";
$tabfieldvalue[19]= "code,libelle";
$tabfieldvalue[20]= "code,libelle";
$tabfieldvalue[21]= "code,label";
$tabfieldvalue[22]= "code,label";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert[1] = "code,libelle,fk_pays";
$tabfieldinsert[2] = "code_departement,nom,fk_region";
$tabfieldinsert[3] = "code_region,nom,fk_pays";
$tabfieldinsert[4] = "code,libelle";
$tabfieldinsert[5] = "code,civilite";
$tabfieldinsert[6] = "code,libelle,type,position";
$tabfieldinsert[7] = "code,libelle,fk_pays,deductible";
$tabfieldinsert[8] = "code,libelle";
//$tabfieldinsert[9] = "code,code_iso,label,symbole";
$tabfieldinsert[9] = "code,code_iso,label";
$tabfieldinsert[10]= "fk_pays,taux,recuperableonly,localtax1,localtax2,accountancy_code,note";
$tabfieldinsert[11]= "element,source,code,libelle";
$tabfieldinsert[12]= "code,libelle,libelle_facture,nbjour,fdm,decalage";
$tabfieldinsert[13]= "code,libelle,type";
$tabfieldinsert[14]= "code,libelle,price,organization,fk_pays";
$tabfieldinsert[15]= "code,label,width,height,unit";
$tabfieldinsert[16]= "code,label";
$tabfieldinsert[17]= "code,libelle";
$tabfieldinsert[18]= "code,libelle";
$tabfieldinsert[19]= "code,libelle";
$tabfieldinsert[20]= "code,libelle";
$tabfieldinsert[21]= "code,label";
$tabfieldinsert[22]= "code,label";

// Nom du rowid si le champ n'est pas de type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid[1] = "";
$tabrowid[2] = "";
$tabrowid[3] = "";
$tabrowid[4] = "rowid";
$tabrowid[5] = "rowid";
$tabrowid[6] = "id";
$tabrowid[7] = "id";
$tabrowid[8] = "id";
$tabrowid[9] = "code";
$tabrowid[10]= "";
$tabrowid[11]= "rowid";
$tabrowid[12]= "rowid";
$tabrowid[13]= "id";
$tabrowid[14]= "";
$tabrowid[15]= "";
$tabrowid[16]= "code";
$tabrowid[17]= "id";
$tabrowid[18]= "rowid";
$tabrowid[19]= "id";
$tabrowid[20]= "";
$tabrowid[21]= "rowid";
$tabrowid[22]= "rowid";

// Condition to show dictionnary in setup page
$tabcond[1] = true;
$tabcond[2] = true;
$tabcond[3] = true;
$tabcond[4] = true;
$tabcond[5] = $conf->societe->enabled||$conf->adherent->enabled;
$tabcond[6] = $conf->agenda->enabled;
$tabcond[7] = $conf->tax->enabled;
$tabcond[8] = $conf->societe->enabled;
$tabcond[9] = true;
$tabcond[10]= true;
$tabcond[11]= true;
$tabcond[12]= $conf->commande->enabled||$conf->propale->enabled||$conf->facture->enabled||$conf->fournisseur->enabled;
$tabcond[13]= $conf->commande->enabled||$conf->propale->enabled||$conf->facture->enabled||$conf->fournisseur->enabled;
$tabcond[14]= $conf->product->enabled&&$conf->ecotax->enabled;
$tabcond[15]= true;
$tabcond[16]= $conf->societe->enabled && empty($conf->global->SOCIETE_DISABLE_PROSPECTS);
$tabcond[17]= $conf->deplacement->enabled;
$tabcond[18]= $conf->expedition->enabled;
$tabcond[19]= $conf->societe->enabled;
$tabcond[20]= $conf->fournisseur->enabled;
$tabcond[21]= $conf->propale->enabled;
$tabcond[22]= $conf->commande->enabled||$conf->propale->enabled;


complete_dictionnary_with_modules($taborder,$tabname,$tablib,$tabsql,$tabsqlsort,$tabfield,$tabfieldvalue,$tabfieldinsert,$tabrowid,$tabcond);

// Define elementList and sourceList (used for dictionnary "type of contacts")
$elementList = array();
$sourceList=array();
if (GETPOST("id") == 11)
{
    $langs->load("orders");
    $langs->load("contracts");
    $langs->load("projects");
    $langs->load("propal");
    $langs->load("bills");
    $langs->load("interventions");
    $elementList = array("commande"=>$langs->trans("Order"),
    "order_supplier"=>$langs->trans("SupplierOrder"),
    "contrat"=>$langs->trans("Contract"),
    "project"=>$langs->trans("Project"),
    "project_task"=>$langs->trans("Task"),
    "propal"=>$langs->trans("Propal"),
    "facture"=>$langs->trans("Bill"),
    "facture_fourn"=>$langs->trans("SupplierBill"),
    "fichinter"=>$langs->trans("InterventionCard"));
    if ($conf->global->MAIN_SUPPORT_CONTACT_TYPE_FOR_THIRDPARTIES) $elementList["societe"]=$langs->trans("ThirdParty");
    $sourceList = array("internal"=>$langs->trans("Internal"),
    "external"=>$langs->trans("External"));
}

$msg='';


/*
 * Actions ajout ou modification d'une entree dans un dictionnaire de donnee
 */
if ($_POST["actionadd"] || $_POST["actionmodify"])
{
    $listfield=explode(',',$tabfield[$_POST["id"]]);
    $listfieldinsert=explode(',',$tabfieldinsert[$_POST["id"]]);
    $listfieldmodify=explode(',',$tabfieldinsert[$_POST["id"]]);
    $listfieldvalue=explode(',',$tabfieldvalue[$_POST["id"]]);

    // Check that all fields are filled
    $ok=1;
    foreach ($listfield as $f => $value)
    {
        if ($value == 'pays')
        {
            if (in_array('region_id',$listfield)) { continue; }		// For region page, we do not require the country input
        }
        if ((! isset($_POST[$value]) || $_POST[$value]=='')
        && $listfield[$f] != 'decalage'  // Fields that are not mandatory
        && $listfield[$f] != 'module'   // Fields that are not mandatory
        && $listfield[$f] != 'accountancy_code')   // Fields that are not mandatory
        {
            $ok=0;
            $fieldnamekey=$listfield[$f];
            // We take translate key of field
            if ($fieldnamekey == 'libelle')  $fieldnamekey='Label';
            if ($fieldnamekey == 'nbjour')   $fieldnamekey='NbOfDays';
            if ($fieldnamekey == 'decalage') $fieldnamekey='Offset';
            if ($fieldnamekey == 'module')   $fieldnamekey='Module';
            $msg.=$langs->trans("ErrorFieldRequired",$langs->transnoentities($fieldnamekey)).'<br>';
        }
    }
    // Autres verif
    if ($tabname[$_POST["id"]] == MAIN_DB_PREFIX."c_actioncomm" && isset($_POST["type"]) && $_POST["type"]=='system') {
        $ok=0;
        $msg.="Value 'system' for type is reserved. You can use 'user' as value to add your own record.<br>";
    }
    if (isset($_POST["code"]) && $_POST["code"]=='0') {
        $ok=0;
        $msg.="Code can't contains value 0<br>";
    }
    if (isset($_POST["pays"]) && $_POST["pays"]=='0') {
        $ok=0;
        $msg.=$langs->trans("ErrorFieldRequired",$langs->trans("Country")).'<br>';
    }

    // Si verif ok et action add, on ajoute la ligne
    if ($ok && $_POST["actionadd"])
    {
        if ($tabrowid[$_POST["id"]])
        {
            // Recupere id libre pour insertion
            $newid=0;
            $sql = "SELECT max(".$tabrowid[$_POST["id"]].") newid from ".$tabname[$_POST["id"]];
            $result = $db->query($sql);
            if ($result)
            {
                $obj = $db->fetch_object($result);
                $newid=($obj->newid + 1);

            } else {
                dol_print_error($db);
            }
        }

        // Add new entry
        $sql = "INSERT INTO ".$tabname[$_POST["id"]]." (";
        // List of fields
        if ($tabrowid[$_POST["id"]] &&
        ! in_array($tabrowid[$_POST["id"]],$listfieldinsert)) $sql.= $tabrowid[$_POST["id"]].",";
        $sql.= $tabfieldinsert[$_POST["id"]];
        $sql.=",active)";
        $sql.= " VALUES(";
        // List of values
        if ($tabrowid[$_POST["id"]] &&
        ! in_array($tabrowid[$_POST["id"]],$listfieldinsert)) $sql.= $newid.",";
        $i=0;
        foreach ($listfieldinsert as $f => $value)
        {
            if ($value == 'price') { $_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]],'MU'); }
            if ($i) $sql.=",";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.=",1)";

        dol_syslog("actionadd sql=".$sql);
        $result = $db->query($sql);
        if ($result)	// Add is ok
        {
            $oldid=$_POST["id"];
            $_POST=array('id'=>$oldid);	// Clean $_POST array, we keep only
            $_GET["id"]=$_POST["id"];   // Force affichage dictionnaire en cours d'edition
        }
        else
        {
            if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                $msg=$langs->trans("ErrorRecordAlreadyExists").'<br>';
            }
            else {
                dol_print_error($db);
            }
        }
    }

    // Si verif ok et action modify, on modifie la ligne
    if ($ok && $_POST["actionmodify"])
    {
        if ($tabrowid[$_POST["id"]]) { $rowidcol=$tabrowid[$_POST["id"]]; }
        else { $rowidcol="rowid"; }

        // Modify entry
        $sql = "UPDATE ".$tabname[$_POST["id"]]." SET ";
        // Modifie valeur des champs
        if ($tabrowid[$_POST["id"]] && !in_array($tabrowid[$_POST["id"]],$listfieldmodify))
        {
            $sql.= $tabrowid[$_POST["id"]]."=";
            $sql.= "'".$db->escape($_POST["rowid"])."', ";
        }
        $i = 0;
        foreach ($listfieldmodify as $field)
        {
            if ($field == 'price') { $_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]],'MU'); }
            if ($i) $sql.=",";
            $sql.= $field."=";
            if ($_POST[$listfieldvalue[$i]] == '') $sql.="null";
            else $sql.="'".$db->escape($_POST[$listfieldvalue[$i]])."'";
            $i++;
        }
        $sql.= " WHERE ".$rowidcol." = '".$_POST["rowid"]."'";

        dol_syslog("actionmodify sql=".$sql);
        //print $sql;
        $resql = $db->query($sql);
        if (! $resql)
        {
            $msg=$db->error();
        }
    }

    if ($msg) $msg='<div class="error">'.$msg.'</div>';
    $_GET["id"]=$_POST["id"];       // Force affichage dictionnaire en cours d'edition
}

if ($_POST["actioncancel"])
{
    $_GET["id"]=$_POST["id"];       // Force affichage dictionnaire en cours d'edition
}

if ($_REQUEST['action'] == 'confirm_delete' && $_REQUEST['confirm'] == 'yes')       // delete
{
    if ($tabrowid[$_GET["id"]]) { $rowidcol=$tabrowid[$_GET["id"]]; }
    else { $rowidcol="rowid"; }

    $sql = "DELETE from ".$tabname[$_GET["id"]]." WHERE ".$rowidcol."='".$_GET["rowid"]."'";

    dol_syslog("delete sql=".$sql);
    $result = $db->query($sql);
    if (! $result)
    {
        if ($db->errno() == 'DB_ERROR_CHILD_EXISTS')
        {
            $msg='<div class="error">'.$langs->trans("ErrorRecordIsUsedByChild").'</div>';
        }
        else
        {
            dol_print_error($db);
        }
    }
}

if ($_GET["action"] == $acts[0])       // activate
{
    if ($tabrowid[$_GET["id"]]) { $rowidcol=$tabrowid[$_GET["id"]]; }
    else { $rowidcol="rowid"; }

    if ($_GET["rowid"]) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 1 WHERE ".$rowidcol."='".$_GET["rowid"]."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 1 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}

if ($_GET["action"] == $acts[1])       // disable
{
    if ($tabrowid[$_GET["id"]]) { $rowidcol=$tabrowid[$_GET["id"]]; }
    else { $rowidcol="rowid"; }

    if ($_GET["rowid"]) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 0 WHERE ".$rowidcol."='".$_GET["rowid"]."'";
    }
    elseif ($_GET["code"]) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 0 WHERE code='".$_GET["code"]."'";
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dol_print_error($db);
    }
}


/*
 * View
 */

$form = new Form($db);
$formadmin=new FormAdmin($db);

llxHeader();

$titre=$langs->trans("DictionnarySetup");
$linkback='';
if ($_GET["id"])
{
    $titre.=' - '.$langs->trans($tablib[$_GET["id"]]);
    $linkback='<a href="'.DOL_URL_ROOT.'/admin/dict.php">'.$langs->trans("BackToDictionnaryList").'</a>';
}
print_fiche_titre($titre,$linkback,'setup');

if (empty($_GET["id"]))
{
    print $langs->trans("DictionnaryDesc");
    print " ".$langs->trans("OnlyActiveElementsAreShown")."<br>\n";
}
print "<br>\n";


/*
 * Confirmation de la suppression de la ligne
 */
if ($_GET['action'] == 'delete')
{
    $ret=$form->form_confirm($_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$_GET["rowid"].'&code='.$_GET["code"].'&id='.$_GET["id"], $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete','',0,1);
    if ($ret == 'html') print '<br>';
}

/*
 * Show a dictionnary
 */
if ($_GET["id"])
{
    dol_htmloutput_mesg($msg);

    // Complete requete recherche valeurs avec critere de tri
    $sql=$tabsql[$_GET["id"]];
    if ($_GET["sortfield"])
    {
        // If sort order is "pays", we use pays_code instead
        if ($_GET["sortfield"] == 'pays') $_GET["sortfield"]='pays_code';
        $sql.= " ORDER BY ".$_GET["sortfield"];
        if ($_GET["sortorder"])
        {
            $sql.=" ".strtoupper($_GET["sortorder"]);
        }
        $sql.=", ";
        // Remove from default sort order the choosed order
        $tabsqlsort[$_GET["id"]]=preg_replace('/'.$_GET["sortfield"].' '.$_GET["sortorder"].',/i','',$tabsqlsort[$_GET["id"]]);
        $tabsqlsort[$_GET["id"]]=preg_replace('/'.$_GET["sortfield"].',/i','',$tabsqlsort[$_GET["id"]]);
    }
    else {
        $sql.=" ORDER BY ";
    }
    $sql.=$tabsqlsort[$_GET["id"]];
    $sql.=$db->plimit($listlimit+1,$offset);
    //print $sql;

    $fieldlist=explode(',',$tabfield[$_GET["id"]]);

    print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<table class="noborder" width="100%">';

    // Form to add a new line
    if ($tabname[$_GET["id"]])
    {
        $alabelisused=0;
        $var=false;

        $fieldlist=explode(',',$tabfield[$_GET["id"]]);
        //        print '<table class="noborder" width="100%">';

        // Line for title
        print '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value)
        {
            // Determine le nom du champ par rapport aux noms possibles
            // dans les dictionnaires de donnees
            $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
            $valuetoshow=$langs->trans($valuetoshow);   // try to translate
            if ($fieldlist[$field]=='source')          { $valuetoshow=$langs->trans("Contact"); }
            if ($fieldlist[$field]=='price')           { $valuetoshow=$langs->trans("PriceUHT"); }
            if ($fieldlist[$field]=='taux')            { $valuetoshow=$langs->trans("Rate"); }
            if ($fieldlist[$field]=='organization')    { $valuetoshow=$langs->trans("Organization"); }
            if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
            if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
            if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
            if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label")."*"; }
            if ($fieldlist[$field]=='libelle_facture') { $valuetoshow=$langs->trans("LabelOnDocuments")."*"; }
            if ($fieldlist[$field]=='pays')            {
                if (in_array('region_id',$fieldlist)) { print '<td>&nbsp;</td>'; continue; }		// For region page, we do not show the country input
                $valuetoshow=$langs->trans("Country"); }
                if ($fieldlist[$field]=='recuperableonly') { $valuetoshow=MAIN_LABEL_MENTION_NPR; }
                if ($fieldlist[$field]=='nbjour')          { $valuetoshow=$langs->trans("NbOfDays"); }
                if ($fieldlist[$field]=='fdm')             { $valuetoshow=$langs->trans("AtEndOfMonth"); }
                if ($fieldlist[$field]=='decalage')        { $valuetoshow=$langs->trans("Offset"); }
                if ($fieldlist[$field]=='width')           { $valuetoshow=$langs->trans("Width"); }
                if ($fieldlist[$field]=='height')          { $valuetoshow=$langs->trans("Height"); }
                if ($fieldlist[$field]=='unit')            { $valuetoshow=$langs->trans("MeasuringUnit"); }
                if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='pays_id') { $valuetoshow=''; }
                if ($fieldlist[$field]=='accountancy_code'){ $valuetoshow=$langs->trans("AccountancyCode"); }
                if ($valuetoshow != '')
                {
                    print '<td>';
                    print $valuetoshow;
                    print '</td>';
                }

                if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') $alabelisused=1;
        }
        print '<td colspan="3">';
        print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
        print '&nbsp;</td>';
        print '</tr>';

        // Line to type new values
        print "<tr ".$bc[$var].">";

        $obj='';
        // If data was already input, we define them in obj to populate input fields.
        if ($_POST["actionadd"])
        {
            foreach ($fieldlist as $key=>$val)
            {
                if (! empty($_POST[$val])) $obj->$val=$_POST[$val];

            }
        }
        
        $tmpaction = 'create';
        $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$_GET["id"]]);
        $reshook=$hookmanager->executeHooks('createDictionaryFieldlist',$parameters, $obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
        $error=$hookmanager->error; $errors=$hookmanager->errors;

        if (empty($reshook)) fieldList($fieldlist,$obj);

        print '<td colspan="3" align="right"><input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'"></td>';
        print "</tr>";

        if ($alabelisused)  // Si un des champs est un libelle
        {
            print '<tr><td colspan="'.(count($fieldlist)+2).'">* '.$langs->trans("LabelUsedByDefault").'.</td></tr>';
        }
        print '<tr><td colspan="'.(count($fieldlist)+2).'">&nbsp;</td></tr>';
    }


    // List of available values in database
    dol_syslog("htdocs/admin/dict sql=".$sql, LOG_DEBUG);
    $resql=$db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=true;
        if ($num)
        {
            // There is several pages
            if ($num > $listlimit)
            {
                print '<tr class="none"><td align="right" colspan="'.(3+count($fieldlist)).'">';
                print_fleche_navigation($page,$_SERVER["PHP_SELF"],'&id='.GETPOST('id'),($num > $listlimit),$langs->trans("Page").' '.($page+1));
                print '</td></tr>';
            }

            // Title of lines
            print '<tr class="liste_titre">';
            foreach ($fieldlist as $field => $value)
            {
                // Determine le nom du champ par rapport aux noms possibles
                // dans les dictionnaires de donnees
                $showfield=1;							  	// Par defaut
                $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
                $valuetoshow=$langs->trans($valuetoshow);   // try to translate
                if ($fieldlist[$field]=='source')          { $valuetoshow=$langs->trans("Contact"); }
                if ($fieldlist[$field]=='price')           { $valuetoshow=$langs->trans("PriceUHT"); }
                if ($fieldlist[$field]=='taux')            { $valuetoshow=$langs->trans("Rate"); }
                if ($fieldlist[$field]=='organization')    { $valuetoshow=$langs->trans("Organization"); }
                if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
                if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
                if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
                if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label")."*";  }
                if ($fieldlist[$field]=='libelle_facture') { $valuetoshow=$langs->trans("LabelOnDocuments")."*"; }
                if ($fieldlist[$field]=='pays')            { $valuetoshow=$langs->trans("Country"); }
                if ($fieldlist[$field]=='recuperableonly') { $valuetoshow=MAIN_LABEL_MENTION_NPR; }
                if ($fieldlist[$field]=='nbjour')          { $valuetoshow=$langs->trans("NbOfDays"); }
                if ($fieldlist[$field]=='fdm')             { $valuetoshow=$langs->trans("AtEndOfMonth"); }
                if ($fieldlist[$field]=='decalage')        { $valuetoshow=$langs->trans("Offset"); }
                if ($fieldlist[$field]=='width')           { $valuetoshow=$langs->trans("Width"); }
                if ($fieldlist[$field]=='height')          { $valuetoshow=$langs->trans("Height"); }
                if ($fieldlist[$field]=='unit')            { $valuetoshow=$langs->trans("MeasuringUnit"); }
                if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='pays_id') { $showfield=0; }
                if ($fieldlist[$field]=='accountancy_code'){ $valuetoshow=$langs->trans("AccountancyCode"); }
                // Affiche nom du champ
                if ($showfield)
                {
                    print_liste_field_titre($valuetoshow,"dict.php",$fieldlist[$field],($page?'page='.$page.'&':'').'&id='.GETPOST("id"),"","",$sortfield,$sortorder);
                }
            }
            print_liste_field_titre($langs->trans("Status"),"dict.php","active",($page?'page='.$page.'&':'').'&id='.GETPOST("id"),"",'align="center"',$sortfield,$sortorder);
            print '<td colspan="2"  class="liste_titre">&nbsp;</td>';
            print '</tr>';

            // Lines with values
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $var=!$var;
                //print_r($obj);
                print "<tr ".$bc[$var].">";

                if ($action == 'edit' && ($_GET["rowid"] == ($obj->rowid?$obj->rowid:$obj->code)))
                {
                    print '<form action="dict.php" method="post">';
                    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
                    print '<input type="hidden" name="id" value="'.GETPOST("id").'">';
                    print '<input type="hidden" name="page" value="'.$page.'">';
                    print '<input type="hidden" name="rowid" value="'.$_GET["rowid"].'">';
                    
                    $tmpaction='edit';
                    $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$_GET["id"]]);
                    $reshook=$hookmanager->executeHooks('editDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
                    $error=$hookmanager->error; $errors=$hookmanager->errors;
                    
                    if (empty($reshook)) fieldList($fieldlist,$obj);
                    
                    print '<td colspan="3" align="right"><a name="'.($obj->rowid?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
                    print '&nbsp;<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'"></td>';
                }
                else
                {
                    $tmpaction = 'view';
                    $parameters=array('fieldlist'=>$fieldlist, 'tabname'=>$tabname[$_GET["id"]]);
                    $reshook=$hookmanager->executeHooks('viewDictionaryFieldlist',$parameters,$obj, $tmpaction);    // Note that $action and $object may have been modified by some hooks
                    
                    $error=$hookmanager->error; $errors=$hookmanager->errors;

                    if (empty($reshook))
                    {
                        foreach ($fieldlist as $field => $value)
                        {
                            $showfield=1;
                            $valuetoshow=$obj->$fieldlist[$field];
                            if ($valuetoshow=='all') {
                                $valuetoshow=$langs->trans('All');
                            }
                            else if ($fieldlist[$field]=='pays') {
                                if (empty($obj->pays_code))
                                {
                                    $valuetoshow='-';
                                }
                                else
                                {
                                    $key=$langs->trans("Country".strtoupper($obj->pays_code));
                                    $valuetoshow=($key != "Country".strtoupper($obj->pays_code))?$obj->pays_code." - ".$key:$obj->pays;
                                }
                            }
                            else if ($fieldlist[$field]=='recuperableonly' || $fieldlist[$field]=='fdm') {
                                $valuetoshow=yn($valuetoshow);
                            }
                            else if ($fieldlist[$field]=='price') {
                                $valuetoshow=price($valuetoshow);
                            }
                            else if ($fieldlist[$field]=='libelle_facture') {
                                $langs->load("bills");
                                $key=$langs->trans("PaymentCondition".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "PaymentCondition".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                                $valuetoshow=nl2br($valuetoshow);
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_pays') {
                                $key=$langs->trans("Country".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "Country".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='label' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_availability') {
                                $langs->load("propal");
                                $key=$langs->trans("AvailabilityType".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "AvailabilityType".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_actioncomm') {
                                $key=$langs->trans("Action".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "Action".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_currencies') {
                                $key=$langs->trans("Currency".strtoupper($obj->code_iso));
                                $valuetoshow=($obj->code_iso && $key != "Currency".strtoupper($obj->code_iso))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_typent') {
                                $key=$langs->trans(strtoupper($obj->code));
                                $valuetoshow=($key != strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_prospectlevel') {
                                $key=$langs->trans(strtoupper($obj->code));
                                $valuetoshow=($key != strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_civilite') {
                                $key=$langs->trans("Civility".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "Civility".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_type_contact') {
                                $key=$langs->trans("TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "TypeContact_".$obj->element."_".$obj->source."_".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_payment_term') {
                                $langs->load("bills");
                                $key=$langs->trans("PaymentConditionShort".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "PaymentConditionShort".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_paiement') {
                                $langs->load("bills");
                                $key=$langs->trans("PaymentType".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "PaymentType".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='label' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_input_reason') {
                                $key=$langs->trans("DemandReasonType".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "DemandReasonType".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_input_method') {
                                $langs->load("orders");
                                $key=$langs->trans($obj->code);
                                $valuetoshow=($obj->code && $key != $obj->code)?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='libelle' && $tabname[$_GET["id"]]==MAIN_DB_PREFIX.'c_shipment_mode') {
                                $langs->load("sendings");
                                $key=$langs->trans("SendingMethod".strtoupper($obj->code));
                                $valuetoshow=($obj->code && $key != "SendingMethod".strtoupper($obj->code))?$key:$obj->$fieldlist[$field];
                            }
                            else if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='pays_id') {
                                $showfield=0;
                            }
                            if ($showfield) print '<td>'.$valuetoshow.'</td>';
                        }
                    }

                    print '<td align="center" nowrap="nowrap">';
                    // Est-ce une entree du dictionnaire qui peut etre desactivee ?
                    $iserasable=1;  // Oui par defaut
                    if (isset($obj->code) && ($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i',$obj->code))) $iserasable=0;
                    if (isset($obj->code) && $obj->code == 'RECEP') $iserasable=0;
                    if (isset($obj->code) && $obj->code == 'EF0') $iserasable=0;
                    if ($obj->type && $obj->type == 'system') $iserasable=0;

                    if ($iserasable) print '<a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
                    else print $langs->trans("AlwaysActive");
                    print "</td>";

                    // Modify link
                    if ($iserasable) print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action=edit#'.($obj->rowid?$obj->rowid:$obj->code).'">'.img_edit().'</a></td>';
                    else print '<td>&nbsp;</td>';
                    
                    // Delete link
                    if ($iserasable) print '<td align="center"><a href="'.$_SERVER["PHP_SELF"].'?'.($page?'page='.$page.'&':'').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action=delete">'.img_delete().'</a></td>';
                    else print '<td>&nbsp;</td>';

                    print "</tr>\n";
                }
                $i++;
            }
        }
    }
    else {
        dol_print_error($db);
    }

    print '</table>';

    print '</form>';
}
else
{
    /*
     * Show list of dictionnary to show
     */

    $var=true;
    $lastlineisempty=false;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre">';
    //print '<td>'.$langs->trans("Module").'</td>';
    print '<td colspan="2">'.$langs->trans("Dictionnary").'</td>';
    print '<td>'.$langs->trans("Table").'</td>';
    print '</tr>';

    foreach ($taborder as $i)
    {
        if ($tabname[$i] && empty($tabcond[$i])) continue;

        if ($i)
        {
            $var=!$var;
            $value=$tabname[$i];
            print '<tr '.$bc[$var].'><td width="30%">';
            if (! empty($tabcond[$i]))
            {
                print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$i.'">'.$langs->trans($tablib[$i]).'</a>';
            }
            else
            {
                print $langs->trans($tablib[$i]);
            }
            print '</td>';
            print '<td>';
            /*if (empty($tabcond[$i]))
             {
             print info_admin($langs->trans("DictionnaryDisabledSinceNoModuleNeedIt"),1);
             }*/
            print '</td>';
            print '<td>'.$tabname[$i].'</td></tr>';
            $lastlineisempty=false;
        }
        else
        {
            if (! $lastlineisempty)
            {
                $var=!$var;
                print '<tr '.$bc[$var].'><td width="30%">&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
                $lastlineisempty=true;
            }
        }
    }
    print '</table>';
}

print '<br>';

$db->close();

llxFooter();


/**
 *	Show field
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *	@return		void
 */
function fieldList($fieldlist,$obj='')
{
    global $conf,$langs,$db;
    global $form;
    global $region_id;
    global $elementList,$sourceList;

    $formadmin = new FormAdmin($db);
    $formcompany = new FormCompany($db);

    foreach ($fieldlist as $field => $value)
    {
    	if ($fieldlist[$field] == 'pays') {
    		if (in_array('region_id',$fieldlist)) { print '<td>&nbsp;</td>'; continue; }	// For region page, we do not show the country input
            print '<td>';
            print $form->select_country(($obj->pays_code?$obj->pays_code:$obj->pays),'pays');
            print '</td>';
        }
        elseif ($fieldlist[$field] == 'pays_id') {
            $pays_id = (! empty($obj->$fieldlist[$field])) ? $obj->$fieldlist[$field] : 0;
            print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$pays_id.'">';
        }
        elseif ($fieldlist[$field] == 'region') {
            print '<td>';
            $formcompany->select_region($region_id,'region');
            print '</td>';
        }
        elseif ($fieldlist[$field] == 'region_id') {
            $region_id = $obj->$fieldlist[$field]?$obj->$fieldlist[$field]:0;
            print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$region_id.'">';
        }
        elseif ($fieldlist[$field] == 'lang') {
            print '<td>';
            print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT,'lang');
            print '</td>';
        }
        // Le type de l'element (pour les type de contact).'
        elseif ($fieldlist[$field] == 'element')
        {
            print '<td>';
            print $form->selectarray('element', $elementList,$obj->$fieldlist[$field]);
            print '</td>';
        }
        // La source de l'element (pour les type de contact).'
        elseif ($fieldlist[$field] == 'source')
        {
            print '<td>';
            print $form->selectarray('source', $sourceList,$obj->$fieldlist[$field]);
            print '</td>';
        }
        elseif ($fieldlist[$field] == 'type' && $tabname[$_GET["id"]] == MAIN_DB_PREFIX."c_actioncomm")
        {
            print '<td>';
            print 'user<input type="hidden" name="type" value="user">';
            print '</td>';
        }
        elseif ($fieldlist[$field] == 'recuperableonly' || $fieldlist[$field] == 'fdm') {
            print '<td>';
            print $form->selectyesno($fieldlist[$field],$obj->$fieldlist[$field],1);
            print '</td>';
        }
        elseif (in_array($fieldlist[$field],array('nbjour','decalage','taux','localtax1','localtax2'))) {
            print '<td><input type="text" class="flat" value="'.$obj->$fieldlist[$field].'" size="3" name="'.$fieldlist[$field].'"></td>';
        }
        elseif ($fieldlist[$field] == 'libelle_facture') {
            print '<td><textarea cols="30" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.$obj->$fieldlist[$field].'</textarea></td>';
        }
        elseif ($fieldlist[$field] == 'price') {
            print '<td><input type="text" class="flat" value="'.price($obj->$fieldlist[$field]).'" size="8" name="'.$fieldlist[$field].'"></td>';
        }
        elseif ($fieldlist[$field] == 'code') {
            print '<td><input type="text" class="flat" value="'.$obj->$fieldlist[$field].'" size="10" name="'.$fieldlist[$field].'"></td>';
        }
        elseif ($fieldlist[$field]=='unit') {
            print '<td>';
            print $form->selectarray('unit',array('mm','cm','point','inch'),$obj->$fieldlist[$field],0,0,1);
            print '</td>';
        }
        else
        {
            print '<td>';
            print '<input type="text" '.($fieldlist[$field]=='libelle'?'size="32" ':'').' class="flat" value="'.$obj->$fieldlist[$field].'" name="'.$fieldlist[$field].'">';
            print '</td>';
        }
    }
}

?>