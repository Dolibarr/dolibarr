<?php
/* Copyright (C) - 2020	Andreu Bisquerra Gaya <jove@bisquerra.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/takepos/public/menu.php
 *       \ingroup    takepos
 *       \brief      Public menu for customers
 */

if (!defined("NOLOGIN"))       define("NOLOGIN", '1'); // If this page is public (can be called outside logged session)
if (!defined('NOIPCHECK'))	   define('NOIPCHECK', '1'); // Do not check IP defined into conf $dolibarr_main_restrict_ip

require '../../main.inc.php';

require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

if (!$conf->global->TAKEPOS_QR_MENU) accessforbidden(); // If Restaurant Menu is disabled never allow NO LOGIN access
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?php echo $mysoc->name; ?></title>
  <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/foundation-sites@6.6.3/dist/css/foundation.min.css'>
<link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Khand:400,300,500,600,700'><link rel="stylesheet" href="css/style.css">
<meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body>
<!-- partial:index.partial.html -->
<body>
    <div class="grid-container">
      <div class="grid-x grid-padding-x menu2">
        <div class="cell small-12">
          <h1><?php print $mysoc->name; ?> - <small><?php print $langs->trans('RestaurantMenu'); ?></small></h1>

<?php
$categorie = new Categorie($db);
$categories = $categorie->get_full_arbo('product', (($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0) ? $conf->global->TAKEPOS_ROOT_CATEGORY_ID : 0), 1);
$levelofrootcategory = 0;
if ($conf->global->TAKEPOS_ROOT_CATEGORY_ID > 0)
{
    foreach ($categories as $key => $categorycursor)
    {
        if ($categorycursor['id'] == $conf->global->TAKEPOS_ROOT_CATEGORY_ID)
        {
            $levelofrootcategory = $categorycursor['level'];
            break;
        }
    }
}
$levelofmaincategories = $levelofrootcategory + 1;

$maincategories = array();
$subcategories = array();
foreach ($categories as $key => $categorycursor)
{
    if ($categorycursor['level'] == $levelofmaincategories)
    {
        $maincategories[$key] = $categorycursor;
    } else {
        $subcategories[$key] = $categorycursor;
    }
}

$maincategories = dol_sort_array($maincategories, 'label');

foreach ($maincategories as $cat){
	print '<div class="text-center">
            <a id="'.$cat['id'].'"></a><h3>'.$cat['label'].'</h3>
          </div>
		  <div class="grid-x grid-padding-x">';

	$object = new Categorie($db);
	$result = $object->fetch($cat['id']);
	$prods = $object->getObjectsInCateg("product", 0, 0, 0, $conf->global->TAKEPOS_SORTPRODUCTFIELD, 'ASC');
	foreach ($prods as $pro){
		print '
		<div class="cell small-6 medium-4">
			<div class="item">
                <h4>'.$pro->label.'</h4>
                <span class="dots"></span>
                <span class="price">'.price($pro->price_ttc, 1).'</span>
            </div>
        </div>';
	}
	print '</div>';
}
?>
			</div>
		</div>
    </div>
    <footer class="footer">
      <div class="container">
        <p class="text-muted"><?php print $mysoc->name; ?></p>
      </div>
    </footer>
  </body>
<!-- partial -->
  <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js'></script>
<script src='https://cdn.jsdelivr.net/npm/foundation-sites@6.6.3/dist/js/foundation.min.js'></script><script  src="js/script.js"></script>

</body>
</html>
