<?php
require("../main.inc.php");

$socid=$_GET['socid'];

?>
{
	features: [
<?php
$resql = $db->query("SELECT rowid, address, ville ,nom , latitude, longitude FROM ".MAIN_DB_PREFIX."societe WHERE longitude is NOT NULL AND latitude IS NOT NULL AND fk_stcomm > 0 AND rowid !=".$socid );
if ($resql)
{
	$nump = $db->num_rows($resql);
        if ($nump)
        {
	        $i = 0;
                while ($i < $nump)
                {
        	        $obj = $db->fetch_object($resql);
?>

	{
            "type": "Feature",
            "toponym": null,
            "title": "<?php print '<a href=\"'.DOL_URL_ROOT."/societe/soc.php?socid=".$obj->rowid.'\">'.$obj->nom.'</a>'; ?>",
            "author": "",
            "id": <?php echo $obj->rowid; ?>,
            "description": "<?php /*print $obj->address.'<br/>*/'<b>'.$obj->ville.'</b>'; ?>",
            "categories": "",
            "geometry": {
                "type": "Point",
                "coordinates": [<?php echo $obj->longitude; ?>,<?php echo $obj->latitude; ?>]
            },
            "icon_shadow": "",
            "icon_shadow_size": [0,0],
		
            "icon_size": [32,32],

            "icon": "<?php if( $obj->rowid == $socid ) print DOL_URL_ROOT."/theme/".$conf->theme."/img/red-dot.png"; else print DOL_URL_ROOT."/theme/".$conf->theme."/img/green-dot.png"; ?>",
            "line_opacity": 1.0,
            "line_width": 1.0,
            "poly_color": "",
            "source_id": <?php echo $obj->rowid; ?>
        }
<?php
                         print ",";
                        $i++;
			
		}
                $resql = $db->query("SELECT rowid, address, ville ,nom , latitude, longitude FROM ".MAIN_DB_PREFIX."societe WHERE longitude is NOT NULL AND latitude IS NOT NULL AND rowid=".$socid );
if ($resql)
{
	$nump = $db->num_rows($resql);
        if ($nump)
        {
	        $i = 0;
                while ($i < $nump)
                {
        	        $obj = $db->fetch_object($resql);
?>

	{
            "type": "Feature",
            "toponym": null,
            "title": "<?php print '<a href=\"'.DOL_URL_ROOT."/societe/soc.php?socid=".$obj->rowid.'\">'.$obj->nom.'</a>'; ?>",
            "author": "",
            "id": <?php echo $obj->rowid; ?>,
            "description": "<?php /*print $obj->address.'<br/>*/'<b>'.$obj->ville.'</b>'; ?>",
            "categories": "",
            "geometry": {
                "type": "Point",
                "coordinates": [<?php echo $obj->longitude; ?>,<?php echo $obj->latitude; ?>]
            },
            "icon_shadow": "",
            "icon_shadow_size": [0,0],

            "icon_size": [32,32],

            "icon": "<?php if( $obj->rowid == $socid ) print DOL_URL_ROOT."/theme/".$conf->theme."/img/red-dot.png"; else print DOL_URL_ROOT."/theme/".$conf->theme."/img/green-dot.png"; ?>",
            "line_opacity": 1.0,
            "line_width": 1.0,
            "poly_color": "",
            "source_id": <?php echo $obj->rowid; ?>
        }
<?php
                         print ",";
                        $i++;

		}
        }
}
        }
        else
        {
		print '"error"';
        }
}

?>
	]
}
