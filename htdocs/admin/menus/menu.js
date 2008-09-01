/* -----------------------------------------------------

-------------------------------------------------------- */
// Tests pour navigateurs
var OPE = (window.opera) ? true : false;
var IE  = (document.all && !OPE) ? true : false;
var MOZ = (!IE && !OPE) ? true : false;
// -----------------------------------------------------
// Fonction d'initialisation de l'arbre
function arbre() {
    // Choix de la balise contenant le texte. <strong> par defaut.
    balise = "STRONG";
    // Presentation de l'arbre au depart : deployee ('yes') ou fermee ('no')
    extend = "no";
    // Textes du lien plier / deplier
    plier_text = 'Replier tout';
    plier_title = 'Replier tous les noeuds de l\'arbre'
    deplier_text = 'Deplier tout';
    deplier_title = 'Deplier tous les noeuds de l\'arbre'
    // Recuperation de tous les arbres de la page
    uls = getElBy('ul','class','arbre');
    for (uli=0;uli<uls.length;uli++) {
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
    for (var i=0; i<ul.childNodes.length; i++) {
        var li = ul.childNodes[i];
        if (li.nodeName == 'LI') {
            for (var j=0; j<li.childNodes.length; j++) {
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
    for (var itemi=0;itemi<ul.childNodes.length;itemi++) {
        var item = ul.childNodes[itemi];
        if (item.nodeName == "LI") {
            // Contenu des balises LI
            var a;
            var subul;
            subul = "";
            for (var sitemi=0;sitemi<item.childNodes.length;sitemi++) {
                // Uniquement pour moz-firefox
                if (MOZ) {item.style.background = "url(./images/arbre-trait.png) repeat-y 0 0";}
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
	for (e=0; e<dbEl.length; e++) {
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
