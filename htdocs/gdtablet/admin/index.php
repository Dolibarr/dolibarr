<?php

require_once __DIR__.'/../../main.inc.php';
require_once __DIR__.'/../../core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

if ($_POST) {
	dolibarr_set_const($db, 'GDTABLET_SOCIETE', GETPOST('GDTABLET_SOCIETE', 'int'));
}

$title = 'Configuración del módulo Gdtablet';

$form = new Form($db);

llxHeader('', $title);

$langs->load('admin');

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($title,$linkback,'setup');

print '<form method="post"><table class="border" width="100%">';
print '<tr>';
print '<td>Tercero emisor de presupuestos para la plantilla "GD aparatos"</td>';
print '<td>'.$form->select_thirdparty_list($conf->global->GDTABLET_SOCIETE, 'GDTABLET_SOCIETE', 's.client = 0 AND s.fournisseur = 0', 1).'</td>';
print '</tr>
</table><br>
<div style="text-align: center"><input type="submit" class="button"></div></form>';

llxFooter();