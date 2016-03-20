<?php

/* Copyright (C) 2016 Marcos García <marcosgdf@gmail.com>
 *
 * Licensed under the GNU GPL v3 or higher (See file gpl-3.0.html)
 *
 * Copyright (C) 2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 */

/**
 * Página de visualización de mapa con los terceros
 * Fork de /google/gmaps_all.php
 */

require '../../main.inc.php';
require_once __DIR__.'/../lib/frontend.lib.php';
require_once __DIR__.'/../lib/other.lib.php';
require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';

require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/contact.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/member.lib.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formother.class.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formcompany.class.php");
require_once(DOL_DOCUMENT_ROOT."/contact/class/contact.class.php");
dol_include_once("/google/class/googlemaps.class.php");
dol_include_once("/google/includes/GoogleMapAPIv3.class.php");

$langs->load("google@google");

// url is:  gmaps.php?mode=thirdparty|contact|member&id=id&max=max


$mode= 'thirdparty';
$id = GETPOST('id','int');
$MAXADDRESS=GETPOST('max','int')?GETPOST('max','int'):'25';	// Set packet size to 25 if no forced from url
$address='';
$socid = GETPOST('socid','int');
$selected_category = GETPOST('categories', 'array');

// Load third party
include_once(DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php');
if ($id > 0)
{
	$object = new Societe($db);
	$object->id = $id;
	$object->fetch($id);
	$address = $object->getFullAddress(1,', ');
	$url = $object->url;
}


/*
 * View
 */

$countrytable="c_pays";
$countrylabelfield='libelle';
include_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
if (versioncompare(versiondolibarrarray(),array(3,7,-3)) >= 0)
{
	$countrytable="c_country";
	$countrylabelfield='label';
}

GdtabletFrontend::llxHeader('Mapas', 'maps');

$form=new Form($db);
$formother = new FormOther($db);
$formcompany = new FormCompany($db);

$content = "Default content";
$act = "";

//On fabrique les onglets
$head=array();
$title='';
$picto='';
$type='';
	if ($user->societe_id) $socid=$user->societe_id;

	$search_sale=empty($conf->global->GOOGLE_MAPS_FORCE_FILTER_BY_SALE_REPRESENTATIVES)?GETPOST('search_sale'):-1;
	$search_departement = GETPOST("state_id","int");

	$title=$langs->trans("MapOfThirdparties");
	$picto='company';
	$type='company';
	$sql="SELECT s.rowid as id, s.nom as name, s.address, s.zip, s.town, s.url, s.email, s.phone, ";
	$sql.= " c.rowid as country_id, c.code as country_code, c.".$countrylabelfield." as country,";
	$sql.= " g.rowid as gid, g.fk_object, g.latitude, g.longitude, g.address as gaddress, g.result_code, g.result_label";
	$sql.= " FROM ".MAIN_DB_PREFIX."categorie_societe cs LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = cs.fk_soc";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX.$countrytable." as c ON s.fk_pays = c.rowid";
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."google_maps as g ON s.rowid = g.fk_object and g.type_object='".$type."'";
	if ($search_sale || (!$user->rights->societe->client->voir && ! $socid)) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	if ($search_departement != '' && $search_departement > 0) $sql.= ", ".MAIN_DB_PREFIX."c_departements as dp";
	$sql.= " WHERE s.status = 1";
	$sql.= " AND s.entity IN (".getEntity('societe', 1).")";
	if ($search_sale == -1 || (! $user->rights->societe->client->voir && ! $socid))	$sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($search_sale > 0)          $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$search_sale;
	if ($search_departement != '' && $search_departement > 0) $sql.= " AND s.fk_departement = dp.rowid AND dp.rowid = ".$db->escape($search_departement);
	if ($socid) $sql.= " AND s.rowid = ".$socid;	// protect for external user
	if ($selected_category) {
		$sql .= " AND cs.fk_categorie IN (".implode(',', $selected_category).")";
	}
	$sql.= " ORDER BY s.rowid";

// If the user can view prospects other than his'
if ($user->rights->societe->client->voir && empty($socid))
{
	$langs->load("commercial");
	print '<form name="formsearch" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<div style="width: 99%;padding:5px">';
	print '<div style="float: right">';
	print GdtabletFrontend::selectAllThirdpartyCategories($db, 'categories', $selected_category);
	print '</div>';
	print '<div style="width: 50%"><div style="width: 10%; float:right">';
	print ' <input type="submit" name="submit_search_sale" value="'.$langs->trans("Search").'" class="button" style="margin-top:15px"></div>';
	print '<div style="width: 90%">';
	print $langs->trans('ThirdPartiesOfSaleRepresentative'). ':<br>';
	print Form::selectarray('search_sale', GdtabletFrontend::getUsers($db, $conf), $search_sale, 1);
	if (! empty($conf->global->GOOGLE_MAPS_SEARCH_ON_STATE))
	{
		print ' &nbsp; &nbsp; &nbsp; ';
		print $langs->trans("State").': ';
		print $formcompany->select_state($search_departement,0,'state_id');
	}
	print '</div>';

	print '</div>';

	print '</form>';
	print '</div>';
}


// Fill array of contacts
$addresses = array();
$adderrors = array();
$googlemaps = new Googlemaps($db);
$countgeoencoding=0;
$countgeoencodedok=0;
$countgeoencodedall=0;

// Loop
dol_syslog("Search addresses sql=".$sql);
$resql=$db->query($sql);
if ($resql)
{
	$num=$db->num_rows($resql);
	$i=0;
	while ($i < $num)
	{
		$obj=$db->fetch_object($resql);
		if (empty($obj->country_code)) $obj->country_code=$mysoc->country_code;

		$error='';

		//Buscamos si hay visita
		$societe = new Societe($db);
		$societe->fetch($obj->id);
		$societe->fetch_optionals($obj->id);

		$addresstosearch=dol_format_address($societe,1," ");
		$address=dol_format_address($societe,1,", ");	// address to show

		$object=new stdClass();
		$object->id=$obj->id;
		$object->name=$obj->name?$obj->name:($obj->lastname.' '.$obj->firstname);
		$object->latitude = $obj->latitude;
		$object->longitude = $obj->longitude;
		$object->address = $address;
		$object->url = $obj->url;
		$object->email = $obj->email;
		$object->phone = $obj->phone;
		$object->freqphone = $societe->array_options['options_tlfhabitual'];

		$visita = false;

		if ($event = GdtabletMisc::getThirdpartySeguimientoEvent($db, $societe)) {
			if ($event->array_options['options_'.GdtabletFrontend::EVENT_VISITA]) {
				$visita = true;
			}
		}

		$object->icon = GdtabletFrontend::getColoredMapMarkerUrl(GdtabletMisc::getCategoryColor($db, $obj->id), $visita);

		$geoencodingtosearch=false;
		if ($obj->gaddress != $addresstosearch) $geoencodingtosearch=true;
		else if ((empty($object->latitude) || empty($object->longitude)) && (empty($obj->result_code) || in_array($obj->result_code, array('OK','OVER_QUERY_LIMIT')))) $geoencodingtosearch=true;

		if ($geoencodingtosearch && (empty($MAXADDRESS) || $countgeoencoding < $MAXADDRESS))
		{
			// Google limit usage of API to 5 requests per second
			if ($countgeoencoding && ($countgeoencoding % 5 == 0))
			{
				dol_syslog("Add a delay of 1");
				sleep(1);
			}

			$countgeoencoding++;

			$point = geocoding($addresstosearch);
			if (is_array($point))
			{
				$object->latitude=$point['lat'];
				$object->longitude=$point['lng'];

				// Update/insert database
				$googlemaps->id=$obj->gid;
				$googlemaps->latitude=$object->latitude;
				$googlemaps->longitude=$object->longitude;
				$googlemaps->address=$addresstosearch;
				$googlemaps->fk_object=$obj->id;
				$googlemaps->type_object=$type;
				$googlemaps->result_code='OK';
				$googlemaps->result_label='';

				if ($googlemaps->id > 0) $result=$googlemaps->update();
				else $result=$googlemaps->create($user);
				if ($result < 0) dol_print_error('',$googlemaps->error);

				$countgeoencodedok++;
				$countgeoencodedall++;
			}
			else
			{
				$error=$point;

				// Update/insert database
				$googlemaps->id=$obj->gid;
				$googlemaps->latitude=$object->latitude;
				$googlemaps->longitude=$object->longitude;
				$googlemaps->address=$addresstosearch;
				$googlemaps->fk_object=$obj->id;
				$googlemaps->type_object=$type;
				if ($error == 'ZERO_RESULTS')
				{
					$error='Address not complete or unknown';
					$googlemaps->result_code='ZERO_RESULTS';
					$googlemaps->result_label=$error;
				}
				else if ($error == 'OVER_QUERY_LIMIT')
				{
					$error='Quota reached';
					$googlemaps->result_code='OVER_QUERY_LIMIT';
					$googlemaps->result_label=$error;
				}
				else
				{
					$googlemaps->result_code=$error;
					$googlemaps->result_label='Geoencoding failed '.$error;
				}

				if ($googlemaps->id > 0) $result=$googlemaps->update();
				else $result=$googlemaps->create($user);
				if ($result < 0) dol_print_error('',$googlemaps->error);

				$object->error_code=$googlemaps->result_code;
				$object->error=$googlemaps->result_label;
				$adderrors[]=$object;

				$countgeoencodedall++;
			}
		}
		else
		{
			if ($obj->result_code == 'OK')	// A success
			{
				$countgeoencodedok++;
				$countgeoencodedall++;
			}
			else if (! empty($obj->result_code))	// An error
			{
				$error=$obj->result_label;
				$object->error_code=$obj->result_code;
				$object->error=$error;
				$adderrors[]=$object;

				$countgeoencodedall++;
			}
			else 	// No geoencoding done yet
			{

			}
		}

		if (! $error)
		{
			$addresses[]=$object;
		}

		$i++;
	}
}
else
{
	dol_print_error($db);
}

$gmap = new GoogleMapAPI();
$gmap->setDivId('test1');
$gmap->setDirectionDivId('route');
$gmap->setEnableWindowZoom(true);
$gmap->setEnableAutomaticCenterZoom(true);
$gmap->setDisplayDirectionFields(true);
$gmap->setClusterer(true);
$gmap->setSize('100%','90vh');
$gmap->setZoom(11);
$gmap->setLang('es');
$gmap->setIconSize(32, 32);
$gmap->setDefaultHideMarker(false);

// Convert array of addresses into the output gmap string
GdtabletMisc::addGMapsMarkers($gmap, $addresses);


$gmap->generate();
echo $gmap->getGoogleMap();

?>

<script type="text/javascript">

	var firstRun = false;
	var locationMarker = null;

	if (navigator.geolocation) {

		navigator.geolocation.watchPosition(function (position) {
			var initialLocation = new google.maps.LatLng(position.coords.latitude, position.coords.longitude);

			if (!locationMarker) {
				locationMarker = new google.maps.Marker({
					position: initialLocation,
					map: map,
					icon: 'http://www.google.com/mapfiles/arrow.png'
				});
			} else {
				locationMarker.setPosition(initialLocation);
			}

			map.setCenter(initialLocation);

			if (firstRun === false) {
				map.setZoom(13);
				firstRun = true;
			}
		}, function(err) {
			console.log(err);
		}, {
			timeout: 5000
		});
	}

</script>
<?php

llxfooter();

$db->close();



/**
 * Geocoding an address (address -> lat,lng)
 * Use API v3.
 * See API doc: https://developers.google.com/maps/documentation/geocoding/#api_key
 * To create a key:
 * Visit the APIs console at https://code.google.com/apis/console and log in with your Google Account.
 * Click "Enable an API" or the Services link from the left-hand menu in the APIs Console, then activate the Geocoding API service.
 * Once the service has been activated, your API key is available from the API Access page, in the Simple API Access section. Geocoding API applications use the Key for server apps.
 *
 * @param 	string 	$address 	An address
 * @return 	mixed				Array(lat, lng) if OK, error message string if KO
 */
function geocoding($address)
{
	global $conf;

	$encodeAddress = urlencode(withoutSpecialChars($address));
	//$url = "http://maps.google.com/maps/geo?q=".$encodeAddress."&output=csv";
	//$url = "http://maps.google.com/maps/api/geocode/json?address=".$encodeAddress."&sensor=false";
	if ($conf->global->GOOGLE_API_SERVERKEY)
	{
		$url = "https://maps.googleapis.com/maps/api/geocode/json?address=".$encodeAddress.($conf->global->GOOGLE_API_SERVERKEY?"&key=".$conf->global->GOOGLE_API_SERVERKEY:"");
	}
	else
	{
		$url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$encodeAddress;
	}
	ini_set("allow_url_open", "1");
	$response = googlegetURLContent($url,'GET');

	if ($response['curl_error_no'])
	{
		$returnstring=$response['curl_error_no'].' '.$response['curl_error_msg'];
		echo "<!-- geocoding : failure to geocode : ".dol_escape_htmltag($encodeAddress)." => " . dol_escape_htmltag($returnstring) . " -->\n";
		return $returnstring;
	}

	$data = json_decode($response['content']);
	if ($data->status == "OK")
	{
		$return=array();
		$return['lat']=$data->results[0]->geometry->location->lat;
		$return['lng']=$data->results[0]->geometry->location->lng;
		return $return;
	}
	else if ($data->status == "OVER_QUERY_LIMIT")
	{
		$returnstring='OVER_QUERY_LIMIT';
		echo "\n<!-- geocoding : called url : ".dol_escape_htmltag($url)." -->\n";
		echo "<!-- geocoding : failure to geocode : ".dol_escape_htmltag($encodeAddress)." => " . dol_escape_htmltag($returnstring) . " -->\n";
		return $returnstring;
	}
	else if ($data->status == "ZERO_RESULTS")
	{
		$returnstring='ZERO_RESULTS';
		echo "\n<!-- geocoding : called url : ".dol_escape_htmltag($url)." -->\n";
		echo "<!-- geocoding : failure to geocode : ".dol_escape_htmltag($encodeAddress)." => " . dol_escape_htmltag($returnstring) . " -->\n";
		return $returnstring;
	}
	else {
		$returnstring='Failed to json_decode result '.$response['content'];
		echo "\n<!-- geocoding : called url : ".dol_escape_htmltag($url)." -->\n";
		echo "<!-- geocoding : failure to geocode : ".dol_escape_htmltag($encodeAddress)." => " . dol_escape_htmltag($returnstring) . " -->\n";
		return $returnstring;
	}
}

/**
 * Remove accentued characters
 *
 * @param string $chaine		The string to treat
 * @param string $remplace_par	The replacement character
 * @return string
 */
function withoutSpecialChars($str, $replaceBy = '_')
{
	$str = htmlentities($str, ENT_NOQUOTES, 'utf-8');
	$str = preg_replace('#&([A-za-z])(?:acute|cedil|circ|grave|orn|ring|slash|th|tilde|uml);#', '\1', $str);
	$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str); // pour les ligatures e.g. '&oelig;'
	$str = preg_replace('#&[^;]+;#', '', $str); // supprime les autres caractères
	return $str;
	/*
		$accents = "ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ";
	$sansAccents = "AAAAAACEEEEIIIIOOOOOUUUUYaaaaaaceeeeiiiioooooouuuuyy";
	$text = strtr($text, $accents, $sansAccents);
	$text = preg_replace('/([^.a-z0-9]+)/i', $replaceBy, $text);
	return $text;*/
}


/**
 * Function get content from an URL (use proxy if proxy defined)
 *
 * @param	string	$url 			URL to call.
 * @param	string	$postorget		'post' = POST, 'get='GET'
 * @return	array					returns an associtive array containing the response from the server.
 */
function googlegetURLContent($url,$postorget='GET',$param='')
{
	//declaring of global variables
	global $conf, $langs;
	$USE_PROXY=empty($conf->global->MAIN_PROXY_USE)?0:$conf->global->MAIN_PROXY_USE;
	$PROXY_HOST=empty($conf->global->MAIN_PROXY_HOST)?0:$conf->global->MAIN_PROXY_HOST;
	$PROXY_PORT=empty($conf->global->MAIN_PROXY_PORT)?0:$conf->global->MAIN_PROXY_PORT;
	$PROXY_USER=empty($conf->global->MAIN_PROXY_USER)?0:$conf->global->MAIN_PROXY_USER;
	$PROXY_PASS=empty($conf->global->MAIN_PROXY_PASS)?0:$conf->global->MAIN_PROXY_PASS;

	dol_syslog("getURLContent postorget=".$postorget." URL=".$url." param=".$param);

	//setting the curl parameters.
	$ch = curl_init();

	/*print $API_Endpoint."-".$API_version."-".$PAYPAL_API_USER."-".$PAYPAL_API_PASSWORD."-".$PAYPAL_API_SIGNATURE."<br>";
	 print $USE_PROXY."-".$gv_ApiErrorURL."<br>";
	print $nvpStr;
	exit;*/
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	curl_setopt($ch, CURLOPT_SSLVERSION, 3); // Force SSLv3

	//turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($conf->global->MAIN_USE_CONNECT_TIMEOUT)?5:$conf->global->MAIN_USE_CONNECT_TIMEOUT);
	curl_setopt($ch, CURLOPT_TIMEOUT, empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT)?5:$conf->global->MAIN_USE_RESPONSE_TIMEOUT);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
	if ($postorget == 'POST') curl_setopt($ch, CURLOPT_POST, 1);
	else curl_setopt($ch, CURLOPT_POST, 0);

	//if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
	if ($USE_PROXY)
	{
		dol_syslog("getURLContent set proxy to ".$PROXY_HOST. ":" . $PROXY_PORT." - ".$PROXY_USER. ":" . $PROXY_PASS);
		//curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Curl 7.10
		curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT);
		if ($PROXY_USER) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $PROXY_USER. ":" . $PROXY_PASS);
	}

	//setting the nvpreq as POST FIELD to curl
	curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

	//getting response from server
	$response = curl_exec($ch);

	$rep=array();
	$rep['content']=$response;
	$rep['curl_error_no']='';
	$rep['curl_error_msg']='';

	//dol_syslog("getURLContent response=".$response);

	if (curl_errno($ch))
	{
		// moving to display page to display curl errors
		$rep['curl_error_no']=curl_errno($ch);
		$rep['curl_error_msg']=curl_error($ch);

		dol_syslog("getURLContent curl_error array is ".join(',',$rep));
	}
	else
	{
		//closing the curl
		curl_close($ch);
	}

	return $rep;
}
?>