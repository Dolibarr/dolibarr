<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
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
 * $Source$
 */

/*!	    \file       htdocs/admin/dict.php
		\ingroup    setup
		\brief      Page d'administration des dictionnaires de données
		\version    $Revision$
*/

require("./pre.inc.php");

$langs->load("main");
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
$taborder=array(4,3,2,0,1,0,5,0,6,0,7);

// Nom des tables des dictionnaires
$tabname[1] = MAIN_DB_PREFIX."c_forme_juridique";
$tabname[2] = MAIN_DB_PREFIX."c_departements";
$tabname[3] = MAIN_DB_PREFIX."c_regions";
$tabname[4] = MAIN_DB_PREFIX."c_pays";
$tabname[5] = MAIN_DB_PREFIX."c_civilite";
$tabname[6] = MAIN_DB_PREFIX."c_actioncomm";
$tabname[7] = MAIN_DB_PREFIX."c_chargesociales";

// Libellé des dictionnaires
$tablib[1] = $langs->trans("DictionnaryCompanyType");
$tablib[2] = $langs->trans("DictionnaryCanton");
$tablib[3] = $langs->trans("DictionnaryRegion");
$tablib[4] = $langs->trans("DictionnaryCountry");
$tablib[5] = $langs->trans("DictionnaryCivility");
$tablib[6] = $langs->trans("DictionnaryActions");
$tablib[7] = $langs->trans("DictionnarySocialContributions");

// Requete pour extraction des données des dictionnaires
$tabsql[1] = "SELECT f.rowid as rowid, f.code, f.libelle, p.libelle as pays, f.active FROM llx_c_forme_juridique as f, llx_c_pays as p WHERE f.fk_pays=p.rowid";
$tabsql[2] = "SELECT d.rowid as rowid, d.code_departement as code , d.nom as libelle, r.nom as region, p.libelle as pays, d.active FROM llx_c_departements as d, llx_c_regions as r, llx_c_pays as p WHERE d.fk_region=r.code_region and r.fk_pays=p.rowid and r.active=1 and p.active=1";
$tabsql[3] = "SELECT r.rowid as rowid, code_region as code , nom as libelle, p.libelle as pays, r.active FROM llx_c_regions as r, llx_c_pays as p WHERE r.fk_pays=p.rowid and p.active=1";
$tabsql[4] = "SELECT rowid   as rowid, code, libelle, active FROM llx_c_pays";
$tabsql[5] = "SELECT c.rowid as rowid, c.code as code, c.civilite AS libelle, c.active FROM llx_c_civilite AS c";
$tabsql[6] = "SELECT a.id    as rowid, a.code as code, a.libelle AS libelle, a.type, a.active FROM llx_c_actioncomm AS a";
$tabsql[7] = "SELECT a.id    as rowid, a.id as code, a.libelle AS libelle, a.deductible, a.active FROM llx_c_chargesociales AS a";

// Tri par defaut
$tabsqlsort[1]="pays, code ASC";
$tabsqlsort[2]="pays, code ASC";
$tabsqlsort[3]="pays, code ASC";
$tabsqlsort[4]="libelle ASC";
$tabsqlsort[5]="c.libelle ASC";
$tabsqlsort[6]="a.type ASC, a.code ASC";
$tabsqlsort[7]="a.libelle ASC";
 
// Nom des champs en resultat de select pour affichage du dictionnaire
$tabfield[1] = "code,libelle,pays";
$tabfield[2] = "code,libelle,region,pays";   // "code,libelle,region,pays"
$tabfield[3] = "code,libelle,pays";
$tabfield[4] = "code,libelle";
$tabfield[5] = "code,libelle";
$tabfield[6] = "code,libelle,type";
$tabfield[7] = "libelle,deductible";

// Nom des champs dans la table pour insertion d'un enregistrement
$tabfieldinsert[1] = "code,libelle,fk_pays";
$tabfieldinsert[2] = "code_departement,nom,fk_region";
$tabfieldinsert[3] = "code_region,nom,fk_pays";
$tabfieldinsert[4] = "code,libelle";
$tabfieldinsert[5] = "code,civilite";
$tabfieldinsert[6] = "code,libelle,type";
$tabfieldinsert[7] = "libelle,deductible";

// Nom du rowid si le champ n'est pas de type autoincrément
$tabrowid[1] = "";
$tabrowid[2] = "";
$tabrowid[3] = "";
$tabrowid[4] = "rowid";
$tabrowid[5] = "rowid";
$tabrowid[6] = "id";
$tabrowid[7] = "id";


$msg='';


$sortfield=$_GET["sortfield"];

/*
 * Actions ajout d'une entrée dans un dictionnaire de donnée
 */
if ($_POST["actionadd"]) {
    
    $listfield=split(',',$tabfield[$_POST["id"]]);

    // Verifie que tous les champs sont renseignés
    $ok=1;
    foreach ($listfield as $f => $value) {
        if (! isset($_POST[$value]) || $_POST[$value]=='') {
            $ok=0;
            $msg.="Le champ '".$listfield[$f]."' n'est pas renseigné.<br>";
        }
    }
    // Autres verif
    if (isset($_POST["code"]) && $_POST["code"]=='0') {
        $ok=0;
        $msg.="Le Code ne peut avoir la valeur 0<br>";
    }
    if (isset($_POST["pays"]) && $_POST["pays"]=='0') {
        $ok=0;
        $msg.="Le Pays n'a pas été choisi<br>";
    }
    
    // Si verif ok, on ajoute la ligne
    if ($ok) {
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
        if ($tabrowid[$_POST["id"]]) $sql.= $tabrowid[$_POST["id"]].",";
        $sql.= $tabfieldinsert[$_POST["id"]];
        $sql.=",active)";
        $sql.= " VALUES(";
        // Ajoute valeur des champs
        if ($tabrowid[$_POST["id"]]) $sql.= $newid.",";
        $i=0;
        foreach ($listfield as $f => $value) {
            if ($i) $sql.=",";
            $sql.="'".$_POST[$value]."'";
            $i++;
        }
        $sql.=",1)";

        $result = $db->query($sql);
        if (!$result)
        {
            if ($db->errno() == $db->ERROR_DUPLICATE) {
                $msg="Une entrée pour cette clé existe déjà<br>";
            }
            else {
                dolibarr_print_error($db);
            }
        }
    }

    $_GET["id"]=$_POST["id"];       // Force affichage dictionnaire en cours d'edition
}

if ($_GET["action"] == 'delete')       // delete
{
    if ($tabrowid[$_GET["id"]]) $rowidcol=$tabrowid[$_GET["id"]];
    else $rowidcol="rowid";

    $sql = "DELETE from ".$tabname[$_GET["id"]]." WHERE $rowidcol=".$_GET["rowid"];

    $result = $db->query($sql);
    if (!$result)
    {
        dolibarr_print_error($db);
    }
}

if ($_GET["action"] == $acts[0])       // activate
{
    if ($tabrowid[$_GET["id"]]) $rowidcol=$tabrowid[$_GET["id"]];
    else $rowidcol="rowid";

    if ($_GET["rowid"] >0) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 1 WHERE $rowidcol=".$_GET["rowid"];
    }
    elseif ($_GET["code"] >0) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 1 WHERE code=".$_GET["code"];
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dolibarr_print_error($db);
    }
}

if ($_GET["action"] == $acts[1])       // disable
{
    if ($tabrowid[$_GET["id"]]) $rowidcol=$tabrowid[$_GET["id"]];
    else $rowidcol="rowid";

    if ($_GET["rowid"] >0) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 0 WHERE $rowidcol=".$_GET["rowid"];
    }
    elseif ($_GET["code"] >0) {
        $sql = "UPDATE ".$tabname[$_GET["id"]]." SET active = 0 WHERE code=".$_GET["code"];
    }

    $result = $db->query($sql);
    if (!$result)
    {
        dolibarr_print_error($db);
    }
}



llxHeader();


/*
 * Affichage d'un dictionnaire particulier
 */
if ($_GET["id"])
{
    print_titre($langs->trans("DictionnarySetup"));
    print '<br>';

    if ($msg) {
        print $msg.'<br>';
    }

    // Complète requete recherche valeurs avec critere de tri
    $sql=$tabsql[$_GET["id"]];
    if ($_GET["sortfield"]) {
        $sql .= " ORDER BY ".$_GET["sortfield"];
        if ($_GET["sortorder"]) {
            $sql.=" ".$_GET["sortorder"];
        }
        $sql.=", ";
    }
    else {
        $sql.=" ORDER BY ";   
    }
    $sql.=$tabsqlsort[$_GET["id"]];
    
    $fieldlist=split(',',$tabfield[$_GET["id"]]);
    print '<table class="noborder" width="100%">';

    // Ligne d'ajout
    if ($tabname[$_GET["id"]]) {
        print_titre($tablib[$_GET["id"]]);
        $var=False;
        $fieldlist=split(',',$tabfield[$_GET["id"]]);
        print '<table class="noborder" width="100%">';

        print '<form action="dict.php" method="post">';
        print '<input type="hidden" name="id" value="'.$_GET["id"].'">';

        // Ligne de titre d'ajout
        print '<tr class="liste_titre">';
        foreach ($fieldlist as $field => $value) {
            // Determine le nom du champ par rapport aux noms possibles
            // dans les dictionnaires de données
            $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
            if ($fieldlist[$field]=='lang')    $valuetoshow=$langs->trans("Language");
            if ($fieldlist[$field]=='type')    $valuetoshow=$langs->trans("Type");
            if ($fieldlist[$field]=='code')    $valuetoshow=$langs->trans("Code");
            if ($fieldlist[$field]=='libelle') $valuetoshow=$langs->trans("Label")."*";
            if ($fieldlist[$field]=='pays')    $valuetoshow=$langs->trans("Country");
            print '<td>';
            print $valuetoshow;
            print '</td>';
        }
        print '<td>&nbsp;</td>';
        print '<td>&nbsp;</td>';
        print '</td>';

        // Ligne d'ajout
        print "<tr $bc[$var] class=\"value\">";
        $html = new Form($db);
        foreach ($fieldlist as $field => $value) {
            if ($fieldlist[$field] == 'pays') {
                print '<td>';
                $html->select_pays('','pays');
                print '</td>';
            }
            elseif ($fieldlist[$field] == 'region') {
                print '<td>';
                $html->select_region('','region');
                print '</td>';
            }
            elseif ($fieldlist[$field] == 'lang') {
                print '<td>';
                $html->select_lang(MAIN_LANG_DEFAULT,'lang');
                print '</td>';
            }
            elseif ($fieldlist[$field] == 'type') {
                print '<td>';
                print 'user<input type="hidden" name="type" value="user">';
                print '</td>';
            }
            else {
                print '<td><input type="text" value="" name="'.$fieldlist[$field].'"></td>';
            }
        }
        print '<td colspan=3><input type="submit" name="actionadd" value="'.$langs->trans("Add").'"></td>';
        print "</tr>";

        print '<tr><td colspan="'.(count($fieldlist)+2).'">* Label used by default if no translation can be found for code.</td></tr>';

        print '</form>';

    }

    // Affiche table des valeurs
    if ($db->query($sql))
    {
        $num = $db->num_rows();
        $i = 0;
        $var=False;
        if ($num)
        {
            // Ligne de titre
            print '<tr class="liste_titre">';
            foreach ($fieldlist as $field => $value) {
                // Determine le nom du champ par rapport aux noms possibles
                // dans les dictionnaires de données
                $valuetoshow=ucfirst($fieldlist[$field]);   // Par defaut
                if ($fieldlist[$field]=='lang')    $valuetoshow=$langs->trans("Language");
                if ($fieldlist[$field]=='type')    $valuetoshow=$langs->trans("Type");
                if ($fieldlist[$field]=='code')    $valuetoshow=$langs->trans("Code");
                if ($fieldlist[$field]=='libelle') $valuetoshow=$langs->trans("Label")."*";
                if ($fieldlist[$field]=='pays')    $valuetoshow=$langs->trans("Country");
                // Affiche nom du champ
                print_liste_field_titre($valuetoshow,"dict.php",$fieldlist[$field],"&id=".$_GET["id"],"","",$sortfield);
            }
            print_liste_field_titre($langs->trans("Activate")."/".$langs->trans("Disable"),"dict.php","active","&id=".$_GET["id"],"","",$sortfield);
            print '<td>&nbsp;</td>';
            print '</tr>';

            // Lignes de valeurs
            while ($i < $num)
            {
                $obj = $db->fetch_object();
                $var=!$var;

                print "<tr $bc[$var] class=\"value\">";

                foreach ($fieldlist as $field => $value) {
                    $valuetoshow=$obj->$fieldlist[$field];
                    if ($valuetoshow=='all') {
                        $valuetoshow=$langs->trans('All');
                    }
                    print '<td>'.$valuetoshow.'</td>';

                }
                print '<td>';

                // Est-ce une entrée du dictionnaire qui peut etre désactivée ?
                $iserasable=1;  // Oui par defaut
                if (isset($obj->code) && ($obj->code == '0' || $obj->code == '')) $iserasable=0;
                if ($obj->type && $obj->type == 'system') $iserasable=0;

                if ($iserasable) {
                    print '<a href="'."dict.php".'?sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
                } else {
                    print $langs->trans("AlwaysActive");
                }
                print "</td>";
                if ($iserasable) {
                    print '<td><a href="dict.php?sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$obj->rowid.'&amp;code='.$obj->code.'&amp;id='.$_GET["id"].'&amp;action=delete"'.img_delete().'</a></td>';
                } else {
                    print '<td>&nbsp;</td>';   
                }
                print "</tr>\n";
                $i++;
            }
        }
    }
    else {
        dolibarr_print_error($db);
    }

    print '</table>';
}
else
{
    /*
     * Affichage de la liste des dictionnaires
     */

    print_titre($langs->trans("DictionnarySetup"));
    print '<br>';

    foreach ($taborder as $i) {
        if ($i) {
            $value=$tabname[$i];
            print '<a href="dict.php?id='.$i.'">'.$tablib[$i].'</a> (Table '.$tabname[$i].')<br>';
        }
        else
        {
            print '<br>';
        }
    }
}

print '<br>';

$db->close();

llxFooter();


?>
