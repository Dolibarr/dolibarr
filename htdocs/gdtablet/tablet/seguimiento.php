<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
require_once '../lib/frontend.lib.php';
require_once '../lib/other.lib.php';

$category_static = new Categorie($db);
$form = new Form($db);

$now = dol_now();
$selected_category = GETPOST('categories', 'array');
$search_name = GETPOST('name');
$search_type = GETPOST('search_type');
$search_state = GETPOST('search_state');
$search_visita = GETPOST('search_visita');

$events = GdtabletMisc::getAllEvents($db, $user, $search_name, $selected_category, $search_state, $search_type,
	$search_visita);
$customer_types = array(
	'CL' => 'CL',
	'POT' => 'POT'
);

GdtabletFrontend::llxHeader('Seguimiento', 'seguimiento');

?>

<script type="text/javascript">
	jQuery(document).ready(function () {
		var jqseg_tr = jQuery('table.seguimiento tr');

		jqseg_tr.click(function () {

			var object = this;
			var id = jQuery(this).data('id');

			if (!id) {
				return;
			}

			var public_notes = jQuery('div#public_notes');
			public_notes.html('Cargando…');

			jQuery.getJSON('<?php echo dol_buildpath('/gdtablet/ajax/getPublicNotes.php', 2) ?>', {
				'socid': id
			}, function (data) {

				if (data.status == 'error') {
					alert('Ha ocurrido un error al intentar recuperar las notas. Mensaje: ' + data.result);
				} else {
					var remail = /(<a href="mailto:.*">)?([a-z0-9_\.-]+@[\da-z\.-]+\.[a-z\.]{2,6})(<\/a>)?/g;
					var rephone = /((?:\+\d{2})?(?:(\d){9}))/g;

					public_notes.html(data.result.replace(remail, '<a href="mailto:$2">$2</a>').replace(rephone, '<a href="tel:$1">$1</a>'));

					//Resaltamos la fila
					jqseg_tr.css('text-decoration', 'inherit');
					jQuery(object).css('text-decoration', 'underline');
				}
			}).fail(function () {
				alert('Ha ocurrido un error al recuperar los datos');
			});
		});

		jqseg_tr.dblclick(function () {
			var id = jQuery(this).data('id');

			if (!id) {
				return;
			}

			window.open('<?php echo dol_buildpath('/gdtablet/tablet/soc.php', 2) ?>?id=' + id);
		});

		jQuery('input#hide_provincia').click(function () {
			var selector = jQuery('table.seguimiento th#provincia, table.seguimiento td#provincia');
			var divtabla = jQuery('div#divtabla');
			var divnota = jQuery('div#divnota');

			if (jQuery(this).prop('checked')) {
				divtabla.css('width', '50%');
				divnota.css('width', '50%');
				selector.hide();
			} else {
				divtabla.css('width', '60%');
				divnota.css('width', '40%');
				selector.show();
			}
		});
	});
</script>

<form method="post">
	<div style="width: 50%; float: right">
		<?php echo GdtabletFrontend::selectAllThirdpartyCategories($db, 'categories', $selected_category) ?>

		<br><br>
	</div>

	<div style="width: 49%">
		<div>
			<div style="width: 90px; float: left">
				<input type="submit" value="Buscar" class="button">
			</div>
			<div style="margin-left: 90px; text-align: right">
				<input name="name" type="text" placeholder="Nombre del tercero" value="<?php echo $search_name ?>">
			</div>
		</div>
		<br>
		<label for="hide_provincia">Ocultar provincia</label><input type="checkbox" id="hide_provincia">

		<br>
		<br>
	</div>

	<div style="clear: both"></div>
	<div style="width: 100%">
		<div id="divnota" style="overflow-y: scroll; height: 92vh; float: right; width: 40%">
			<div style="width: 100%; height: 92vh;border: 2px solid black;background-color: white; ">
				<div style="text-align: center; border-bottom: 2px solid black; font-weight: bold;padding: 1px">Nota</div>
				<div style="padding: 3px" id="public_notes"></div>
			</div>
		</div>
		<div id="divtabla" style="overflow-y: scroll; height: 92vh; width: 60%">
			<table class="seguimiento" cellspacing="0">
				<thead>
				<tr>
					<th>Pot</th>
					<th>Tercero</th>
					<th>Seguim</th>
					<th>Tel</th>
					<th>Vis</th>
					<th>CL</th>
					<th id="provincia">Provincia</th>
				</tr>
				<tr>
					<td></td>
					<td></td>
					<td></td>
					<td></td>
					<td><?php echo Form::selectarray('search_visita', array(
							0 => 'No',
							1 => 'Sí'
						), $search_visita, 1) ?></td>
					<td><?php echo Form::selectarray('search_type', $customer_types, $search_type, 1) ?></td>
					<td id="provincia"><?php echo Form::selectarray('search_state',
							GdtabletFrontend::getStates($db, $langs, 4),
							$search_state, 1) ?></td>
				</tr>
				</thead>
				<tbody>
				<?php foreach ($events as $event): ?>
					<tr data-id="<?php echo $event->thirdparty->id ?>"
					    style="<?php echo GdtabletFrontend::getCssStyle($event->fetchCategoryColor()) ?>">
						<td style="text-align: center"><?php echo GdtabletFrontend::getEventLibStatut($langs, $event->percentage) ?></td>
						<td><a href="<?php echo dol_buildpath('/gdtablet/tablet/soc.php',
								1) ?>?id=<?php echo $event->thirdparty->id ?>"><?php echo $event->thirdparty->getFullName($langs) ?></a>
						</td>
						<td style="text-align: center<?php echo $event->datep < $now ? ';background-color: red"' : '' ?>"><?php echo dol_print_date($event->datep) ?></td>
						<td><?php echo $event->thirdparty->array_options['options_tlfhabitual'] ?></td>
						<td style="text-align: center">
							<?php if ($event->array_options['options_'.GdtabletFrontend::EVENT_VISITA] == 1): ?>
								<img src="<?php echo dol_buildpath('/gdtablet/img/1454887527_bell.svg', 2) ?>"
								     width="16">
							<?php endif ?>
						</td>
						<td><?php if ($event->thirdparty->client == 1): ?>CL<?php else: ?>POT<?php endif; ?></td>
						<td id="provincia"><?php echo $event->thirdparty->state ?></td>
					</tr>
				<?php endforeach ?>
				</tbody>
			</table>
		</div>
	</div>
</form>


<?php llxFooter() ?>
