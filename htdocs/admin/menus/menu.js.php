<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *		\file       htdocs/admin/menus/menu.js.php
 *		\brief      File for js menu
 *		\version    $Id: menu.js.php,v 1.9 2011/07/31 22:23:15 eldy Exp $
 */


//if (! defined('NOREQUIREUSER')) define('NOREQUIREUSER','1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))   define('NOREQUIREDB','1');	// Not disabled to increase speed. Language code is found on url.
if (! defined('NOREQUIRESOC'))    define('NOREQUIRESOC','1');
//if (! defined('NOREQUIRETRAN')) define('NOREQUIRETRAN','1');	// Not disabled cause need to do translations
if (! defined('NOCSRFCHECK'))     define('NOCSRFCHECK',1);
if (! defined('NOTOKENRENEWAL'))  define('NOTOKENRENEWAL',1);
if (! defined('NOLOGIN'))         define('NOLOGIN',1);
if (! defined('NOREQUIREMENU'))   define('NOREQUIREMENU',1);
if (! defined('NOREQUIREHTML'))   define('NOREQUIREHTML',1);
if (! defined('NOREQUIREAJAX'))   define('NOREQUIREAJAX','1');

session_cache_limiter( FALSE );

require_once("../../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions.lib.php");

// Define css type
header('Content-type: application/javascript');
// Important: Following code is to avoid page request by browser and PHP CPU at
// each Dolibarr page access.
if (empty($dolibarr_nocache)) header('Cache-Control: max-age=3600, public, must-revalidate');
else header('Cache-Control: no-cache');

// On the fly GZIP compression for all pages (if browser support it). Must set the bit 3 of constant to 1.
if (isset($conf->global->MAIN_OPTIMIZE_SPEED) && ($conf->global->MAIN_OPTIMIZE_SPEED & 0x04)) { ob_start("ob_gzhandler"); }

if (GETPOST('lang')) $langs->setDefaultLang(GETPOST('lang'));  // If language was forced on URL
if (GETPOST('theme')) $conf->theme=GETPOST('theme');  // If theme was forced on URL
$langs->load("main",0,0);
?>


// Tests pour navigateurs
var OPE = (window.opera) ? true : false;
var IE  = (document.all && !OPE) ? true : false;
var MOZ = (!IE && !OPE) ? true : false;
// -----------------------------------------------------
// Fonction d'initialisation de l'arbre
function arbre() {
    // Choix de la balise contenant le texte. (strong par defaut).
    balise = "STRONG";
    // Presentation de l'arbre au depart : deployee ('yes') ou fermee ('no')
    extend = "no";
    // Textes du lien plier / deplier
    plier_text = '<?php echo $langs->transnoentities("UndoExpandAll"); ?>';
    plier_title = 'Replier tous les noeuds de l\'arbre'
    deplier_text = '<?php echo $langs->transnoentities("ExpandAll"); ?>';
    deplier_title = 'Deplier tous les noeuds de l\'arbre'
    // Recuperation de tous les arbres de la page
    uls = getElBy('ul','class','arbre');
    for (uli=0;uli < uls.length;uli++)
    {
        ul = uls[uli];
        linkSwitch(ul);
        processULEL(ul);
        plier(ul,'replier');
    }

}

// -------------------------------------------------------
// Creation des liens plier /deplier tout
function linkSwitch(ul) {
    var a=document.createElement('a');
    a.setAttribute('href','#');
    if (extend=='yes') {
        a.appendChild(document.createTextNode(plier_text));
        a.setAttribute('title',plier_title);
    }
    else {
        a.appendChild(document.createTextNode(deplier_text));
        a.setAttribute('title',deplier_title);
    }
    var parbre = document.createElement('p');
    parbre.setAttribute('class','arbre-switch');
    parbre.appendChild(a);
    ul.parentNode.insertBefore(parbre,ul);
    listenlink(ul);
}
// Gestion des Clics sur les liens plier / deplier tout
function listenlink(ul) {
    var link = ul.previousSibling.childNodes[0];
    link.onclick = function() {
        if (this.childNodes[0].nodeValue == plier_text) {
            plier(ul,'replier');
            this.childNodes[0].nodeValue = deplier_text;
            this.setAttribute('title',deplier_title);
        }
        else {
            plier(ul,'deplier');
            this.childNodes[0].nodeValue = plier_text;
            this.setAttribute('title',plier_title);
        }
        return false;
    }
}
// Action Plier / deplier tout
function plier(ul,act) {
    for (var i=0; i < ul.childNodes.length; i++) {
        var li = ul.childNodes[i];
        if (li.nodeName == 'LI') {
            for (var j=0; j < li.childNodes.length; j++) {
                var child = li.childNodes[j];
                if (child.nodeName==balise) {
                    var strong = child;
                }
                if (child.nodeName=='UL') {
                    if (act=='replier') {
                        child.className='hide';
                        strong.className='arbre-plier';
                    }
                    else {
                        child.className='';
                        strong.className='arbre-deplier';
                    }
                    var sub = child;
                    plier(sub,act);
                }
            }
        }
    }
}
// ---------------------------------------------------------
// Analyse de l'arbre
function processULEL(ul) {
    if (!ul.childNodes || ul.childNodes.length == 0) return;
    // Iterate LIs
    for (var itemi=0;itemi < ul.childNodes.length;itemi++) {
        var item = ul.childNodes[itemi];
        if (item.nodeName == "LI") {
            // Contenu des balises LI
            var a;
            var subul;
            subul = "";
            for (var sitemi=0;sitemi < item.childNodes.length;sitemi++) {
                // Uniquement pour moz-firefox
                if (MOZ) {item.style.background = "";}
                // Enfants des li : balise ou sous-ul
                var sitem = item.childNodes[sitemi];
                switch (sitem.nodeName) {
                    case balise:
                        a = sitem;
                        break;
                    case "UL":
                        subul = sitem;
                        if (extend != "yes") {sitem.className = 'hide';}
                        processULEL(subul);
                        break;
                }
            }
            if (subul) {
                if (extend!="yes") {
                    a.className='arbre-plier';
                }
                else {
                    a.className='arbre-deplier';
                    subul.className='';

                }
                associateEL(a,subul);
            }
        }
    }
}
// Swicth des noeuds
function associateEL(a,ul) {
    a.onclick = function () {
        this.className = (ul.className=='hide') ? 'arbre-deplier' : 'arbre-plier';
        ul.className = (ul.className=='hide') ? '' : 'hide';
        return false;
    }
}
// -----------------------------------------------------
// Nom  : GetElBy(tag,attr,val)
// By   : Rui Nibau
// Date : aout 2005
// Func : Tableau des elements 'tag' dont l'attribut 'attr' a la valeur 'val'.
// -----------------------------------------------------
function getElBy(tag,attr,val) {
	var dbRes = [];
	var dbEl = document.getElementsByTagName(tag);
	for (e=0; e < dbEl.length; e++) {
		if (attr == 'class') {if (dbEl[e].className==val) {dbRes.push(dbEl[e]);}}
		else {if (dbEl[e].getAttribute(attr)==val) {dbRes.push(dbEl[e]);}}
	}
	return dbRes;
}
// -----------------------------------------------------
// A l'affichage de la page, lancer la fonction arbre
window.onload = function() {
    arbre();
}

function imgDel(id)
{
	var delId='del'+id;

		var imgDel = document.getElementById('del'+id);
		if (imgDel != null) imgDel.style.display='block';

	return true;
}
