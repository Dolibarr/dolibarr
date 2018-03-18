<?php
/* Copyright (C) 2016-2017 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *   	\file       htdocs/collab/index.php
 *		\ingroup    collab
 *		\brief      Page to work on a shared document (PAD)
 */

define('NOSCANPOSTFORINJECTION',1);
define('NOSTYLECHECK',1);

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

$langs->load("admin");
$langs->load("other");
$langs->load("website");

if (! $user->admin) accessforbidden();

if (! ((GETPOST('testmenuhider','int') || ! empty($conf->global->MAIN_TESTMENUHIDER)) && empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)))
{
	$conf->dol_hide_leftmenu = 1;   // Force hide of left menu.
}

$error=0;
$website=GETPOST('website', 'alpha');
$page=GETPOST('page', 'alpha');
$pageid=GETPOST('pageid', 'int');
$action=GETPOST('action','alpha');

if (GETPOST('delete')) { $action='delete'; }
if (GETPOST('preview')) $action='preview';
if (GETPOST('create')) { $action='create'; }
if (GETPOST('editmedia')) { $action='editmedia'; }
if (GETPOST('editcss')) { $action='editcss'; }
if (GETPOST('editmenu')) { $action='editmenu'; }
if (GETPOST('setashome')) { $action='setashome'; }
if (GETPOST('editmeta')) { $action='editmeta'; }
if (GETPOST('editcontent')) { $action='editcontent'; }

if (empty($action)) $action='preview';




/*
 * Actions
 */

if (GETPOST('refreshsite')) $pageid=0;      // If we change the site, we reset the pageid.
if (GETPOST('refreshpage')) $action='preview';


// Add a collab page
if ($action == 'add')
{
	$db->begin();

	$objectpage->title = GETPOST('WEBSITE_TITLE');
	$objectpage->pageurl = GETPOST('WEBSITE_PAGENAME');
	$objectpage->description = GETPOST('WEBSITE_DESCRIPTION');
	$objectpage->keywords = GETPOST('WEBSITE_KEYWORD');

	if (empty($objectpage->title))
	{
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("WEBSITE_PAGENAME")), null, 'errors');
		$error++;
	}

	if (! $error)
	{
		$res = $objectpage->create($user);
		if ($res <= 0)
		{
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		}
	}
	if (! $error)
	{
		$db->commit();
		setEventMessages($langs->trans("PageAdded", $objectpage->pageurl), null, 'mesgs');
		$action='';
	}
	else
	{
		$db->rollback();
	}

	$action = 'preview';
	$id = $objectpage->id;
}

// Update page
if ($action == 'delete')
{
	$db->begin();

	$res = $object->fetch(0, $website);

	$res = $objectpage->fetch($pageid, $object->fk_website);

	if ($res > 0)
	{
		$res = $objectpage->delete($user);
		if (! $res > 0)
		{
			$error++;
			setEventMessages($objectpage->error, $objectpage->errors, 'errors');
		}

		if (! $error)
		{
			$db->commit();
			setEventMessages($langs->trans("PageDeleted", $objectpage->pageurl, $website), null, 'mesgs');

			header("Location: ".$_SERVER["PHP_SELF"].'?website='.$website);
			exit;
		}
		else
		{
			$db->rollback();
		}
	}
	else
	{
		dol_print_error($db);
	}
}



/*
 * View
 */

$form = new Form($db);

$help_url='';

llxHeader('', $langs->trans("WebsiteSetup"), $help_url, '', 0, '', '', '', '', '', '<!-- Begin div class="fiche" -->'."\n".'<div class="fichebutwithotherclass">');

print "\n".'<form action="'.$_SERVER["PHP_SELF"].'" method="POST"><div>';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
if ($action == 'create')
{
	print '<input type="hidden" name="action" value="add">';
}


// Add a margin under toolbar ?
$style='';
if ($action != 'preview' && $action != 'editcontent') $style=' margin-bottom: 5px;';

//var_dump($objectpage);exit;
print '<div class="centpercent websitebar">';

if (count($object->records) > 0)
{
	// ***** Part for web sites

	print '<div class="websiteselection hideonsmartphoneimp">';
	print $langs->trans("WebSite").': ';
	print '</div>';

	// List of websites
	print '<div class="websiteselection">';
	$out='';
	$out.='<select name="website" class="minwidth100" id="website">';
	if (empty($object->records)) $out.='<option value="-1">&nbsp;</option>';
	// Loop on each sites
	$i=0;
	foreach($object->records as $key => $valwebsite)
	{
		if (empty($website)) $website=$valwebsite->ref;

		$out.='<option value="'.$valwebsite->ref.'"';
		if ($website == $valwebsite->ref) $out.=' selected';		// To preselect a value
		$out.='>';
		$out.=$valwebsite->ref;
		$out.='</option>';
		$i++;
	}
	$out.='</select>';
	$out.=ajax_combobox('website');
	print $out;
	print '<input type="submit" class="button" name="refreshsite" value="'.$langs->trans("Load").'">';

	if ($website)
	{
		$virtualurl='';
		$dataroot=DOL_DATA_ROOT.'/collab/'.$website;
		if (! empty($object->virtualhost)) $virtualurl=$object->virtualhost;
	}

	if ($website && $action == 'preview')
	{
		$disabled='';
		if (empty($user->rights->websites->write)) $disabled=' disabled="disabled"';

		print ' &nbsp; ';

		//print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("MediaFiles")).'" name="editmedia">';
		print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditCss")).'" name="editcss">';
		print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("EditMenu")).'" name="editmenu">';
		print '<input type="submit"'.$disabled.' class="button" value="'.dol_escape_htmltag($langs->trans("AddPage")).'" name="create">';
	}

	print '</div>';

	// Button for websites
	print '<div class="websitetools">';

	if ($action == 'preview')
	{
		print '<div class="websiteinputurl">';
		print '<input type="text" id="previewsiteurl" class="minwidth200imp" name="previewsite" placeholder="'.$langs->trans("http://myvirtualhost").'" value="'.$virtualurl.'">';
		//print '<input type="submit" class="button" name="previewwebsite" target="tab'.$website.'" value="'.$langs->trans("ViewSiteInNewTab").'">';
		$htmltext=$langs->trans("SetHereVirtualHost", $dataroot);
		print $form->textwithpicto('', $htmltext);
		print '</div>';

		$urlext=$virtualurl;
		$urlint=$urlwithroot.'/public/collab/index.php?website='.$website;
		//if (! empty($object->virtualhost))
		//{
			print '<a class="websitebuttonsitepreview" id="previewsiteext" href="'.$urlext.'" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $dataroot, $urlext)).'">';
			print $form->textwithpicto('', $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $dataroot, $urlext?$urlext:$langs->trans("VirtualHostUrlNotDefined")), 1, 'preview_ext');
			print '</a>';
		//}

		print '<a class="websitebuttonsitepreview" id="previewsite" href="'.$urlwithroot.'/public/collab/index.php?website='.$website.'" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $urlint)).'">';
		print $form->textwithpicto('', $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Site"), $langs->transnoentitiesnoconv("Site"), $urlint, $dataroot), 1, 'preview');
		print '</a>';
	}

	if (in_array($action, array('editcss','editmenu','create')))
	{
		if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
		if (preg_match('/^create/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		if (preg_match('/^edit/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
	}

	print '</div>';


	// ***** Part for pages

	if ($website)
	{
		print '</div>';

		$array=$objectpage->fetchAll($object->id);
		if (! is_array($array) && $array < 0) dol_print_error('', $objectpage->error, $objectpage->errors);
		$atleastonepage=(is_array($array) && count($array) > 0);

		print '<div class="centpercent websitebar"'.($style?' style="'.$style.'"':'').'">';
		print '<div class="websiteselection hideonsmartphoneimp">';
		print $langs->trans("Page").': ';
		print '</div>';
		print '<div class="websiteselection">';

		if ($action != 'add')
		{
			$out='';
			$out.='<select name="pageid" id="pageid" class="minwidth200">';
			if ($atleastonepage)
			{
				if (empty($pageid) && $action != 'create')      // Page id is not defined, we try to take one
				{
					$firstpageid=0;$homepageid=0;
					foreach($array as $key => $valpage)
					{
						if (empty($firstpageid)) $firstpageid=$valpage->id;
						if ($object->fk_default_home && $key == $object->fk_default_home) $homepageid=$valpage->id;
					}
					$pageid=$homepageid?$homepageid:$firstpageid;   // We choose home page and if not defined yet, we take first page
				}

				foreach($array as $key => $valpage)
				{
					$out.='<option value="'.$key.'"';
					if ($pageid > 0 && $pageid == $key) $out.=' selected';		// To preselect a value
					$out.='>';
					$out.=$valpage->title;
					if ($object->fk_default_home && $key == $object->fk_default_home) $out.=' ('.$langs->trans("HomePage").')';
					$out.='</option>';
				}
			}
			else $out.='<option value="-1">&nbsp;</option>';
			$out.='</select>';
			$out.=ajax_combobox('pageid');
			print $out;
		}
		else
		{
			print $langs->trans("New");
		}

		print '<input type="submit" class="button" name="refreshpage" value="'.$langs->trans("Load").'"'.($atleastonepage?'':' disabled="disabled"').'>';
		//print $form->selectarray('page', $array);

		if ($action == 'preview')
		{
			$disabled='';
			if (empty($user->rights->websites->write)) $disabled=' disabled="disabled"';

			if ($pageid > 0)
			{
				print ' &nbsp; ';

				if ($object->fk_default_home > 0 && $pageid == $object->fk_default_home) print '<input type="submit" class="button" disabled="disabled" value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
				else print '<input type="submit" class="button"'.$disabled.' value="'.dol_escape_htmltag($langs->trans("SetAsHomePage")).'" name="setashome">';
				print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageMeta")).'" name="editmeta">';
				print '<input type="submit" class="button"'.$disabled.'  value="'.dol_escape_htmltag($langs->trans("EditPageContent")).'" name="editcontent">';
				//print '<a href="'.$_SERVER["PHP_SELF"].'?action=editmeta&website='.urlencode($website).'&pageid='.urlencode($pageid).'" class="button">'.dol_escape_htmltag($langs->trans("EditPageMeta")).'</a>';
				//print '<a href="'.$_SERVER["PHP_SELF"].'?action=editcontent&website='.urlencode($website).'&pageid='.urlencode($pageid).'" class="button">'.dol_escape_htmltag($langs->trans("EditPageContent")).'</a>';
				print '<input type="submit" class="buttonDelete" name="delete" value="'.$langs->trans("Delete").'"'.($atleastonepage?'':' disabled="disabled"').'>';
			}
		}

		print '</div>';
		print '<div class="websiteselection">';
		print '</div>';

		print '<div class="websitetools">';

		if ($website && $pageid > 0 && $action == 'preview')
		{
			$websitepage = new WebSitePage($db);
			$websitepage->fetch($pageid);

			$realpage=$urlwithroot.'/public/collab/index.php?website='.$website.'&page='.$pageid;
			$pagealias = $websitepage->pageurl;

			print '<div class="websiteinputurl">';
			print '<input type="text" id="previewpageurl" class="minwidth200imp" name="previewsite" value="'.$pagealias.'" disabled="disabled">';
			//print '<input type="submit" class="button" name="previewwebsite" target="tab'.$website.'" value="'.$langs->trans("ViewSiteInNewTab").'">';
			$htmltext=$langs->trans("WEBSITE_PAGENAME", $pagealias);
			print $form->textwithpicto('', $htmltext);
			print '</div>';

			if (! empty($object->virtualhost))
			{
				$urlext=$virtualurl.'/'.$pagealias.'.php';
				print '<a class="websitebuttonsitepreview" id="previewpageext" href="'.$urlext.'" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $dataroot, $urlext)).'">';
				print $form->textwithpicto('', $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $dataroot, $urlext?$urlext:$langs->trans("VirtualHostUrlNotDefined")), 1, 'preview_ext');
				print '</a>';
			}
			else
			{
				print '<a class="websitebuttonsitepreview" id="previewpageextnoclick" href="#">';
				print $form->textwithpicto('', $langs->trans("PreviewSiteServedByWebServer", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $dataroot, $urlext?$urlext:$langs->trans("VirtualHostUrlNotDefined")), 1, 'preview_ext');
				print '</a>';
			}

			print '<a class="websitebuttonsitepreview" id="previewpage" href="'.$realpage.'&nocache='.dol_now().'" class="button" target="tab'.$website.'" alt="'.dol_escape_htmltag($langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage)).'">';
			print $form->textwithpicto('', $langs->trans("PreviewSiteServedByDolibarr", $langs->transnoentitiesnoconv("Page"), $langs->transnoentitiesnoconv("Page"), $realpage, $dataroot), 1, 'preview');
			print '</a>';       // View page in new Tab
			//print '<input type="submit" class="button" name="previewpage" target="tab'.$website.'"value="'.$langs->trans("ViewPageInNewTab").'">';

			// TODO Add js to save alias like we save virtual host name and use dynamic virtual host for url of id=previewpageext
		}
		if (! in_array($action, array('editcss','editmenu','create')))
		{
			if ($action != 'preview') print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Cancel")).'" name="preview">';
			if (preg_match('/^create/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
			if (preg_match('/^edit/',$action)) print '<input type="submit" class="button" value="'.dol_escape_htmltag($langs->trans("Save")).'" name="update">';
		}

		print '</div>';

		if ($action == 'preview')
		{
			// Adding jquery code to change on the fly url of preview ext
			if (! empty($conf->use_javascript_ajax))
			{
				print '<script type="text/javascript" language="javascript">
                    jQuery(document).ready(function() {
                    	jQuery("#previewsiteext,#previewpageext").click(function() {
                            newurl=jQuery("#previewsiteurl").val();
                            newpage=jQuery("#previewsiteurl").val() + "/" + jQuery("#previewpageurl").val() + ".php";
                            console.log("Open url "+newurl);
                            /* Save url */
                            jQuery.ajax({
                                method: "POST",
                                url: "'.DOL_URL_ROOT.'/core/ajax/saveinplace.php",
                                data: {
                                    field: \'editval_virtualhost\',
                                    element: \'websites\',
                                    table_element: \'website\',
                                    fk_element: '.$object->id.',
                                    value: newurl,
                                },
                                context: document.body
                            });

                            jQuery("#previewsiteext").attr("href",newurl);
                            jQuery("#previewpageext").attr("href",newpage);
                        });
                    });
                    </script>';
			}
		}
	}
}
else
{
	print '<div class="websiteselection">';
	$langs->load("errors");
	print $langs->trans("ErrorModuleSetupNotComplete");
	print '<div>';
	$action='';
}


print '</div>';

$head = array();

if ($action == 'editcontent')
{
	/*
     * Editing global variables not related to a specific theme
     */

	$csscontent = @file_get_contents($filecss);

	$contentforedit = '';
	/*$contentforedit.='<style scoped>'."\n";        // "scoped" means "apply to parent element only". Not yet supported by browsers
    $contentforedit.=$csscontent;
    $contentforedit.='</style>'."\n";*/
	$contentforedit .= $objectpage->content;

	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('PAGE_CONTENT',$contentforedit,'',500,'Full','',true,true,true,ROWS_5,'90%');
	$doleditor->Create(0, '', false);
}

print "</div>\n</form>\n";




llxFooter();

$db->close();
