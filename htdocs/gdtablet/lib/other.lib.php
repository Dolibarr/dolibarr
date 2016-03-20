<?php

class GdtabletMisc
{
	/**
	 * @param DoliDB $db Database handler
	 * @param Societe $societe Thirdparty event
	 * @return ActionComm|bool Will return false in case there is less than 1 record
	 * @throws Exception In case of Mysql error
	 */
	public static function getThirdpartySeguimientoEvent(DoliDB $db, Societe $societe)
	{
		require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

		$sql = 'SELECT a.id FROM '.MAIN_DB_PREFIX.'actioncomm a';
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action=c.id ";
		$sql.= ' WHERE c.code = "'.$db->escape(GdtabletFrontend::EVENT_TYPE).'"';
		$sql.= ' AND fk_soc = '.(int) $societe->id;

		$query = $db->query($sql);

		if (!$query) {
			throw new Exception('Error interno');
		}

		if ($db->num_rows($query) < 1) {
			return false;
		}

		$result = $db->fetch_object($query);

		$actioncomm = new ActionComm($db);
		$actioncomm->fetch($result->id);
		$actioncomm->fetch_optionals($actioncomm->id);

		return $actioncomm;
	}

	/**
	 * @param DoliDb $db
	 * @param User $user
	 * @param string $search_3rdname
	 * @param array $search_catid
	 * @return GdActionComm[]
	 */
	public static function getAllEvents(DoliDb $db, User $user, $search_3rdname = null, $search_catid = array(), $search_state = null, $search_type = null, $search_visita = null)
	{
		require_once __DIR__.'/../class/GdActionComm.class.php';

		$return = array();

		$sql = 'SELECT
 a.id
FROM llx_c_actioncomm AS ca, llx_actioncomm AS a, llx_societe s, llx_actioncomm_extrafields ae, llx_categorie_societe cs
WHERE a.fk_action = ca.id AND a.entity IN (1) AND cs.fk_soc = s.rowid AND ca.code ="'.GdtabletFrontend::EVENT_TYPE.'" AND
		s.rowid = a.fk_soc AND ae.fk_object = a.id AND
      a.fk_user_action = '.$user->id;

		if ($search_3rdname) {
			$sql .= natural_search(array(
				"s.nom",
				"s.name_alias",
				"s.code_client",
				"s.code_fournisseur",
				"s.email",
				"s.url",
				"s.siren",
				"s.siret",
				"s.ape",
				"s.idprof4",
				"s.idprof5",
				"s.idprof6"
			), $search_3rdname);
		}
		if ($search_type == 'CL') {
			$sql .= ' AND s.client = 1';
		} elseif ($search_type == 'POT') {
			$sql .= ' AND s.client <> 1';
		}
		if ($search_state && $search_state != '-1') {
			$sql .= ' AND fk_departement = '.(int) $search_state;
		}
		if ($search_visita !== null && $search_visita !== '' && $search_visita != '-1') {
			if ($search_visita) {
				$sql .= ' AND ae.visita = '.(int) $search_visita;
			} else {
				$sql .= ' AND ae.visita IS NULL';
			}
		}
		if ($search_catid) {
			$sql .= ' AND cs.fk_categorie IN ('.implode(',', $search_catid).')';
		}

		$sql .= $db->order('datep', 'ASC');

		$query = $db->query($sql);

		while ($result = $db->fetch_object($query)) {
			$tmp = new GdActionComm($db);
			$tmp->fetch($result->id);
			$tmp->fetch_thirdparty();
			$tmp->fetch_optionals();

			$return[] = $tmp;
		}

		return $return;
	}


	public static function addGMapsMarkers(GoogleMapAPI $gmaps, array $tabAddresses)
	{
		// Detect if we use https
		$sforhttps = (((empty($_SERVER["HTTPS"]) || $_SERVER["HTTPS"] != 'on') && (empty($_SERVER["SERVER_PORT"]) || $_SERVER["SERVER_PORT"] != 443)) ? '' : 's');

		$i = 0;
		foreach ($tabAddresses as $elem) {
			$i++;
			//if ($i != 9) continue;	// Output only eleme i = 9

			/*if($elem->client == 1) $icon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/green-dot.png";
			else $icon = "http://www.google.com/intl/en_us/mapfiles/ms/micons/red-dot.png";
			if ($sforhttps) $icon=preg_replace('/^http:/','https:',$icon);
			*/
			$address = dol_string_nospecial($elem->address, ', ', array("\r\n", "\n", "\r"));

			$addPropre = dol_escape_js($gmaps->no_special_character_v2($address));
			$lienGmaps = ' <a href="http'.$sforhttps.'://maps.google.fr/maps?q='.urlencode($gmaps->withoutSpecialChars($address)).'">Google Maps</a>';

			$html = '';
			$html .= '<a href="'.dol_buildpath('/gdtablet/tablet/soc.php', 2).'?id='.$elem->id.'"><b>'.$elem->name.'</b>';
			$html .= '</a>';
			$html .= '<br/>'.$addPropre.'<br/>';
			if (!empty($elem->url)) {
				$html .= '<a href="'.$elem->url.'">'.$elem->url.'</a><br/>';
			}
			if ($elem->phone) {
				$html .= '<br>Teléfono: <a href="tel:'.$elem->phone.'">'.$elem->phone.'</a>';
			}
			if ($elem->freqphone) {
				$html .= '<br>Teléfono habitual de contacto: <a href="tel:'.$elem->freqphone.'">'.$elem->freqphone.'</a>';
			}
			if ($elem->email) {
				$html .= '<br>Email: <a href="mailto:'.$elem->email.'">'.$elem->email.'</a>';
			}
			$html .= '<br/>'.$lienGmaps.'<br/>';

			if (isset($elem->latitude, $elem->longitude)) {
				$gmaps->addMarkerByCoords($elem->latitude, $elem->longitude, $elem->name, $html, '', $elem->icon);
			}
		}
	}

	public static function getCategoryColor(DoliDB $db, $socid)
	{
		$cat = new Categorie($db);

		foreach ($cat->containing($socid, Categorie::TYPE_CUSTOMER) as $currcat) {
			$currcat->fetch_optionals();

			if (in_array($currcat->array_options['options_COLOR'], array_keys(GdtabletFrontend::getMapColors()))) {
				return $currcat->array_options['options_COLOR'];
			}
		}

		return false;
	}
}