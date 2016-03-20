<?php

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once '../lib/frontend.lib.php';
require_once '../lib/other.lib.php';

$socid = GETPOST('id');
$action = GETPOST('action');

//Init objects
$form = new Form($db);
$formcompany = new FormCompany($db);
$object = new Societe($db);
$contact = new Contact($db);
$c = new Categorie($db);

// Security check
$result = restrictedArea($user, 'societe', $socid, '&societe', '', 'fk_soc', 'rowid');

$selected_category = '';
$selected_salesrep = '';

//Retrieve thirdparty
if ($socid) {
	if ($object->fetch($socid) < 1) {
		echo '<h1>Tercero no encontrado</h1>';
		die;
	}

	$object->fetch_optionals($object->id);

	//Set default values
	$cats = $c->containing($object->id, Categorie::TYPE_CUSTOMER);

	if (is_array($cats) && isset($cats[0])) {
		$selected_category = $cats[0]->id;
	}

	$salesRepresentatives = $object->getSalesRepresentatives($user);

	if (isset($salesRepresentatives[0])) {
		$selected_salesrep = $salesRepresentatives[0]['id'];
	}

	$method = 'update';
} else {
	$method = 'create';
}

//Evento de seguimiento del cliente.
//En caso de que no exista será false.
$event = GdtabletMisc::getThirdpartySeguimientoEvent($db, $object);

if (!$event) {
	$event = new ActionComm($db);
}

//Pasamos el GPS a la tabla
require_once dol_buildpath('/google/class/googlemaps.class.php');

$gmaps = new Googlemaps($db);
$gmaps->fetch($object->id);

//Define options
$client_options = array(
	0 => 'No es cliente',
	1 => 'Cliente',
	2 => 'Cliente potencial',
	3 => 'Cliente/cliente potencial',
);

/**
 * Actions
 */

//Used to set entered values in case there was an error
if (isset($_POST['action'])) {

	$object->name = GETPOST('name', 'alpha');
	$object->address = GETPOST('address', 'alpha');
	$object->zip = GETPOST('zip', 'alpha');
	$object->country_id = GETPOST('country') != '' ? GETPOST('country') : $mysoc->country_id;
	$object->state_id = GETPOST('state', 'int');
	$object->email = GETPOST('email', 'custom', 0, FILTER_SANITIZE_EMAIL);
	$object->phone = GETPOST('phone', 'alpha');
	$object->idprof1 = GETPOST('nif', 'alpha');
	$object->client = GETPOST('client', 'int');
	$object->commercial_id = GETPOST('comercial', 'int');
	$object->fk_prospectlevel = GETPOST('prospect', 'alpha');
	$object->note_private = GETPOST('note_private');

	$checked = false;

	if (GETPOST('visita')) {
		$checked = true;
	}

	$object->array_options['options_tlfhabitual'] = GETPOST('tlfhabitual');
	$event->array_options['options_'.GdtabletFrontend::EVENT_VISITA] = $checked;

	// We set country_id, country_code and country for the selected country
	if ($object->country_id) {
		$tmparray = getCountry($object->country_id, 'all');
		$object->country_code = $tmparray['code'];
		$object->country = $tmparray['label'];
	}

	$db->begin();

	//Update
	if ($object->id) {
		$category = GETPOST('category');

		$res = $object->update($object->id, $user);
	} else {

		// Load object modCodeTiers
		$module=(! empty($conf->global->SOCIETE_CODECLIENT_ADDON)?$conf->global->SOCIETE_CODECLIENT_ADDON:'mod_codeclient_leopard');
		if (substr($module, 0, 15) == 'mod_codeclient_' && substr($module, -3) == 'php')
		{
			$module = substr($module, 0, dol_strlen($module)-4);
		}
		$dirsociete=array_merge(array('/core/modules/societe/'),$conf->modules_parts['societe']);
		foreach ($dirsociete as $dirroot)
		{
			$res=dol_include_once($dirroot.$module.'.php');
			if ($res) break;
		}
		$modCodeClient = new $module;

		if (! empty($modCodeClient->code_auto)) {
			$object->code_client = $modCodeClient->getNextValue($object,0);
		}

		//Create
		$res = $object->create($user);
	}

	try {

		if ($res < 0) {
			throw new Exception('Error al crear/actualizar el tercero: '.$object->error);
		}

		$gps = explode(',', GETPOST('gps'));

		$gmaps->latitude = $gps[0];
		$gmaps->longitude = $gps[1];
		$gmaps->address = dol_format_address($object, 1, ' ');
		$gmaps->result_code = 'OK';

		//Si está el ID seteado entonces existe
		if ($gmaps->id) {
			$gmapsres = $gmaps->update($user);
		} else {
			$gmaps->fk_object = $object->id;
			$gmaps->type_object = 'company';
			$gmapsres = $gmaps->create($user);
		}

		if ($gmapsres < 1) {
			throw new Exception('Error al guardar las coordenadas GPS: '.$gmaps->error);
		}

		if ($object->update_note(dol_html_entity_decode($object->note_private, ENT_QUOTES), '_public') < 1) {
			throw new Exception('Error al actualizar la nota: '.$object->error);
		}

		if ($object->set_prospect_level($user) < 1) {
			throw new Exception('Error al actualizar el estado del cliente potencial: '.$object->error);
		}
		$object->setCategories(array(
			$category
		), 'customer');

		//Siempre que haya fecha intentamos actualizar o crear
		$seguimiento = GETPOST('seguimiento');

		if ($seguimiento) {
			//Fecha de inicio
			$datep = DateTime::createFromFormat('|Y-m-d\TH:i', $seguimiento);

			if (!$datep) {
				throw new Exception('La fecha de seguimiento no tiene un formato válido.');
			}

			//Fecha de fin, añadimos 1 hora
			$datef = clone $datep;
			$datef->add(new DateInterval('PT1H'));

			$event->datep = $datep->getTimestamp();
			$event->datef = $datef->getTimestamp();

			//Evento de seguimiento
			if ($event->id) {
				//Actualización
				if ($event->update($user) < 1) {
					throw new Exception('Error al actualizar el evento: '.$event->error);
				}
			} else {
				//Creación
				$event->socid = $object->id;
				$event->label = 'Seguimiento';
				$event->userownerid = $user->id;
				$event->type_code = GdtabletFrontend::EVENT_TYPE;

				if ($event->add($user) < 1) {
					throw new Exception('Error al crear el evento: '.$event->error);
				}
			}
		}

		$db->commit();
		setEventMessage('Cambios guardados');
	} catch (Exception $e) {
		$db->rollback();
		setEventMessage($e->getMessage(), 'errors');
	}

	if ($object->id) {
		header('Location: '.dol_buildpath('/gdtablet/tablet/soc.php?id='.$object->id, 2));
		die;
	}
} elseif ($method == 'create') {
	$object->country_id = $mysoc->country_id;
	$selected_salesrep = $user->id;
	$object->client = 2;
}

/**
 * View
 */

if ($event->datep) {
	$datep = DateTime::createFromFormat('U', $event->datep);
	$datep = $datep->format('Y-m-d\TH:i');
} else {
	$datep = '';
}

GdtabletFrontend::llxHeader('Tercero', 'soc');

?>

	<script type="text/javascript">

		var ckeditorConfig = '<?php echo dol_buildpath('/gdtablet/js/ckeditor.js', 2) ?>';

		jQuery(document).ready(function() {
			jQuery('label[for=gps]').click(function() {
				navigator.geolocation.getCurrentPosition(drawLocation);
				jQuery('input#gps').hide().parent().append('<span class="loading">Cargando…</span>');
			});
		});

		function drawLocation(position) {
			jQuery('input#gps')
				.val(position.coords.latitude + ',' + position.coords.longitude)
				.show().parent().children('.loading').detach();
		}
	</script>

	<form method="post">
		<div style="height: 100%; width: 99%;margin:10px">
			<div style="">
				<table class="border" style="width: 100%">
					<tbody>
					<tr>
						<td style="width: 20%"><label for="name">Nombre del tercero</label></td>
						<td style="width: 30%"><input type="text" name="name" id="name"
						                              value="<?php echo $object->name ?>"></td>
						<td style="width: 20%">Cliente potencial/Cliente</td>
						<td style="width: 30%"><?php echo $form::selectarray('client', $client_options, $object->client,
								0) ?></td>
					</tr>
					<tr>
						<td><label for="address">Dirección</label></td>
						<td><input type="text" name="address" id="address" value="<?php echo $object->address ?>"></td>
						<td><label for="gps" class="button">Coord. GPS</label></td>
						<td><input type="text" name="gps" id="gps" value="<?php echo $gmaps->latitude && $gmaps->longitude ? $gmaps->latitude.','.$gmaps->longitude : '' ?>"></td>
					</tr>
					<tr>
						<td><label for="state">Provincia</label></td>
						<td><?php print $form::selectarray('state',
								GdtabletFrontend::getStates($db, $langs, $object->country_id), $object->state_id,
								1) ?></td>
						<td><label for="zip">Código postal</label></td>
						<td><input type="text" name="zip" id="zip" value="<?php echo $object->zip ?>"></td>
					</tr>
					<tr>
						<td><label for="email">Email</label></td>
						<td><input type="text" name="email" id="email" value="<?php echo $object->email ?>"></td>
						<td><label for="country">País</label></td>
						<td><?php print $form::selectarray('country', GdtabletFrontend::getCountries($db, $langs),
								$object->country_id) ?></td>
					</tr>
					<tr>
						<td><label for="phone">Teléfono</label></td>
						<td><input type="text" name="phone" id="phone" value="<?php echo $object->phone ?>"></td>
						<td><label for="nif">NIF</label></td>
						<td><input type="text" name="nif" id="nif" value="<?php echo $object->idprof1 ?>"></td>
					</tr>
					<tr>
						<td><label for="comercial">Comercial</label></td>
						<td><?php print $form::selectarray('comercial', GdtabletFrontend::getUsers($db, $conf),
								$selected_salesrep, 1) ?></td>
						<td><label for="tlfhabitual">Teléfono hab.</label></td>
						<td><input type="text" name="tlfhabitual" id="tlfhabitual" value="<?php echo $object->array_options['options_tlfhabitual'] ?>"></td>
					</tr>
					<tr>
						<td><label for="category">Categoría cliente</label></td>
						<td><?php print $form->select_all_categories(Categorie::TYPE_CUSTOMER, $selected_category,
								'category'); ?></td>
						<td><label for="prospect">Cli. potencial</label></td>
						<td><?php print $form::selectarray('prospect', GdtabletFrontend::getProspectLevels($db, $langs),
								$object->fk_prospectlevel, 1) ?></td>
					</tr>
					<tr>
						<td><label for="seguimiento">Seguimiento</label></td>
						<td><input type="datetime-local" id="seguimiento" name="seguimiento" value="<?php echo $datep ?>"></td>
						<td><label for="visita">Visita</label></td>
						<td><input type="checkbox" name="visita" id="visita" value="on"<?php echo $event->array_options['options_'.GdtabletFrontend::EVENT_VISITA] ? ' checked' : '' ?>></td>
					</tr>

					</tbody>
				</table>
			</div>
			<div style="height: 50vh;">
				<?php
				$typeofdata = 'ckeditor:dolibarr_notes:100%:200::1:12:100';
				$tmp = explode(':', $typeofdata);
				require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
				$doleditor = new DolEditor('note_private', $object->note_private, '100%', ($tmp[3] ? $tmp[3] : '100'),
					'tablet', 'In', ($tmp[5] ? $tmp[5] : 0), true, true,
					($tmp[6] ? $tmp[6] : '20'), ($tmp[7] ? $tmp[7] : '100'));
				print $doleditor->Create(1);
				?>
				<br>
				<div style="text-align: right;margin-top: 5px">
					<input type="hidden" name="action" value="save">
					<input type="submit" value="Guardar" class="button">

					<?php

					if ($method == 'update') {

						if (!empty($conf->propal->enabled) && $user->rights->propal->creer && $object->status == 1) {
							$langs->load("propal");
							print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/propal.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddProp").'</a></div>';
						}

						if (!empty($conf->commande->enabled) && $user->rights->commande->creer && $object->status == 1) {
							$langs->load("orders");
							print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/card.php?socid='.$object->id.'&amp;action=create">'.$langs->trans("AddOrder").'</a></div>';
						}

						if (!empty($conf->facture->enabled)) {
							if ($user->rights->facture->creer && $object->status == 1) {
								$langs->load("bills");
								$langs->load("orders");

								if (!empty($conf->commande->enabled)) {
									if (!empty($orders2invoice) && $orders2invoice > 0) {
										print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/commande/orderstoinvoice.php?socid='.$object->id.'">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
									} else {
										print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("NoOrdersToInvoice")).'" href="#">'.$langs->trans("CreateInvoiceForThisCustomer").'</a></div>';
									}
								}

								if ($object->client != 0) {
									print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/compta/facture.php?action=create&socid='.$object->id.'">'.$langs->trans("AddBill").'</a></div>';
								} else {
									print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("ThirdPartyMustBeEditAsCustomer")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
								}

							} else {
								print '<div class="inline-block divButAction"><a class="butActionRefused" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddBill").'</a></div>';
							}
						}

						// Add action
						if (!empty($conf->agenda->enabled) && !empty($conf->global->MAIN_REPEATTASKONEACHTAB)) {
							if ($user->rights->agenda->myactions->create) {
								print '<div class="inline-block divButAction"><a class="butAction" href="'.DOL_URL_ROOT.'/comm/action/card.php?action=create&socid='.$object->id.'">'.$langs->trans("AddAction").'</a></div>';
							} else {
								print '<div class="inline-block divButAction"><a class="butAction" title="'.dol_escape_js($langs->trans("NotAllowed")).'" href="#">'.$langs->trans("AddAction").'</a></div>';
							}
						}
					}
					?>
				</div>
			</div>
		</div>
	</form>
<?php

llxFooter();