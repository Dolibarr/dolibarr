<?php

require '../main.inc.php';

if (! $user->rights->enlacekeme->export) {
    accessforbidden();
}

require 'class/EnlaceKeme.class.php';

$ek = new EnlaceKeme();

$type = array();

if ($_POST) {

    if (!$type = GETPOST('type')) {
        $type = array();
    }

    if (isset($type['customers'])) {
        $ek->exportThirds(EnlaceKeme::EXPORT_CUSTOMERS);
    }
    if (isset($type['suppliers'])) {
        $ek->exportThirds(EnlaceKeme::EXPORT_SUPPLIERS);
    }

    $today = new DateTime('now');

    $from = DateTime::createFromFormat('d/m/Y', GETPOST('from'));
    $to = DateTime::createFromFormat('d/m/Y', GETPOST('to'));

	//Comprobamos la validez de la fecha solo en caso de que se hayan marcado datos acotados
	if (isset($type['customers_payments']) || isset($type['suppliers_payments']) ||
		isset($type['suppliers_invoices']) || isset($type['customers_invoices']) ||
		isset($type['tax_payments'])) {

		if (!$from) {
			setEventMessage('La fecha de inicio no es correcta', 'errors');
			header('Location: wizard.php');
			die;
		}

		if (!$to) {
			setEventMessage('La fecha de fin no es correcta', 'errors');
			header('Location: wizard.php');
			die;
		}
	}

    if (isset($type['customers_payments'])) {
        $ek->exportCustomerPayments($from, $to);
    }
    if (isset($type['suppliers_payments'])) {
        $ek->exportSupplierPayments($from, $to);
    }
    if (isset($type['suppliers_invoices'])) {
        $ek->exportSupplierInvoices($from, $to);
    }
    if (isset($type['customers_invoices'])) {
        $ek->exportCustomerInvoices($from, $to);
    }
    if (isset($type['tax_payments'])) {
        $ek->exportTaxPayments($from, $to);
    }

    header('Content-type: Text');
    header('Content-Disposition: attachment; filename="export_'.$today->format('U').'"');
    echo $ek->getResult();
    die;
}

$check = $ek->checkAccountancyCodes();

$title = 'Exportación de datos a Keme Contabilidad';

llxHeader('', $title, '', '' ,'' ,'' ,'', array(
    '/enlacekeme/css/main.css'
));

print_fiche_titre($title);

$default_from = new DateTime('yesterday');
$default_to = new DateTime('now');

$form = new Form($db);

?>

<?php
if (!$check) {
    echo info_admin('La exportación no podrá comenzar hasta que todos los bancos y terceros tengan códigos contables asignados.');
}
?>

    <form method="POST" action="" id="kchoose">

<div class="titre">

    <p style="float:right;margin:0"><a href="#" onclick="$('form#kchoose input[type=\'checkbox\']').attr('checked', 'checked');return false;">Seleccionar todos</a></p>
    <p>Elija los datos que desea exportar:</p>
    </div>


    <div id="customers" class="option">
        <label for="customers" class="cb">Cuentas de clientes</label>
        <input name="type[customers]" value="customers" id="customers" type="checkbox" <?php echo (in_array('customers', $type) ? 'checked' : '') ?>/>
    </div>
    <div id="suppliers" class="option">
        <label for="suppliers" class="cb">Cuentas de proveedores</label>
        <input name="type[suppliers]" id="suppliers" type="checkbox" <?php echo (in_array('suppliers', $type) ? 'checked' : '') ?>/>
    </div>

    <br /><br />

    <fieldset>
        <legend>Datos acotados</legend>

        <div class="option">
            <label for="from">Fecha de inicio:</label><br />
            <?php print Form::select_date($default_from->format('Y-m-d'), 'from', 0, 0, 0, 'from'); ?>
        </div>

        <div class="option">
            <label for="to">Fecha de fin:</label><br />
            <?php print Form::select_date($default_to->format('Y-m-d'), 'to', 0, 0, 0, 'to'); ?>
        </div>

        <br /><br />

        <div id="customers_invoices" class="option"><label for="customers_invoices" class="cb">Facturas de clientes</label><input name="type[customers_invoices]" id="customers_invoices" type="checkbox"/></div>
        <div id="suppliers_invoices" class="option"><label for="suppliers_invoices" class="cb">Facturas de proveedores</label><input name="type[suppliers_invoices]" id="suppliers_invoices" type="checkbox"/></div>

        <br />

        <div id="customers_payments" class="option"><label for="customers_payments" class="cb">Pagos de clientes</label><input name="type[customers_payments]" id="customers_payments" type="checkbox"/></div>
        <div id="suppliers_payments" class="option"><label for="suppliers_payments" class="cb">Pagos a proveedores</label><input name="type[suppliers_payments]" id="suppliers_payments" type="checkbox"/></div>
        <div id="tax_payments" class="option"><label for="tax_payments" class="cb">Pagos de impuestos (IVA e IRPF)</label><input name="type[tax_payments]" id="tax_payments" type="checkbox"/></div>

    </fieldset>

    <br /><br />

    <div style="text-align: center"><input type="submit" value="Exportar" class="button"<?php echo (!$check ? 'disabled="disabled"' : '') ?>></div>
</form>

<?php
dol_fiche_end();

llxFooter();
$db->close();