<?php
/* Copyright (C) 2004      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 *
 * $Id$
 */

/**
	    \file       htdocs/admin/dict.php
		\ingroup    setup
		\brief      Page d'administration des dictionnaires de données
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("other");
$langs->load("admin");
$langs->load("companies");

if (!$user->admin)
  accessforbidden();

$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = $langs->trans("Activate");
$actl[1] = $langs->trans("Disable");

$active = 1;

// Cette page est une page d'édition générique des dictionnaires de données
// Mettre ici tous les caractéristiques des dictionnaires

// Ordres d'affichage des dictionnaires (0 pour espace)
$taborder=array(9,0,4,3,2,0,1,8,0,5,11,0,6,0,10,12,13,0,14,0,7,0,15);

// Nom des tables des dictionnaires
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
$tabname[12]= MAIN_DB_PREFIX."cond_reglement";
$tabname[13]= MAIN_DB_PREFIX."c_paiement";
$tabname[14]= MAIN_DB_PREFIX."c_ecotaxe";
$tabname[15]= MAIN_DB_PREFIX."c_paper_format";

// Libellé des dictionnaires
$tablib[1] = $langs->trans("DictionnaryCompanyJuridicalType");
$tablib[2] = $langs->trans("DictionnaryCanton");
$tablib[3] = $langs->trans("DictionnaryRegion");
$tablib[4] = $langs->trans("DictionnaryCountry");
$tablib[5] = $langs->trans("DictionnaryCivility");
$tablib[6] = $langs->trans("DictionnaryActions");
$tablib[7] = $langs->trans("DictionnarySocialContributions");
$tablib[8] = $langs->trans("DictionnaryCompanyType");
$tablib[9] = $langs->trans("DictionnaryCurrency");
$tablib[10]= $langs->trans("DictionnaryVAT");
$tablib[11]= $langs->trans("DictionnaryTypeContact");
$tablib[12]= $langs->trans("DictionnaryPaymentConditions");
$tablib[13]= $langs->trans("DictionnaryPaymentModes");
$tablib[14]= $langs->trans("DictionnaryEcotaxe");
$tablib[15]= $langs->trans("DictionnaryPaperFormat");

// Requete pour extraction des données des dictionnaires
$tabsql[1] = "SELECT f.rowid as rowid, f.code, f.libelle, p.libelle as pays, f.active FROM ".MAIN_DB_PREFIX."c_forme_juridique as f, ".MAIN_DB_PREFIX."c_pays as p WHERE f.fk_pays=p.rowid";
$tabsql[2] = "SELECT d.rowid as rowid, d.code_departement as code , d.nom as libelle, d.fk_region as region_id, r.nom as region, p.libelle as pays, d.active FROM ".MAIN_DB_PREFIX."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid and r.active=1 and p.active=1";
$tabsql[3] = "SELECT r.rowid as rowid, code_region as code , nom as libelle, r.fk_pays as pays_id, p.libelle as pays, r.active FROM ".MAIN_DB_PREFIX."c_regions as r, ".MAIN_DB_PREFIX."c_pays as p WHERE r.fk_pays=p.rowid and p.active=1";
$tabsql[4] = "SELECT rowid   as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_pays";
$tabsql[5] = "SELECT c.rowid as rowid, c.code as code, c.civilite AS libelle, c.active FROM ".MAIN_DB_PREFIX."c_civilite AS c";
$tabsql[6] = "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.type, a.active FROM ".MAIN_DB_PREFIX."c_actioncomm AS a";
$tabsql[7] = "SELECT a.id    as rowid, a.id as code, a.libelle AS libelle, a.deductible, a.actioncompta, a.active FROM ".MAIN_DB_PREFIX."c_chargesociales AS a";
$tabsql[8] = "SELECT id      as rowid, code, libelle, active FROM ".MAIN_DB_PREFIX."c_typent";
$tabsql[9] = "SELECT code, code_iso, label as libelle, active FROM ".MAIN_DB_PREFIX."c_currencies";
$tabsql[10]= "SELECT t.rowid, t.taux, p.libelle as pays, t.recuperableonly, t.note, t.active FROM ".MAIN_DB_PREFIX."c_tva as t, llx_c_pays as p WHERE t.fk_pays=p.rowid";
$tabsql[11]= "SELECT t.rowid as rowid, element, source, code, libelle, active FROM ".MAIN_DB_PREFIX."c_type_contact AS t";
$tabsql[12]= "SELECT c.rowid as rowid, code, sortorder, c.libelle, c.libelle_facture, nbjour, fdm, decalage, active FROM ".MAIN_DB_PREFIX."cond_reglement AS c";
$tabsql[13]= "SELECT id      as rowid, code, c.libelle, type, active FROM ".MAIN_DB_PREFIX."c_paiement AS c";
$tabsql[14]= "SELECT e.rowid as rowid, e.code as code, e.libelle, e.price, e.organization, e.fk_pays as pays_id, p.libelle as pays, e.active FROM ".MAIN_DB_PREFIX."c_ecotaxe AS e, ".MAIN_DB_PREFIX."c_pays as p WHERE e.fk_pays=p.rowid and p.active=1";
$tabsql[15]= "SELECT rowid   as rowid, code, label as libelle, width, height, unit, active FROM ".MAIN_DB_PREFIX."c_paper_format";

// Critere de tri du dictionnaire
$tabsqlsort[1] ="pays, code ASC";
$tabsqlsort[2] ="pays, code ASC";
$tabsqlsort[3] ="pays, code ASC";
$tabsqlsort[4] ="libelle ASC";
$tabsqlsort[5] ="libelle ASC";
$tabsqlsort[6] ="a.type ASC, a.code ASC";
$tabsqlsort[7] ="a.libelle ASC";
$tabsqlsort[8] ="libelle ASC";
$tabsqlsort[9] ="code ASC";
$tabsqlsort[10]="pays ASC, taux ASC, recuperableonly ASC";
$tabsqlsort[11]="element ASC, source ASC, code ASC";
$tabsqlsort[12]="sortorder ASC, code ASC";
$tabsqlsort[13]="code ASC";
$tabsqlsort[14]="pays, e.organization ASC, code ASC";
$tabsqlsort[15]="rowid ASC";
 
// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield[1] = "code,libelle,pays";
$tabfield[2] = "code,libelle,region_id,region";   // "code,libelle,region"
$tabfield[3] = "code,libelle,pays_id,pays";
$tabfield[4] = "code,libelle";
$tabfield[5] = "code,libelle";
$tabfield[6] = "code,libelle,type";
$tabfield[7] = "libelle,deductible,actioncompta";
$tabfield[8] = "code,libelle";
$tabfield[9] = "code,code_iso,libelle";
$tabfield[10]= "pays,taux,recuperableonly,note";
$tabfield[11]= "element,source,code,libelle";
$tabfield[12]= "code,libelle,libelle_facture,nbjour,fdm,decalage";
$tabfield[13]= "code,libelle,type";
$tabfield[14]= "code,libelle,price,organization,pays_id,pays";
$tabfield[15]= "code,libelle,width,height,unit";

// Nom des champs d'édition pour modification du dictionnaire
$tabfieldvalue[1] = "code,libelle,pays";
$tabfieldvalue[2] = "code,libelle,region";   // "code,libelle,region"
$tabfieldvalue[3] = "code,libelle,pays";
$tabfieldvalue[4] = "code,libelle";
$tabfieldvalue[5] = "code,libelle";
$tabfieldvalue[6] = "code,libelle,type";
$tabfieldvalue[7] = "libelle,deductible,actioncompta";
$tabfieldvalue[8] = "code,libelle";
$tabfieldvalue[9] = "code,code_iso,libelle";
$tabfieldvalue[10]= "pays,taux,recuperableonly,note";
$tabfieldvalue[11]= "element,source,code,libelle";
$tabfieldvalue[12]= "code,libelle,libelle_facture,nbjour,fdm,decalage";
$tabfieldvalue[13]= "code,libelle,type";
$tabfieldvalue[14]= "code,libelle,price,organization,pays";
$tabfieldvalue[15]= "code,libelle,width,height,unit";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert[1] = "code,libelle,fk_pays";
$tabfieldinsert[2] = "code_departement,nom,fk_region";
$tabfieldinsert[3] = "code_region,nom,fk_pays";
$tabfieldinsert[4] = "code,libelle";
$tabfieldinsert[5] = "code,civilite";
$tabfieldinsert[6] = "code,libelle,type";
$tabfieldinsert[7] = "libelle,deductible,actioncompta";
$tabfieldinsert[8] = "code,libelle";
$tabfieldinsert[9] = "code,code_iso,label";
$tabfieldinsert[10]= "fk_pays,taux,recuperableonly,note";
$tabfieldinsert[11]= "element,source,code,libelle";
$tabfieldinsert[12]= "code,libelle,libelle_facture,nbjour,fdm,decalage";
$tabfieldinsert[13]= "code,libelle,type";
$tabfieldinsert[14]= "code,libelle,price,organization,fk_pays";
$tabfieldinsert[15]= "code,label,width,height,unit";

// Nom du rowid si le champ n'est pas de type autoincrément
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

// Condition to show dictionnary in setup page
$tabcond[1] = $conf->societe->enabled;
$tabcond[2] = true;
$tabcond[3] = true;
$tabcond[4] = true;
$tabcond[5] = $conf->societe->enabled;
$tabcond[6] = true;
$tabcond[7] = $conf->tax->enabled;
$tabcond[8] = $conf->societe->enabled;
$tabcond[9] = true;
$tabcond[10]= true;
$tabcond[11]= true;
$tabcond[12]= $conf->facture->enabled||$conf->fournisseur->enabled;
$tabcond[13]= $conf->facture->enabled||$conf->fournisseur->enabled;
$tabcond[14]= $conf->produit->enabled&&$conf->global->PRODUIT_USE_ECOTAXE;
$tabcond[15]= true;

$msg='';


$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];

/*
 * Actions ajout ou modification d'une entrée dans un dictionnaire de donnée
 */
if ($_POST["actionadd"] || $_POST["actionmodify"])
{
    $listfield=split(',',$tabfield[$_POST["id"]]);
    $listfieldinsert=split(',',$tabfieldinsert[$_POST["id"]]);
    $listfieldmodify=split(',',$tabfieldinsert[$_POST["id"]]);
    $listfieldvalue=split(',',$tabfieldvalue[$_POST["id"]]);

    // Verifie que tous les champs sont renseignés
    $ok=1;
    foreach ($listfield as $f => $value) {
        if (! isset($_POST[$value]) || $_POST[$value]=='') {
            $ok=0;
            $msg.=$langs->trans("ErrorFieldRequired",$listfield[$f]).'<br>';
        }
    }
    // Autres verif
    if (isset($_POST["code"]) && $_POST["code"]=='0') {
        $ok=0;
        $msg.="Le Code ne peut avoir la valeur 0<br>";
    }
    if (isset($_POST["pays"]) && $_POST["pays"]=='0') {
        $ok=0;
        $msg.=$langs->trans("ErrorFieldRequired",$langs->trans("Country")).'<br>';
    }
    
    // Si verif ok et action add, on ajoute la ligne
    if ($ok && $_POST["actionadd"]) {
        if ($tabrowid[$_POST["id"]]) {
            // Recupere id libre pour insertion
            $newid=0;
            $sql = "SELECT max(".$tabrowid[$_POST["id"]].") newid from ".$tabname[$_POST["id"]];
            $result = $db->query($sql);
            if ($result)
            {
                $obj = $db->fetch_object($result);
                $newid=($obj->newid + 1);
                        
            } else {
                dolibarr_print_error($db);
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
			$sql.="'".addslashes($_POST[$listfieldvalue[$i]])."'";
			$i++;
        }
        $sql.=",1)";

	     dolibarr_syslog("dict actionadd sql=".$sql);
        $result = $db->query($sql);
        if (!$result)
        {
            if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
                $msg=$langs->trans("ErrorRecordAlreadyExists").'<br>';
            }
            else {
                dolibarr_print_error($db);
            }
        }
    }
    
    // Si verif ok et action modify, on modifie la ligne
    if ($ok && $_POST["actionmodify"]) {
    	if ($tabrowid[$_POST["id"]]) { $rowidcol=$tabrowid[$_POST["id"]]; }
      else { $rowidcol="rowid"; }
      
        // Modify entry
        $sql = "UPDATE ".$tabname[$_POST["id"]]." SET ";
        // Modifie valeur des champs
		if ($tabrowid[$_POST["id"]] && !in_array($tabrowid[$_POST["id"]],$listfieldmodify))
        {
        	$sql.= $tabrowid[$_POST["id"]]."=";
        	$sql.= "'".addslashes($_POST["rowid"])."', ";
        }
        $i = 0;
        foreach ($listfieldmodify as $field) {
        	if ($field == 'price') { $_POST[$listfieldvalue[$i]] = price2num($_POST[$listfieldvalue[$i]],'MU'); }
        	if ($i) $sql.=",";
        	$sql.= $field."=";
        	$sql.= "'".addslashes($_POST[$listfieldvalue[$i]])."'";
          $i++;
        }
        $sql.= " WHERE ".$rowidcol." = '".$_POST["rowid"]."'";

		dolibarr_syslog("dict actionmodify sql=".$sql);
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

if ($_POST['action'] == 'confirm_delete' && $_POST['confirm'] == 'yes')       // delete
{
    if ($tabrowid[$_GET["id"]]) { $rowidcol=$tabrowid[$_GET["id"]]; }
    else { $rowidcol="rowid"; }

    $sql = "DELETE from ".$tabname[$_GET["id"]]." WHERE ".$rowidcol."='".$_GET["rowid"]."'";

	dolibarr_syslog("dict delete sql=".$sql);
    $result = $db->query($sql);
    if (! $result)
    {
        if ($db->errno() == 'DB_ERROR_CHILD_EXISTS')
        {
            $msg='<div class="error">'.$langs->trans("ErrorRecordIsUsedByChild").'</div>';
        }
        else 
        {
            dolibarr_print_error($db);
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
        dolibarr_print_error($db);
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
        dolibarr_print_error($db);
    }
}



llxHeader();

$html = new Form($db);

$titre=$langs->trans("DictionnarySetup");
if ($_GET["id"])
{
	$titre.=' - '.$tablib[$_GET["id"]];
}
print_fiche_titre($titre,'','setup');

print $langs->trans("DictionnaryDesc")."<br>\n";
print "<br>\n";

  /*
   * Confirmation de la suppression de la ligne
   */
  if ($_GET['action'] == 'delete')
  {
  	$html = new Form($db);
  	$html->form_confirm($_SERVER["PHP_SELF"].'?sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$_GET["rowid"].'&amp;code='.$_GET["code"].'&amp;id='.$_GET["id"], $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete');
    print '<br>';
  }

/*
 * Affichage d'un dictionnaire particulier
 */
if ($_GET["id"])
{
    if ($msg)
	{
        print $msg.'<br>';
    }

    // Complète requete recherche valeurs avec critere de tri
    $sql=$tabsql[$_GET["id"]];
    if ($_GET["sortfield"])
    {
        $sql.= " ORDER BY ".$_GET["sortfield"];
        if ($_GET["sortorder"])
        {
            $sql.=" ".strtoupper($_GET["sortorder"]);
        }
        $sql.=", ";
		// Remove from default sort order the choosed order
        $tabsqlsort[$_GET["id"]]=eregi_replace($_GET["sortfield"].' '.$_GET["sortorder"].',','',$tabsqlsort[$_GET["id"]]);
        $tabsqlsort[$_GET["id"]]=eregi_replace($_GET["sortfield"].',','',$tabsqlsort[$_GET["id"]]);
    }
    else {
        $sql.=" ORDER BY ";   
    }
    $sql.=$tabsqlsort[$_GET["id"]];
	//print $sql;
    
    $fieldlist=split(',',$tabfield[$_GET["id"]]);

    print '<form action="dict.php" method="post">';
    print '<table class="noborder" width="100%">';

    // Ligne d'ajout
    if ($tabname[$_GET["id"]])
    {
        $alabelisused=0;
        $var=false;
        
        $fieldlist=split(',',$tabfield[$_GET["id"]]);
//        print '<table class="noborder" width="100%">';

        // Ligne de titre d'ajout
        print '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value)
        {
            // Determine le nom du champ par rapport aux noms possibles
            // dans les dictionnaires de données
            $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
            if ($fieldlist[$field]=='source')          { $valuetoshow=$langs->trans("Contact"); }
            if ($fieldlist[$field]=='price')           { $valuetoshow=$langs->trans("PriceUHT"); }
            if ($fieldlist[$field]=='organization')    { $valuetoshow=$langs->trans("Organization"); }
            if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
            if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
            if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
            if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label")."*"; } 
            if ($fieldlist[$field]=='libelle_facture') { $valuetoshow=$langs->trans("LabelOnDocuments")."*"; } 
            if ($fieldlist[$field]=='pays')            { $valuetoshow=$langs->trans("Country"); }
            if ($fieldlist[$field]=='recuperableonly') { $valuetoshow=MENTION_NPR; }
            if ($fieldlist[$field]=='nbjour')          { $valuetoshow=$langs->trans("NbOfDays"); }
            if ($fieldlist[$field]=='fdm')             { $valuetoshow=$langs->trans("AtEndOfMonth"); }
            if ($fieldlist[$field]=='decalage')        { $valuetoshow=$langs->trans("Offset"); }
            if ($fieldlist[$field]=='width')           { $valuetoshow=$langs->trans("Width"); }
            if ($fieldlist[$field]=='height')          { $valuetoshow=$langs->trans("Height"); }
            if ($fieldlist[$field]=='unit')            { $valuetoshow=$langs->trans("MeasuringUnit"); }
            if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='pays_id') { $valuetoshow=''; }

            if ($valuetoshow != '')
            {
            	print '<td>';
            	print $valuetoshow;
            	print '</td>';
            }

            if ($fieldlist[$field]=='libelle') $alabelisused=1; 
        }
        print '<td colspan="3">&nbsp;</td>';
        print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
        print '</tr>';

        // Ligne d'ajout
        print "<tr ".$bc[$var].">";

        fieldList($fieldlist);
        
        print '<td colspan="3"><input type="submit" class="button" name="actionadd" value="'.$langs->trans("Add").'"></td>';
        print "</tr>";

        if ($alabelisused)  // Si un des champs est un libellé
        {
            print '<tr><td colspan="'.(count($fieldlist)+2).'">* '.$langs->trans("LabelUsedByDefault").'.</td></tr>';
        }
        print '<tr><td colspan="'.(count($fieldlist)+2).'">&nbsp;</td></tr>';
    }

    // Affiche table des valeurs
    $resql=$db->query($sql);
	if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;
        $var=true;
        if ($num)
        {
            // Ligne de titre
            print '<tr class="liste_titre">';
            foreach ($fieldlist as $field => $value) {
                // Determine le nom du champ par rapport aux noms possibles
                // dans les dictionnaires de données
                $showfield=1;							  	// Par defaut
				        $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
                if ($fieldlist[$field]=='source')          { $valuetoshow=$langs->trans("Contact"); }
                if ($fieldlist[$field]=='price')           { $valuetoshow=$langs->trans("PriceUHT"); }
                if ($fieldlist[$field]=='organization')    { $valuetoshow=$langs->trans("Organization"); }
                if ($fieldlist[$field]=='lang')            { $valuetoshow=$langs->trans("Language"); }
                if ($fieldlist[$field]=='type')            { $valuetoshow=$langs->trans("Type"); }
                if ($fieldlist[$field]=='code')            { $valuetoshow=$langs->trans("Code"); }
                if ($fieldlist[$field]=='libelle' || $fieldlist[$field]=='label') { $valuetoshow=$langs->trans("Label")."*";  }
                if ($fieldlist[$field]=='libelle_facture') { $valuetoshow=$langs->trans("LabelOnDocuments")."*"; }
                if ($fieldlist[$field]=='pays')            { $valuetoshow=$langs->trans("Country"); }
                if ($fieldlist[$field]=='recuperableonly') { $valuetoshow=MENTION_NPR; }
                if ($fieldlist[$field]=='nbjour')          { $valuetoshow=$langs->trans("NbOfDays"); }
                if ($fieldlist[$field]=='fdm')             { $valuetoshow=$langs->trans("AtEndOfMonth"); }
                if ($fieldlist[$field]=='decalage')        { $valuetoshow=$langs->trans("Offset"); }
                if ($fieldlist[$field]=='width')           { $valuetoshow=$langs->trans("Width"); }
                if ($fieldlist[$field]=='height')          { $valuetoshow=$langs->trans("Height"); }
                if ($fieldlist[$field]=='unit')            { $valuetoshow=$langs->trans("MeasuringUnit"); }
                if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='pays_id') { $showfield=0; }
                
                // Affiche nom du champ
                if ($showfield)
                {
                	print_liste_field_titre($valuetoshow,"dict.php",$fieldlist[$field],"&id=".$_GET["id"],"","",$sortfield,$sortorder);
                }
            }
            print_liste_field_titre($langs->trans("Action"),"dict.php","active","&id=".$_GET["id"],"",'align="center"',$sortfield,$sortorder);
            print '<td colspan="2">&nbsp;</td>';
            print '</tr>';

            // Lignes de valeurs
            while ($i < $num)
            {
                $obj = $db->fetch_object($resql);
                $var=!$var;
				//print_r($obj);
                print "<tr $bc[$var]>";
                if ($_GET["action"] == 'modify' && ($_GET["rowid"] == ($obj->rowid?$obj->rowid:$obj->code)))
                {
                	print '<form action="dict.php" method="post">';
                	print '<input type="hidden" name="id" value="'.$_GET["id"].'">';
                	print '<input type="hidden" name="rowid" value="'.$_GET["rowid"].'">';
                	fieldList($fieldlist,$obj);
                	print '<td colspan="3" align="right"><a name="'.($obj->rowid?$obj->rowid:$obj->code).'">&nbsp;</a><input type="submit" class="button" name="actionmodify" value="'.$langs->trans("Modify").'">';
                	print '&nbsp;<input type="submit" class="button" name="actioncancel" value="'.$langs->trans("Cancel").'"></td>';
                }
                else
                {                	
                	foreach ($fieldlist as $field => $value)
                	{
                		$showfield=1;
                		$valuetoshow=$obj->$fieldlist[$field];

	                    if ($valuetoshow=='all') {
	                    	$valuetoshow=$langs->trans('All');
	                    }
	                    if ($fieldlist[$field]=='recuperableonly' || $fieldlist[$field]=='fdm') {
	                      $valuetoshow=yn($valuetoshow);
	                    }
	                    if ($fieldlist[$field]=='price') {
	                      $valuetoshow=price($valuetoshow);
	                    }
	                    if ($fieldlist[$field]=='region_id' || $fieldlist[$field]=='pays_id') {
	                      $showfield=0;
	                    }
						          if ($showfield) print '<td>'.$valuetoshow.'</td>';
					        }

                  print '<td align="center">';
                  // Est-ce une entrée du dictionnaire qui peut etre désactivée ?
                  $iserasable=1;  // Oui par defaut
                  if (isset($obj->code) && ($obj->code == '0' || $obj->code == '' || eregi('unknown',$obj->code))) $iserasable=0;
                  if ($obj->type && $obj->type == 'system') $iserasable=0;

                  if ($iserasable) {
                  	print '<a href="'."dict.php".'?sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
                  } else {
                  	print $langs->trans("AlwaysActive");
                  }
                  print "</td>";
				  
                  if ($iserasable) {
                  	print '<td align="center"><a href="dict.php?sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action=modify#'.($obj->rowid?$obj->rowid:$obj->code).'">'.img_edit().'</a></td>';
                  } else {
                    print '<td>&nbsp;</td>';   
                  }
                  if ($iserasable) {
                    print '<td align="center"><a href="dict.php?sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.($obj->rowid?$obj->rowid:$obj->code).'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action=delete">'.img_delete().'</a></td>';
                  } else {
                    print '<td>&nbsp;</td>';   
                  }
                  print "</tr>\n";
                }
                $i++;
              }
          }
      }
      else {
        dolibarr_print_error($db);
    }

    print '</table>';

    print '</form>';
}
else
{
    /*
     * Affichage de la liste des dictionnaires
     */

    $var=true;
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td>'.$langs->trans("Dictionnary").'</td><td>'.$langs->trans("Table").'</td></tr>';

    foreach ($taborder as $i)
    {
        if ($tabname[$i] && ! $tabcond[$i]) continue;	// If dictionnary and condition not true
        
        $var=!$var;
        if ($i)
        {
            $value=$tabname[$i];
            print '<tr '.$bc[$var].'><td width="30%"><a href="dict.php?id='.$i.'">'.$tablib[$i].'</a></td><td>'.$tabname[$i].'</td></tr>';

        }
        else
        {
            print '<tr '.$bc[$var].'><td width="30%">&nbsp;</td><td>&nbsp;</td></tr>';
        }
    }
    print '</table>';
}

print '<br>';

$db->close();

llxFooter('$Date$ - $Revision$');

/**
        \brief      Affiche les champs
*/
function fieldList($fieldlist,$obj='')
{
	global $conf,$langs,$db;
	global $region_id,$pays_id;

	$html = new Form($db);

	foreach ($fieldlist as $field => $value)
  {
  	if ($fieldlist[$field] == 'pays') {
  		print '<td>';
      $html->select_pays($pays_id,'pays');
      print '</td>';
    }
    elseif ($fieldlist[$field] == 'pays_id') {
    	$pays_id = $obj->$fieldlist[$field]?$obj->$fieldlist[$field]:0;
    	print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$pays_id.'">';
    }
    elseif ($fieldlist[$field] == 'region') {
    	print '<td>';
      $html->select_region($region_id,'region');
      print '</td>';
    }
    elseif ($fieldlist[$field] == 'region_id') {
    	$region_id = $obj->$fieldlist[$field]?$obj->$fieldlist[$field]:0;
    	print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$region_id.'">';
    }
    elseif ($fieldlist[$field] == 'lang') {
    	print '<td>';
      $html->select_lang($conf->global->MAIN_LANG_DEFAULT,'lang');
      print '</td>';
    }
    // Le type de l'element (pour les type de contact).'
    elseif ($fieldlist[$field] == 'element')
    {
    	$langs->load("orders");
      $langs->load("contracts");
      $langs->load("project");
      $langs->load("propal");
      $langs->load("bills");
      print '<td>';
      $elementList = array("commande"=>$langs->trans("Order"),
                           "contrat"=>$langs->trans("Contract"),
                           "projet"=>$langs->trans("Project"),
                           "propal"=>$langs->trans("Propal"),
                           "facture"=>$langs->trans("Bill"));
      $html->select_array('element', $elementList,$obj->$fieldlist[$field]);
      print '</td>';
    }
    // La source de l'element (pour les type de contact).'
    elseif ($fieldlist[$field] == 'source')
    {
    	print '<td>';
      $elementList = array("internal"=>$langs->trans("Internal"),
                           "external"=>$langs->trans("External"));
      $html->select_array('source', $elementList,$obj->$fieldlist[$field]);
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
      print $html->selectyesno($fieldlist[$field],$obj->$fieldlist[$field],1);
      print '</td>';
    }
    elseif ($fieldlist[$field] == 'nbjour' || $fieldlist[$field] == 'decalage' || $fieldlist[$field] == 'taux') {
    	print '<td><input type="text" class="flat" value="'.$obj->$fieldlist[$field].'" size="3" name="'.$fieldlist[$field].'"></td>';
    }
    elseif ($fieldlist[$field] == 'price') {
    	print '<td><input type="text" class="flat" value="'.price($obj->$fieldlist[$field]).'" size="8" name="'.$fieldlist[$field].'"></td>';
    }
    elseif ($fieldlist[$field] == 'code') {
    	print '<td><input type="text" class="flat" value="'.$obj->$fieldlist[$field].'" size="10" name="'.$fieldlist[$field].'"></td>';
    }
    elseif ($fieldlist[$field]=='unit') {
	    print '<td>';
	    $html->select_array('unit',array('mm','cm','point','inch'),$obj->$fieldlist[$field],0,0,1);
	    print '</td>';
	  }
    else
    {
    	print '<td><input type="text" class="flat" value="'.$obj->$fieldlist[$field].'" name="'.$fieldlist[$field].'" ></td>';
    }
  }
}

?>
