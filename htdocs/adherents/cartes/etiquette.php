<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file 		htdocs/adherents/cartes/etiquette.php
 *	\ingroup    member
 *	\brief      Page to output members labels sheets
 *	\version    $Id$
 */
require("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");
require_once(DOL_DOCUMENT_ROOT."/adherents/class/adherent.class.php");
require_once(DOL_DOCUMENT_ROOT.'/includes/modules/member/labels/modules_labels.php');

//if (empty($conf->global->ADHERENT_ETIQUETTE_TYPE)) $conf->global->ADHERENT_ETIQUETTE_TYPE='L7163';
//$pdf = new PDF_card($conf->global->ADHERENT_ETIQUETTE_TYPE, 1, 1);

// Choix de l'annee d'impression ou annee courante.
$now = dol_now();
$year=dol_print_date($now,'%Y');
$month=dol_print_date($now,'%m');
$day=dol_print_date($now,'%d');


$arrayofmembers=array();

// requete en prenant que les adherents a jour de cotisation
$sql = "SELECT d.rowid, d.prenom, d.nom, d.login, d.societe, d.datefin,";
$sql.= " d.adresse, d.cp, d.ville, d.naiss, d.email, d.photo,";
$sql.= " t.libelle as type,";
$sql.= " p.libelle as pays";
$sql.= " FROM ".MAIN_DB_PREFIX."adherent_type as t, ".MAIN_DB_PREFIX."adherent as d";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_pays as p ON d.pays = p.rowid";
$sql.= " WHERE d.fk_adherent_type = t.rowid AND d.statut = 1";
$sql.= " ORDER BY d.rowid ASC";

$result = $db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;
	while ($i < $num)
	{
        $objp = $db->fetch_object($result);

        if ($objp->pays == '-') $objp->pays='';

        // List of values to scan for a replacement
        $substitutionarray = array (
        '%PRENOM%'=>$objp->prenom,
        '%NOM%'=>$objp->nom,
        '%LOGIN%'=>$objp->login,
        '%SERVEUR%'=>"http://".$_SERVER["SERVER_NAME"]."/",
        '%SOCIETE%'=>$objp->societe,
        '%ADRESSE%'=>$objp->adresse,
        '%CP%'=>$objp->cp,
        '%VILLE%'=>$objp->ville,
        '%PAYS%'=>$objp->pays,
        '%EMAIL%'=>$objp->email,
        '%NAISS%'=>$objp->naiss,
        '%TYPE%'=>$objp->type,
        '%ID%'=>$objp->rowid,
        '%ANNEE%'=>$year,    // For backward compatibility
        '%YEAR%'=>$year,
        '%MONTH%'=>$month,
        '%DAY%'=>$day
        );

        $conf->global->ADHERENT_ETIQUETTE_TEXT="%PRENOM% %NOM%\n%ADRESSE%\n%CP% %VILLE%\n%PAYS%";
        $textleft=make_substitutions($conf->global->ADHERENT_ETIQUETTE_TEXT, $substitutionarray, $langs);
        $textheader='';
        $textfooter='';
        $textright='';

        $arrayofmembers[]=array('textleft'=>$textleft,
                                'textheader'=>$textheader,
                                'textfooter'=>$textfooter,
                                'textright'=>$textright,
                                'id'=>$objp->rowid,
                                'photo'=>$objp->photo);

        $i++;

//		$message=sprintf("%s\n%s\n%s %s\n%s", $objp->prenom." ".$objp->nom, $objp->adresse, $objp->cp, $objp->ville, $objp->pays);
//		$pdf->Add_PDF_card($message,'','',$langs);
	}

    // Build and output PDF
    $result=members_label_pdf_create($db, $arrayofmembers, '', $outputlangs);
    if ($result <= 0)
    {
        dol_print_error($db,$result);
        exit;
    }

    /*
	$pdf->Output($file,'F');

	if (! empty($conf->global->MAIN_UMASK))
		@chmod($file, octdec($conf->global->MAIN_UMASK));

	$db->close();

	clearstatcache();

	$attachment=true;
	if (! empty($conf->global->MAIN_DISABLE_FORCE_SAVEAS)) $attachment=false;
	$filename='tmplabels.pdf';
	$type=dol_mimetype($filename);

	if ($encoding)   header('Content-Encoding: '.$encoding);
	if ($type)       header('Content-Type: '.$type);
	if ($attachment) header('Content-Disposition: attachment; filename="'.$filename.'"');
	else header('Content-Disposition: inline; filename="'.$filename.'"');

	// Ajout directives pour resoudre bug IE
	header('Cache-Control: Public, must-revalidate');
	header('Pragma: public');

	readfile($file);
	*/
}
else
{
	dol_print_error($db);

	llxFooter('$Date$ - $Revision$');
}

?>
