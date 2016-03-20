<?php

class GdtabletFrontend
{
	const EVENT_VISITA = 'VISITA';
	const EVENT_TYPE = 'AC_SEGUIMIEN';

	const COLOR_LIGHTBLUE = 1;
	const COLOR_GREEN = 2;
	const COLOR_ORANGE = 3;
	const COLOR_PURPLE = 4;
	const COLOR_RED = 5;
	const COLOR_WHITE = 6;
	const COLOR_YELLOW = 7;
	const COLOR_PINK = 8;
	const COLOR_BLUE = 9;

	public static function getMapColors()
	{
		return array(
			self::COLOR_LIGHTBLUE => 'Azul claro',
			self::COLOR_GREEN => 'Verde',
			self::COLOR_ORANGE => 'Naranja',
			self::COLOR_PURPLE => 'Morado',
			self::COLOR_RED => 'Rojo',
			self::COLOR_WHITE => 'Blanco',
			self::COLOR_YELLOW => 'Amarillo',
			self::COLOR_PINK => 'Rosa',
			self::COLOR_BLUE => 'Azul',
		);
	}

	public static function getColoredMapMarkerUrl($color, $pined = false)
	{
		$map_unpin = array(
			self::COLOR_LIGHTBLUE => 'http://maps.google.com/mapfiles/ms/micons/lightblue.png',
			self::COLOR_GREEN => 'http://maps.google.com/mapfiles/ms/micons/green.png',
			self::COLOR_ORANGE => 'http://maps.google.com/mapfiles/ms/micons/orange.png',
			self::COLOR_PURPLE => 'http://maps.google.com/mapfiles/ms/micons/purple.png',
			self::COLOR_RED => 'http://maps.google.com/mapfiles/ms/micons/red.png',
			self::COLOR_WHITE => 'http://maps.google.com/mapfiles/ms/micons/white.png',
			self::COLOR_YELLOW => 'http://maps.google.com/mapfiles/ms/micons/yellow.png',
			self::COLOR_PINK => 'http://maps.google.com/mapfiles/ms/micons/pink.png',
			self::COLOR_BLUE => 'http://maps.google.com/mapfiles/ms/micons/blue.png',
		);

		$map_pin = array(
			self::COLOR_LIGHTBLUE => 'http://maps.google.com/mapfiles/ms/micons/ltblu-pushpin.png',
			self::COLOR_GREEN => 'http://maps.google.com/mapfiles/ms/micons/grn-pushpin.png',
			self::COLOR_PURPLE => 'http://maps.google.com/mapfiles/ms/micons/purple-pushpin.png',
			self::COLOR_RED => 'http://maps.google.com/mapfiles/ms/micons/red-pushpin.png',
			self::COLOR_YELLOW => 'http://maps.google.com/mapfiles/ms/micons/ylw-pushpin.png',
			self::COLOR_PINK => 'http://maps.google.com/mapfiles/ms/micons/pink-pushpin.png',
			self::COLOR_BLUE => 'http://maps.google.com/mapfiles/ms/micons/blue-pushpin.png',
		);

		$default_color = self::COLOR_RED;

		if (!$color) {
			$color = self::COLOR_RED;
		}

		if ($pined) {
			if (isset($map_pin[$color])) {
				return $map_pin[$color];
			} else {
				return $map_pin[$default_color];
			}
		} else {
			if (isset($map_unpin[$color])) {
				return $map_unpin[$color];
			} else {
				return $map_unpin[$default_color];
			}
		}
	}

	public static function getCssStyle($color)
	{
		$map = array(
			self::COLOR_LIGHTBLUE => 'gray',
			self::COLOR_GREEN => 'green',
			self::COLOR_ORANGE => 'orange',
			self::COLOR_PURPLE => 'purple',
			self::COLOR_RED => 'red',
			self::COLOR_WHITE => 'white',
			self::COLOR_YELLOW => 'yellow',
			self::COLOR_PINK => 'pink',
			self::COLOR_BLUE => 'blue',
		);

		return 'background: '.$map[$color];
	}

	public static function llxHeader($title, $selectedmenu)
	{
		// html header
		top_htmlhead('', $title, 0, 0, array(
			'/gdtablet/js/ckeditor.js'
		), array(
			'/gdtablet/css/style.css'
		));

		//Mostramos el menú
		$menuentries = array(
			'seguimiento' => 'Seguimiento',
			'soc' => 'Crear tercero',
			'maps' => 'Mapa',
			'calendar' => 'Calendario'
		);

		?>
		<div class="side-nav-vert">
			<div id="id-top" style="height: 25px">
				<div id="tmenu_tooltip" class="tmenu">
					<div class="tmenudiv">
						<ul class="tmenu">
							<?php foreach ($menuentries as $menukey => $menulabel): ?>
							<li class="<?php echo $selectedmenu == $menukey ? 'tmenusel' : 'tmenu' ?>" style="height: 25px">
								<div class="tmenuleft" style="height: 25px"></div>
								<div class="tmenucenter" style="height: 25px"><a class="tmenu" id="mainmenua_home"
								           href="<?php echo dol_buildpath('/gdtablet/tablet/'.$menukey.'.php', 2) ?>"><span class="mainmenuaspan"><?php echo $menulabel ?></span></a>
								</div>
							</li>
							<?php endforeach ?>
							<li class="tmenuend" id="mainmenutd_">
								<div class="tmenuleft"></div>
								<div class="tmenucenter" style="height: 25px"></div>
							</li>
						</ul>
					</div>
				</div>
				<div class="login_block">
					<div class="login_block_other" style="padding-top: 0">
						<div class="inline-block">
							<div class="classfortooltip inline-block login_block_elem inline-block"
							     style="padding: 0; padding-right: 3px !important;"><a
									href="/user/logout.php"><img src="/theme/eldy/img/logout.png" border="0"
							                                     alt="Desconexión" class="login"></a></div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php
	}

	public static function getStates(DoliDB $db, Translate $langs, $country_id)
	{
		$return = array();

		// On recherche les departements/cantons/province active d'une region et pays actif
		$sql = "SELECT d.rowid, d.code_departement as code, d.nom as name, d.active, c.label as country, c.code as country_code FROM";
		$sql .= " ".MAIN_DB_PREFIX ."c_departements as d, ".MAIN_DB_PREFIX."c_regions as r,".MAIN_DB_PREFIX."c_country as c";
		$sql .= " WHERE d.fk_region=r.code_region and r.fk_pays=c.rowid";
		$sql .= " AND d.active = 1 AND r.active = 1 AND c.active = 1";
		if ($country_id && is_numeric($country_id))   $sql .= " AND c.rowid = '".$country_id."'";
		$sql .= " ORDER BY c.code, d.code_departement";

		$result= $db->query($sql);

		while ($objp = $db->fetch_object($result)) {
			$return[$objp->rowid] = $objp->code . ' - ' . ($langs->trans($objp->code)!=$objp->code?$langs->trans($objp->code):($objp->name!='-'?$objp->name:''));
		}

		return $return;
	}

	public static function getUsers(DoliDB $db, Conf $conf)
	{
		$return = array();

		// On recherche les utilisateurs
		$sql = "SELECT DISTINCT u.rowid, u.lastname as lastname, u.firstname, u.statut, u.login, u.admin, u.entity";
		$sql.= " FROM ".MAIN_DB_PREFIX ."user as u";
		$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
		$sql.= " ORDER BY u.lastname ASC";

		$result= $db->query($sql);

		while ($objp = $db->fetch_object($result)) {
			$return[$objp->rowid] = dolGetFirstLastname($objp->firstname, $objp->lastname);
		}

		return $return;
	}

	public static function getCountries(DoliDB $db, Translate $langs)
	{
		$return = array();

		$sql = "SELECT rowid, code as code_iso, code_iso as code_iso3, label, favorite";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_country";
		$sql.= " WHERE active > 0";
		//$sql.= " ORDER BY code ASC";

		$result= $db->query($sql);

		while ($objp = $db->fetch_object($result)) {
			$return[$objp->rowid] = ($objp->code_iso && $langs->transnoentitiesnoconv("Country".$objp->code_iso)!="Country".$objp->code_iso?$langs->transnoentitiesnoconv("Country".$objp->code_iso):($objp->label!='-'?$objp->label:''));
		}

		return $return;
	}

	public static function getProspectLevels(DoliDB $db, Translate $langs)
	{
		$langs->load('companies');

		$return = array();

		$sql = "SELECT code, label";
		$sql.= " FROM ".MAIN_DB_PREFIX."c_prospectlevel";
		$sql.= " WHERE active > 0";
		$sql.= " ORDER BY sortorder";

		$result= $db->query($sql);

		while ($objp = $db->fetch_object($result)) {
			$return[$objp->code] = $langs->trans($objp->code);
		}

		return $return;
	}

	public static function selectAllThirdpartyCategories(DoliDB $db, $htmlname, $selected = array())
	{
		global $langs;
		$langs->load("categories");

		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

		$cat = new Categorie($db);
		$cate_arbo = $cat->get_full_arbo(Categorie::TYPE_CUSTOMER);

		$output = '<select name="'.$htmlname.'[]" multiple style="width: 100%">';
		if (is_array($cate_arbo))
		{
			if (! count($cate_arbo)) $output.= '<option value="-1" disabled>'.$langs->trans("NoCategoriesDefined").'</option>';
			else
			{

				foreach($cate_arbo as $key => $value)
				{
					if (in_array($cate_arbo[$key]['id'], $selected))
					{
						$add = 'selected ';
					}
					else
					{
						$add = '';
					}
					$output.= '<option '.$add.'value="'.$cate_arbo[$key]['id'].'">'.dol_trunc($cate_arbo[$key]['fulllabel'],'','middle').'</option><br>';
				}
			}
		}
		$output.= "</select>";

		return $output;
	}

	public static function getEventLibStatut(Translate $langs, $percent)
	{
		if ($percent == -1) {
			return img_picto($langs->trans('StatusNotApplicable'), 'statut9');
		} elseif ($percent == 0) {
			return img_picto($langs->trans('StatusActionToDo'), 'statut1');
		} elseif ($percent > 0 && $percent < 100) {
			return img_picto($langs->trans('StatusActionInProcess').' - '.$percent.'%',
				'statut3@gdtablet');
		} elseif ($percent >= 100) {
			return img_picto($langs->trans('StatusActionDone'), 'statut6');
		}

	}
}