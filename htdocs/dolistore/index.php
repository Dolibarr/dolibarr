<?php
/*
 * Copyright (C) 2017		 Oscss-Shop       <support@oscss-shop.fr>.
 *
 * This program is free software; you can redistribute it and/or modifyion 2.0 (the "License");
 * it under the terms of the GNU General Public License as published bypliance with the License.
 * the Free Software Foundation; either version 3 of the License, or
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

if (!defined('REQUIRE_JQUERY_BLOCKUI')) define('REQUIRE_JQUERY_BLOCKUI', 1);
/**
 *      \file       htdocs/commande/info.php
 *      \ingroup    commande
 * 		\brief      Page des informations d'une commande
 */
$res = 0;
if (!$res && file_exists("../main.inc.php")) $res = @include("../main.inc.php");
if (!$res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (!$res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (!$res && file_exists("../../../../main.inc.php")) $res = @include("../../../../main.inc.php");
if (!$res && file_exists("../../../dolibarr/htdocs/main.inc.php"))
        $res = @include("../../../dolibarr/htdocs/main.inc.php");     // Used on dev env only
if (!$res && file_exists("../../../../dolibarr/htdocs/main.inc.php"))
        $res = @include("../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res && file_exists("../../../../../dolibarr/htdocs/main.inc.php"))
        $res = @include("../../../../../dolibarr/htdocs/main.inc.php");   // Used on dev env only
if (!$res) die("Include of main fails");

// CORE

global $lang, $user, $conf;


dol_include_once('/dolistore/class/dolistore.class.php');
$options              = array();
$options['per_page']  = 20;
$options['categorie'] = GETPOST('categorie', 'int') + 0;
$options['start']     = GETPOST('start', 'int') + 0;
$options['end']       = GETPOST('end', 'int') + 0;
$options['search']    = GETPOST('search_keyword', 'alpha');
$dolistore            = new Dolistore($options);
/*
 * View
 */

/* * *************************************************
 * VIEW
 *
 * Put here all code to build page
 * ************************************************** */

// llxHeader('', iconv(iconv_get_encoding($langs->trans($lbl_folder)), $character_set_client . "//TRANSLIT", $langs->trans($lbl_folder)) . ' (' . $info->Nmsgs . ') ', '');

$morejs  = array("/dolistore/js/dolistore.js.php",
    "/dolistore/js/fancybox/jquery.fancybox.pack.js",
    "/dolistore/js/fancybox/helpers/jquery.fancybox-thumbs.js");
$morecss = array("/dolistore/css/dolistore.css",
    "/dolistore/js/fancybox/jquery.fancybox.css",
    "/dolistore/js/fancybox/helpers/jquery.fancybox-thumbs.css");
llxHeader('', $langs->trans('DOLISTOREMENU'), '', '', '', '', $morejs, $morecss, 0, 0);
?><div class="fiche"> <!-- begin div class="fiche" -->
    <table summary="" class="centpercent notopnoleftnoright" style="margin-bottom: 2px;">
        <tbody><tr><td class="nobordernopadding widthpictotitle" valign="middle">
                    <img src="<?= dol_buildpath('/dolistore/img/dolistore.png', 2) ?>" alt="" title="" id="pictotitle" border="0" >
                </td>
                <td class="nobordernopadding" valign="middle">
                    <div class="titre"><?= $langs->trans('Extensions disponibles sur le Dolistore') ?></div>
                </td></tr></tbody></table>
    <?= $langs->trans('DOLISTOREdescriptionLong') ?><br><br>

    <div class="tabBar">
        <form method="GET" id="searchFormList" action="<?= $dolistore->url ?>">
            <div class="divsearchfield"><?= $langs->trans('Mot-cle') ?>:
                <input name="search_keyword" placeholder="<?= $langs->trans('Chercher un module') ?>" id="search_keyword" type="text" size="50" value="<?= $options['search'] ?>"><br>
            </div>
            <div class="divsearchfield">
                <input class="button butAction searchDolistore" value="<?= $langs->trans('Rechercher') ?>" type="submit">
                <a class="button butActionDelete" href="<?= $dolistore->url ?>"><?= $langs->trans('Tout afficher') ?></a>
            </div><br><br><br style="clear: both">
        </form>
    </div>
    <div id="category-tree-left">
        <ul class="tree">
            <?= $dolistore->get_categories(); ?>
        </ul>
    </div>
    <div id="listing-content">
        <table summary="list_of_modules" id="list_of_modules" class="liste" width="100%">
            <thead>
                <tr class="liste_titre">
                    <td colspan="100%"><?= $dolistore->get_previous_link() ?> <?= $dolistore->get_next_link() ?> <span style="float:right"><?= $langs->trans('AchatTelechargement') ?></span></td>
                </tr>
            </thead>
            <tbody id="listOfModules">
                <?= $dolistore->get_products($categorie); ?>
            </tbody>
            <tfoot>
                <tr class="liste_titre">
                    <td colspan="100%"><?= $dolistore->get_previous_link() ?> <?= $dolistore->get_next_link() ?> <span style="float:right"><?= $langs->trans('AchatTelechargement') ?></span></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php
llxFooter();
