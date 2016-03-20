<?php

require '../../main.inc.php';
require '../../core/lib/admin.lib.php';
require '../class/EnlaceKeme.class.php';

$relaciones = EnlaceKeme::obtenerRelacionesIVA();

if ($_POST) {

    $refs = GETPOST('ref');

    foreach ($refs as $id => $ref) {
        if (isset($relaciones[$id])) {

            if (strlen($ref) > 5) {
                setEventMessage('Las referencias de KEME no pueden tener más de 5 caracteres.', 'errors');
                continue;
            }

            if (!EnlaceKeme::actualizarRelacionIVA($id, $ref)) {
                setEventMessage('Ha ocurrido un error al intentar grabar la relación', 'errors');
                continue;
            }

            //Actualizamos el nombre
            $relaciones[$id]['ref'] = $ref;
        }
    }

    $freetext = GETPOST('cuenta_irpf');

    dolibarr_set_const($db, "ENLACEKEME_CUENTA_IRPF",$freetext,'chaine',0,'',$conf->entity);

    $freetext = GETPOST('cuenta_aeat_iva');

    dolibarr_set_const($db, "ENLACEKEME_CUENTA_HAEAT_IVA",$freetext,'chaine',0,'',$conf->entity);

    $freetext = GETPOST('cuenta_anticipos');

    dolibarr_set_const($db, "ENLACEKEME_PREFIJOCUENTA_ANTICIPOS",$freetext,'chaine',0,'',$conf->entity);

}

$title = 'Configuración del módulo Enlace Keme-Contabilidad';

llxHeader('', $title);

$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php">'.$langs->trans("BackToModuleList").'</a>';
print_fiche_titre($title,$linkback,'setup');

print '<br />';

print '<div class="titre">Relación de códigos de IVA Dolibarr - KEME</div>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Tipo de IVA").'</td>';
print '<td>'.$langs->trans("Código KEME").'</td>';
print '<td>'.$langs->trans('Nota').'</td>';
print '<td style="width: 60px"></td>';
print '</tr>';

print '<form method="POST" action="">';

foreach ($relaciones as $id => $relacion) {

    print '<tr>
        <td>'.$relacion['rate'].'</td>
        <td><input type="text" value="'.$relacion['ref'].'" name="ref['.$id.']" maxlength="5"></td>
        <td>'.$relacion['note'].'</td>
    </td></tr>';
}

print '</table>';

print '<br /><br />';

print '<div class="titre">Otros parámetros</div>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td>Parámetro</td><td>Valor</td></tr>';

print '<tr><td>Prefijo para generación de cuenta contable de anticipos de clientes<br />La generación sera: {prefijo}+{cód cliente}. Ejemplo: <strong>438</strong>000001</td><td><input type="text" name="cuenta_anticipos" value="'.$conf->global->ENLACEKEME_PREFIJOCUENTA_ANTICIPOS.'"></td></tr>';
print '<tr><td>Cuenta contable de Hª pública acreedora por retenciones practicadas</td><td><input type="text" name="cuenta_irpf" value="'.$conf->global->ENLACEKEME_CUENTA_IRPF.'"></td></tr>';
print '<tr><td>Cuenta contable de Hª pública acreedora por IVA</td><td><input type="text" name="cuenta_aeat_iva" value="'.$conf->global->ENLACEKEME_CUENTA_HAEAT_IVA.'"></td></tr>';

print '</table>';


print '<br /><br /><div style="text-align: center"><input type="submit" value="Actualizar" class="button"></div></form>';

dol_fiche_end();

llxFooter();
$db->close();
?>