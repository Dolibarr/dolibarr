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
        \file       htdocs/html.formfile.class.php
        \brief      Fichier de la classe des fonctions prédéfinie de composants html fichiers
		\version	$Id$
*/


/**
        \class      FormFile
        \brief      Classe permettant la génération de composants html fichiers
*/
class FormFile
{
	var $db;
	var $error;
	

	/**
	*		\brief     Constructeur
	*		\param     DB      handler d'accès base de donnée
	*/
	function FormFile($DB)
	{
		$this->db = $DB;
		
		return 1;
	}


	/**
	*    	\brief      Affiche formulaire ajout fichier
	*    	\param      url				Url
	*    	\param      titre			Titre zone
	*    	\param      addcancel		1=Ajoute un bouton 'Annuler'
	*		\param		sectionid		If upload must be done inside a particular ECM section
	*		\return		int				<0 si ko, >0 si ok
	*/
	function form_attach_new_file($url,$titre='',$addcancel=0, $sectionid=0)
	{
		global $conf,$langs;
		
		if ($conf->upload != 0)
		{
			print "\n\n<!-- Start form attach new file -->\n";
			
			if (! $titre) $titre=$langs->trans("AttachANewFile");
			print_titre($titre);

			print '<form name="userfile" action="'.$url.'" enctype="multipart/form-data" method="POST">';
			print '<input type="hidden" name="section" value="'.$sectionid.'">';
			
			print '<table width="100%" class="noborder">';
			print '<tr><td width="50%" valign="top">';
			
			$max=$conf->upload;							// En Kb
			$maxphp=@ini_get('upload_max_filesize');	// En inconnu
			if (eregi('m$',$maxphp)) $maxphp=$maxphp*1024;
			if (eregi('k$',$maxphp)) $maxphp=$maxphp;
			// Now $max and $maxphp are in Kb
			if ($maxphp > 0) $max=min($max,$maxphp);
			
			if ($conf->upload > 0)
			{
				print '<input type="hidden" name="max_file_size" value="'.($max*1024).'">';
			}
			print '<input class="flat" type="file" name="userfile" size="70">';
			print ' &nbsp; ';
			print '<input type="submit" class="button" name="sendit" value="'.$langs->trans("Upload").'">';
			
			if ($addcancel)
			{
				print ' &nbsp; ';
				print '<input type="submit" class="button" name="cancel" value="'.$langs->trans("Cancel").'">';
			}
			
			print ' ('.$langs->trans("MaxSize").': '.$max.' '.$langs->trans("Kb");
			print ' '.info_admin($langs->trans("ThisLimitIsDefinedInSetup",$max,$maxphp),1);
			print ')';
			print "</td></tr>";
			print "</table>";

			print '</form>';
			print '<br>';
			
			print "\n<!-- End form attach new file -->\n\n";
		}
		
		return 1;
	}
	
	
    /**
     *      \brief      Affiche la cartouche de la liste des documents d'une propale, facture...
     *      \param      modulepart          propal=propal, facture=facture, ...
     *      \param      filename            Sous rep à scanner (vide si filedir deja complet)
     *      \param      filedir             Repertoire à scanner
     *      \param      urlsource           Url page origine
     *      \param      genallowed          Génération autorisée (1/0 ou array des formats)
     *      \param      delallowed          Suppression autorisée (1/0)
     *      \param      modelselected       Modele à pré-sélectionner par défaut
     *      \param      modelliste			    Tableau des modeles possibles
     *      \param      forcenomultilang	  N'affiche pas option langue meme si MAIN_MULTILANGS défini
     *      \param      iconPDF             N'affiche que l'icone PDF avec le lien (1/0)
     *      \remarks    Le fichier de facture détaillée est de la forme
     *                  REFFACTURE-XXXXXX-detail.pdf ou XXXXX est une forme diverse
     *		\return		int					<0 si ko, nbre de fichiers affichés si ok
     */
    function show_documents($modulepart,$filename,$filedir,$urlsource,$genallowed,$delallowed=0,$modelselected='',$modelliste=array(),$forcenomultilang=0,$iconPDF=0)
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
 
        $filename = sanitize_string($filename);
        $headershown=0;
        $i=0;

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
            	include_once(DOL_DOCUMENT_ROOT.'/expedition/mods/pdf/ModelePdfExpedition.class.php');
            	$model=new ModelePDFExpedition();
            	$modellist=$model->liste_modeles($this->db);
            }
          }
          elseif ($modulepart == 'livraison')
          {
          	if (is_array($genallowed)) $modellist=$genallowed;
          	else
            {
            	include_once(DOL_DOCUMENT_ROOT.'/livraison/mods/modules_livraison.php');
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
		        	include_once(DOL_DOCUMENT_ROOT.'/fourn/commande/modules/modules_commandefournisseur.php');
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
		      else
		      {
		      	dolibarr_print_error($this->db,'Bad value for modulepart');
		      	return -1;
		      }
		      
		      $headershown=1;
		      
		      $html = new Form($db);
	  
	        print '<form action="'.$urlsource.'#builddoc" method="post">';
	        print '<input type="hidden" name="action" value="builddoc">';
	        
	        print_titre($langs->trans("Documents"));
          print '<table class="border" width="100%">';

          print '<tr '.$bc[$var].'>';
          print '<td>'.$langs->trans('Model').'</td>';
          print '<td align="center">';
          $html->select_array('model',$modellist,$modelselected,0,0,1);
          $texte=$langs->trans('Generate');
          print '</td>';
          print '<td align="center">';
          if($conf->global->MAIN_MULTILANGS && ! $forcenomultilang)
          {
            $html->select_lang($langs->getDefaultLang());
          }
          else
          {
          	print '&nbsp;';
          }
          print '</td>';
          print '<td align="center" colspan="'.($delallowed?'2':'1').'">';
          print '<input class="button" type="submit" value="'.$texte.'">';
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
		
        // Affiche en-tete tableau si non deja affiché
		if (sizeof($file_list) && ! $headershown && !$iconPDF)
		{
			$headershown=1;
		  print_titre($langs->trans("Documents"));
		  print '<table class="border" width="100%">';
		}	
		
		// Boucle sur chaque ligne trouvée
		foreach($file_list as $i => $file)
		{
			// Défini chemin relatif par rapport au module pour lien download
	    $relativepath=$file["name"];								// Cas general
	    if ($filename) $relativepath=$filename."/".$file["name"];	// Cas propal, facture...
	    // Autre cas
      if ($modulepart == 'don')        { $relativepath = get_exdir($filename,2).$file["name"]; }
      if ($modulepart == 'export')     { $relativepath = $file["name"]; }
 
      // Défini le type MIME du document
      if (eregi('\.([^\.]+)$',$file["name"],$reg)) $extension=$reg[1];
      $mimetype=strtoupper($extension);
      if ($extension == 'pdf') $mimetype='PDF';
      if ($extension == 'html') $mimetype='HTML';
      if (eregi('\-detail\.pdf',$file["name"])) $mimetype='PDF Détaillé';

      if (!$iconPDF) print "<tr $bc[$var]>";

      // Affiche colonne type MIME
      if (!$iconPDF) print '<td nowrap>'.$mimetype.'</td>';
      // Affiche nom fichier avec lien download
	    if (!$iconPDF) print '<td>';
	    print '<a href="'.DOL_URL_ROOT . '/document.php?modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'">';
	    if (!$iconPDF)
	    {
	    	print $file["name"];
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
      if (!$iconPDF) print '<td align="right">'.dolibarr_print_date(filemtime($filedir."/".$file["name"]),'dayhour').'</td>';

			if ($delallowed)
			{
            	print '<td><a href="'.DOL_URL_ROOT.'/document.php?action=remove_file&amp;modulepart='.$modulepart.'&amp;file='.urlencode($relativepath).'&amp;urlsource='.urlencode($urlsource).'">'.img_delete().'</a></td>';
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
	            print '</form>';
	        }
	    }
	
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
     *		\return		int					<0 if KO, nb of files shown if OK
     */
    function list_of_documents($filearray,$object,$modulepart,$param,$forcedownload=0,$relativepath='',$permtodelete=1)
    {
    	global $user, $conf, $langs;
    	global $bc;
    	global $sortfield, $sortorder;
    	
		// Affiche liste des documents existant
	  	print_titre($langs->trans("AttachedFiles"));
		
		$url=$_SERVER["PHP_SELF"];
		print '<table width="100%" class="noborder">';
		print '<tr class="liste_titre">';
		print_liste_field_titre($langs->trans("Document"),$_SERVER["PHP_SELF"],"name","",$param,'align="left"',$sortfield,$sortorder);
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
					$relativepath=$object->ref.'/';
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
				print '<td align="center">'.dolibarr_print_date($file['date'],"dayhour").'</td>';
				print '<td align="right">';
				//print '&nbsp;'; 
				if ($permtodelete)
					print '<a href="'.$url.'?id='.$object->id.'&amp;section='.$_REQUEST["section"].'&amp;action=delete&urlfile='.urlencode($file['name']).'">'.img_delete().'</a>';
				else
					print '&nbsp;';
				print "</td></tr>\n";
			}
		}
		if (sizeof($filearray) == 0) print '<tr '.$bc[$var].'><td colspan="4">'.$langs->trans("NoFileFound").'</td></tr>';
		print "</table>";
		// Fin de zone

	}

	/**
     *      \brief      Show list of documents in a directory
     *      \param      upload_dir			Dir to scan
     * 		\param		object				Object on which document is linked to
     * 		\param		modulepart			Value for modulepart used by download wrapper
     *		\return		int					<0 if KO, nb of files shown if OK
     */
    function list_of_documents2($upload_dir,$object,$modulepart)
    {
    	global $user, $conf, $langs;
    	global $bc;
    	
		// List of documents
	    $errorlevel=error_reporting();
		error_reporting(0);
		$handle=opendir($upload_dir);
		error_reporting($errorlevel);
	
		// Affiche liste des documents existant
	  	print_titre($langs->trans("AttachedFiles"));
		
	  	print '<table width="100%" class="noborder">';
		
	  	print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Document').'</td>';
		print '<td align="right">'.$langs->trans('Size').'</td>';
		print '<td align="center">'.$langs->trans('Date').'</td>';
		print '<td>&nbsp;</td>';
		print '</tr>';
		$var=true;
		
		if ($handle)
		{
			while (($file = readdir($handle))!==false)
			{
				if (!is_dir($dir.$file) 
					&& $file != '.'
					&& $file != '..'
					&& $file != 'CVS'
					&& ! eregi('\.meta$',$file)
					)
				{
					// Define relative path used to store the file
					$morepath=$object->ref.'/';
					if ($modulepart == 'facture_fournisseur')	$morepath=get_exdir($object->id,2).$morepath;
					
					$var=!$var;
					print '<tr '.$bc[$var].'>';
					print '<td>';
					print img_mime($file).' ';
					print '<a href="'.DOL_URL_ROOT.'/document.php?modulepart='.$modulepart.'&file='.urlencode($morepath.$file).'">'.$file.'</a>';
					print "</td>\n";
					print '<td align="right">'.filesize($upload_dir.'/'.$file). ' '.$langs->trans('bytes').'</td>';
					print '<td align="center">'.dolibarr_print_date(filemtime($upload_dir.'/'.$file),'dayhour').'</td>';
					print '<td align="center">';
					print '<a href="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'&action=delete&urlfile='.urlencode($file).'">'.img_delete($langs->trans('Delete')).'</a>';
					print "</td></tr>\n";
				}
			}
			closedir($handle);
		}
		print '</table>';
	}
}

?>
