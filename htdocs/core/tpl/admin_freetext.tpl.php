<?php
/* Copyright (C) 2019 Christophe Battarel <christophe.battarel@altairis.fr>
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
 *
 */

/**
 * The following vars must be defined:
 * $freetexttitle
 * $freetextvar
 * $conf, $langs, $db
 * The following vars may also be defined:
 * $freetextlang
 */

// Protection to avoid direct call of template
if (empty($conf) || ! is_object($conf))
{
	print "Error, template page can't be called as URL";
	exit;
}

$substitutionarray=pdf_getSubstitutionArray($langs, null, null, 2);
$substitutionarray['__(AnyTranslationKey)__']=$langs->trans("Translation");
$htmltext = '<i>'.$langs->trans("AvailableVariables").':<br>';
foreach($substitutionarray as $key => $val)	$htmltext.=$key.'<br>';
$htmltext.='</i>';

print '<form action="'.$_SERVER["PHP_SELF"].'" method="post">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<input type="hidden" name="action" value="set_freetext">';
print '<input type="hidden" name="freetextvar" value="'.$freetextvar.'">';
print '<tr class="oddeven"><td colspan="2">';

$form=new Form($db);
print $form->textwithpicto($freetexttitle, $langs->trans("AddCRIfTooLong").'<br><br>'.$htmltext, 1, 'help', '', 0, 2, 'freetexttooltip').'<br>';

$freetextconfvar = $freetextvar;
if (! empty($conf->global->MAIN_MULTILANGS) )
{
	$freetextlang = GETPOST('freetextlang', 'alpha');
	require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
	$formadmin=new FormAdmin($db);
	print '<br/>'.$langs->trans('Language').'&emsp;';
    print $formadmin->select_language($freetextlang, 'freetextlang', 0, $freetextlang, 1);
	print '</br>';

	if (! empty($freetextlang))
	{
		$freetextconfvar .= '_'.$freetextlang;
	}
}

if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
{
    print '<textarea name="freetext" class="flat" cols="120">'.$conf->global->$freetextconfvar.'</textarea>';
}
else
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
    $doleditor=new DolEditor("freetext", $conf->global->$freetextconfvar, '', 80, 'dolibarr_notes');
    print $doleditor->Create();
}
print '</td><td class="right">';
print '<input type="submit" class="button" value="'.$langs->trans("Modify").'">';
print "</td></tr>\n";
print '</form>';

if (! empty($conf->global->MAIN_MULTILANGS) )
{
?>
	<script type="text/javascript">
		var freetext = [];
		freetext["0"] = "<?php echo $conf->global->$freetextvar;?>";
<?php
		$langs_available=$langs->get_available_languages(DOL_DOCUMENT_ROOT, 12);

		foreach ($langs_available as $key => $value)
		{
			$freetextlangvar = $freetextvar."_".$key;
			print 'freetext["'.$key.'"] = "'.$conf->global->$freetextlangvar.'";';
		}
?>
		$('#freetextlang').change(function()
		{
			<?php
				if (empty($conf->global->PDF_ALLOW_HTML_FOR_FREE_TEXT))
				{
				?>
					$('#freetext').html(freetext[$(this).val()]);
				<?php
				}
				else
				{
				?>
					CKEDITOR.instances.freetext.setData(freetext[$(this).val()]);
				<?php
				}
				?>
		});
	</script>
<?php
}

