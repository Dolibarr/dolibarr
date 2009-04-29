<?php
/* Copyright (c) 2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/html.formfile.class.php
 *	\brief      Fichier de la classe des fonctions pr�d�finie de composants html fichiers
 *	\version	$Id$
 */


/**
 *	\class      FormFile
 *	\brief      Classe permettant la g�n�ration de composants html fichiers
 */
class FormFile
{
	var $db;
	var $error;


	/**
	 *		\brief     Constructeur
	 *		\param     DB      handler d'acc�s base de donn�e
	 */
	function FormFile($DB)
	{
		$this->db = $DB;

		return 1;
	}


	/**
	 *    	\brief      Show file add form
	 *    	\param      url				Url
	 *    	\param      titre			Titre zone
	 *    	\param      addcancel		1=Ajoute un bouton 'Annuler'
	 *		\param		sectionid		If upload must be done inside a particular ECM section
	 * 		\param		perm			Value of permission ot allow upload
	 * 		\return		int				<0 si ko, >0 si ok
	 */
	function form_attach_new_file($url,$titre='',$addcancel=0, $sectionid=0, $perm=1)
	{
		global $conf,$langs;

		print "\n\n<!-- Start form attach new file -->\n";

		if (! $titre) $titre=$langs->trans("AttachANewFile");
		print_titre($titre);

		print '<form name="userfile" action="'.$url.'" enctype="multipart/form-data" method="POST">';
		print '<input type="hidden" name="section" value="'.$sectionid.'">';

		print '<table width="100%" class="noborder">';
		print '<tr><td width="50%" valign="top">';

		$max=$conf->global->MAIN_UPLOAD_DOC;		// En Kb
		$maxphp=@ini_get('upload_max_filesize');	// En inconnu
		if (eregi('m$',$maxphp)) $maxphp=$maxphp*1024;
		if (eregi('k$',$maxphp)) $maxphp=$maxphp;
		// Now $max and $maxphp are in Kb
		if ($maxphp > 0) $max=min($max,$maxphp);

		if ($max > 0)
		{
			print '<input type="hidden" name="max_file_size" value="'.($max*1024).'">';
		}
		print '<input class="flat" type="file" name="userfile" size="70"';
		print (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled="true"':'');
		print '>';
		print ' &nbsp; ';
		print '<input type="submit" class="button" name="sendit" value="'.$langs->trans("Upload").'"';
		print (empty($conf->global->MAIN_UPLOAD_DOC) || empty($perm)?' disabled="true"':'');
		print '>';

		if ($addcancel)
		{
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
		}

		if (! empty($conf->global->MAIN_UPLOAD_DOC))
		{
			if ($perm)
			{
				print ' ('.$langs->trans("MaxSize").': '.$max.' '.$langs->trans("Kb");
				print ' '.info_admin($langs->trans("ThisLimitIsDefinedInSetup",$max,$maxphp),1);
				print ')';
			}
		}
		else
		{
			print ' ('.$langs->trans("UploadDisabled").')';
		}
		print "</td></tr>";
		print "</table>";

		print '</form>';
		if (empty($sectionid)) print '<br>';

		print "\n<!-- End form attach new file -->\n\n";

		return 1;
	}


	/**
	 *      \brief      Affiche la cartouche de la liste des documents d'une propale, facture...
	 *      \param      modulepart          propal=propal, facture=facture, ...
	 *      \param      filename            Sub dir to scan (vide si filedir deja complet)
	 *      \param      filedir             Dir to scan
	 *      \param      urlsource           Url of origin page (for return)
	 *      \param      genallowed          Generation is allowed (1/0 or array of formats)
	 *      \param      delallowed          Remove is allowed (1/0)
	 *      \param      modelselected       Model to preselect by default
	 *      \param      modelliste			Tableau des modeles possibles. Use '' to hide combo select list.
	 *      \param      forcenomultilang	N'affiche pas option langue meme si MAIN_MULTILANGS defini
	 *      \param      iconPDF             N'affiche que l'icone PDF avec le lien (1/0)
	 * 		\param		maxfilenamelength	Max length for filename shown
	 * 		\param		noform				Do not output html form start and end
	 * 		\param		param				More param on http links
	 * 		\param		title				Title to show on top of form
	 * 		\param		buttonlabel			Label on submit button
	 *      \remarks    Le fichier de facture detaillee est de la forme
	 *                  REFFACTURE-XXXXXX-detail.pdf ou XXXXX est une forme diverse
	 *		\return		int					<0 si ko, nbre de fichiers affiches si ok
	 */
	function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0,$iconPDF=0,$maxfilenamelength=28,$noform=0,$param='',$title='',$buttonlabel='')
	{
		// filedir = conf->...dir_ouput."/".get_exdir(id)
		include_once(DOL_DOCUMENT_ROOT.'/lib/files.lib.php');

		global $langs,$bc,$conf;
		$var=true;

		if ($iconPDF == 1)
		{
			$genallowed = '';
			$delallowed = 0;
			$modelselected = '';
			$modelliste = '';
			$forcenomultilang=0;
		}

		$filename = dol_sanitizeFileName($filename);
		$headershown=0;
		$i=0;

		print "\n".'<!-- Start show_document -->'."\n";
		//print 'filedir='.$filedir;

		// Affiche en-tete tableau
		if ($genallowed)
		{
			$modellist=array();
			if ($modulepart == 'propal')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/propale/modules_propale.php');
					$model=new ModelePDFPropales();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'commande')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/commande/modules_commande.php');
					$model=new ModelePDFCommandes();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'expedition')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/expedition/pdf/ModelePdfExpedition.class.php');
					$model=new ModelePDFExpedition();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'livraison')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/livraison/modules_livraison.php');
					$model=new ModelePDFDeliveryOrder();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'ficheinter')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/fichinter/modules_fichinter.php');
					$model=new ModelePDFFicheinter();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'facture')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/facture/modules_facture.php');
					$model=new ModelePDFFactures();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			elseif ($modulepart == 'export')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/export/modules_export.php');
					$model=new ModeleExports();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'commande_fournisseur')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/supplier_order/modules_commandefournisseur.php');
					$model=new ModelePDFSuppliersOrders();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'facture_fournisseur')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/fourn/facture/modules/modules_facturefournisseur.php');
					$model=new ModelePDFFacturesSuppliers();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'remisecheque')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					// ??
				}
			}
			elseif ($modulepart == 'donation')
			{
				if (is_array($genallowed)) $modellist=$genallowed;
				else
				{
					include_once(DOL_DOCUMENT_ROOT.'/includes/modules/dons/modules_don.php');
					$model=new ModeleDon();
					$modellist=$model->liste_modeles($this->db);
				}
			}
			else if ($modulepart == 'unpayed')
			{
				$modellist='';
			}
			else
			{
				dol_print_error($this->db,'Bad value for modulepart');
				return -1;
			}

			$headershown=1;

			$html = new Form($db);
			$buttonlabeltoshow=$buttonlabel;
			if (empty($buttonlabel)) $buttonlabel=$langs->trans('Generate');

			if (empty($noform)) print '<form action="'.$urlsource.'#builddoc" method="post">';
			print '<input type="hidden" name="action" value="builddoc">';

			print_titre($langs->trans("BuildDocuments"));
			print '<table class="border" width="100%">';

			print '<tr '.$bc[$var].'>';
			if (! empty($modellist))
			{
				print '<td align="center">';
				print $langs->trans('Model').' ';
				$html->select_array('model',$modellist,$modelselected,0,0,1);
			}
			else
			{
				print '<td align="left">';
				print $langs->trans("Files");
			}
			print '</td>';
			print '<td align="center">';
			if($conf->global->MAIN_MULTILANGS && ! $forcenomultilang)
			{
				include_once(DOL_DOCUMENT_ROOT.'/html.formadmin.class.php');
				$formadmin=new FormAdmin($this->db);
				$formadmin->select_lang($langs->getDefaultLang());
			}
			else
			{
				print '&nbsp;';
			}
			print '</td>';
			print '<td align="center" colspan="'.($delallowed?'2':'1').'">';
			print '<input class="button" type="submit" value="'.$buttonlabel.'">';
			print '</td></tr>';
		}

		// Recupe liste des fichiers
		$png = '';
		$filter = '';
		if ($iconPDF==1)
		{
			$png = '|\.png$';
			$filter = $filename.'.pdf';
		}
		$file_list=dol_dir_list($filedir,'files',0,$filter,'\.meta$'.$png,'date',SORT_DESC);

		// Affiche en-tete tableau si non deja affiche
		if (sizeof($file_list) && ! $headershown && !$iconPDF)
		{
			$headershown=1;
			$titletoshow=$langs->trans("Documents");
			if (! empty($title)) $titletoshow=$title;
			print_titre($titletoshow);
			print '<table class="border" width="100%">';
		}

		// Boucle sur chaque ligne trouvee
		foreach($file_list as $i => $file)
		{
			$var=!$var;

			// Defini chemin relatif par rapport au module pour lien download
			$relativepath=$file["name"];								// Cas general
			if ($filename) $relativepath=$filename."/".$file["name"];	// Cas propal, facture...
			// Autre cas
			if ($modulepart == 'donation')   { $relativepath = get_exdir($filename,2).$file["name"]; }
			if ($modulepart == 'export')     { $relativepath = $file["name"]; }

			if (!$iconPDF) print "<tr ".$bc[$var].">";

			// Affiche nom fichier avec lien download
			if (!$iconPDF) print '<td>';
			print '<a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">';
			if (!$iconPDF)
			{
				print img_mime($file["name"],$langs->trans("File").': '.$file["name"]).' '.dol_trunc($file["name"],$maxfilenamelength);
			}
			else
			{
				print img_pdf($file["name"],2);
			}
			print '</a>';
			if (!$iconPDF) print '</td>';
			// Affiche taille fichier
			if (!$iconPDF) print '<td align="right">'.filesize($filedir."/".$file["name"]). ' bytes</td>';
			// Affiche date fichier
			if (!$iconPDF) print '<td align="right">'.dol_print_date(filemtime($filedir."/".$file["name"]),'dayhour').'</td>';

			if ($delallowed)
			{
				print '<td align="right"><a href="'.DOL_URL_ROOT.'/document.php?action=remove_file&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath);
				print ($param?'&amp;'.$param:'');
				print '&amp;urlsource='.urlencode($urlsource);
				print '">'.img_delete().'</a></td>';
			}

			if (!$iconPDF) print '</tr>';

			$i++;
		}


		if ($headershown)
		{
			// Affiche pied du tableau
			print "</table>\n";
			if ($genallowed)
			{
				if (empty($noform)) print '</form>'."\n";
			}
		}
		print '<!-- End show_document -->'."\n";
		return ($i?$i:$headershown);
	}


	/**
	 *      \brief      Show list of documents in a directory
	 *      \param      filearray			Array of files loaded by dol_dir_list function
	 * 		\param		object				Object on which document is linked to
	 * 		\param		modulepart			Value for modulepart used by download wrapper
	 * 		\param		param				Parameters on sort links
	 * 		\param		forcedownload		Mime type is forced to 'application/binary' to have a download
	 * 		\param		relativepath		Relative path of docs (autodefined if not provided)
	 * 		\param		permtodelete		Permission to delete
	 * 		\param		useinecm			Change output for use in ecm module
	 * 		\param		textifempty			Text to show if filearray is empty
	 * 		\return		int					<0 if KO, nb of files shown if OK
	 */
	function list_of_documents($filearray,$object,$modulepart,$param,$forcedownload=0,$relativepath='',$permtodelete=1,$useinecm=0,$textifempty='')
	{
		global $user, $conf, $langs;
		global $bc;
		global $sortfield, $sortorder;

		// Affiche liste des documents existant
		if (empty($useinecm)) print_titre($langs->trans("AttachedFiles"));
		else { $bc[true]=''; $bc[false]=''; };
		$url=$_SERVER["PHP_SELF"];
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Documents2"),$_SERVER["PHP_SELF"],"name","",$param,'align="left"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Size"),$_SERVER["PHP_SELF"],"size","",$param,'align="right"',$sortfield,$sortorder);
		print_liste_field_titre($langs->trans("Date"),$_SERVER["PHP_SELF"],"date","",$param,'align="center"',$sortfield,$sortorder);
		print '<td>&nbsp;</td>';
		print '</tr>';

		$var=true;
		foreach($filearray as $key => $file)
		{
			if (!is_dir($dir.$file['name'])
			&& $file['name'] != '.'
			&& $file['name'] != '..'
			&& $file['name'] != 'CVS'
			&& ! eregi('\.meta$',$file['name']))
			{
				// Define relative path used to store the file
				if (! $relativepath)
				{
					$relativepath=dol_sanitizeFileName($object->ref).'/';
					if ($modulepart == 'facture_fournisseur')	$relativepath=get_exdir($object->id,2).$relativepath;
				}

				$var=!$var;
				print "<tr $bc[$var]><td>";
				print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart;
				if ($forcedownload) print '&type=application/binary';
				print '&file='.urlencode($relativepath.$file['name']).'">';
				print img_mime($file['name']).' ';
				print $file['name'];
				print '</a>';
				print "</td>\n";
				print '<td align="right">'.dol_print_size($file['size']).'</td>';
				print '<td align="center">'.dol_print_date($file['date'],"dayhour").'</td>';
				print '<td align="right">';
				//print '&nbsp;';
				if ($permtodelete)
				print '<a href="'.$url.'?id='.$object->id.'&amp;section='.$_REQUEST["section"].'&amp;action=delete&urlfile='.urlencode($file['name']).'">'.img_delete().'</a>';
				else
				print '&nbsp;';
				print "</td></tr>\n";
			}
		}
		if (sizeof($filearray) == 0)
		{
			print '<tr '.$bc[$var].'><td colspan="4">';
			if (empty($textifempty)) print $langs->trans("NoFileFound");
			else print $textifempty;
			print '</td></tr>';
		}
		print "</table>";
		// Fin de zone

	}

}

?>
