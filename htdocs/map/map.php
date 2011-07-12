<?php

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");

$langs->load("companies");


// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe', $socid);

// Initialization Company Object
$soc = new Societe($db);
$soc->fetch($socid);

$arrayjs=array();

if($conf->map->enabled)
{
    if($conf->global->MAP_SYSTEM=='microsoft')
    {
        //microsoft
        $arrayjs[0]= 'https://dev.virtualearth.net/mapcontrol/mapcontrol.ashx?v=6';
        $arrayjs[1]= '/map/lib/mxn.js?(microsoft)';
    }

    if($conf->global->MAP_SYSTEM=='google')
    {
        //google
        $arrayjs[0]= 'https://maps.google.com/maps?file=api&amp;v=3&amp;sensor=false&amp;key='.$conf->global->GOOGLE_KEY;
        $arrayjs[1]= '/map/lib/mxn.js?(google)';
    }

    if($conf->global->MAP_SYSTEM=='openlayers')
    {
        //open street map
        $arrayjs[0]= 'http://www.openlayers.org/api/OpenLayers.js';
        $arrayjs[1]= '/map/lib/mxn.js?(openlayers)';
    }
                
}


llxHeader('','','','','','',$arrayjs,'');

$head = societe_prepare_head($soc);

dol_fiche_head($head, 'map',$langs->trans("ThirdParty") ,0,'company');



?>

<center>
			<div id="mapdiv" style="z-index: 0; overflow-x: hidden; overflow-y: hidden; position: relative; width: 100%;height: 580px;background-color: rgb(255, 245, 242);"></div>
</center>

<script type="text/javascript">
//<![CDATA[
  function initialize() {

      // create mxn object
      var m = new mxn.Mapstraction('mapdiv','<?php print $conf->global->MAP_SYSTEM; ?>');

      $.ajax({
	url:'<?php print DOL_URL_ROOT; ?>/map/geoservice.php?socid=<?php print $socid ; ?>',
        type:'GET',
        success: function(json){
        	   m.addJSON(json);
        	 }
      });

      m.addControls({zoom:'small'});

      var latlon = new mxn.LatLonPoint(<?php print $soc->lat; ?>,<?php print $soc->lng; ?> );
      // put map on page
      m.setCenterAndZoom(latlon, 13);
  }
  window.onload = initialize;
//]]>
</script> 

<?php
llxFooter('$Date: 2010/06/30 15:39:47 $ - $Revision: 1.23 $');
?>

