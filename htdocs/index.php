<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 *
 * $Id$
 * $Source$
 *
 */


/**
        \file       htdocs/index.php
        \brief      Page accueil par defaut
        \version    $Revision$
*/

require("./pre.inc.php");

$user->getrights('');


// Simule le menu par défaut sur Home
if (! isset($_GET["mainmenu"])) $_GET["mainmenu"]="home";


llxHeader();



print_fiche_titre($langs->trans("HomeArea"));


if (defined("MAIN_MOTD") && strlen(trim(MAIN_MOTD)))
{
    print '<table width="100%" class="notopnoleftnoright"><tr><td>';
    print nl2br(MAIN_MOTD);
    print '</td></tr></table><br>';
}

// Affiche warning répertoire install existe (si utilisateur admin)
if ($user->admin && ! defined("MAIN_REMOVE_INSTALL_WARNING"))
{
    if (is_dir(DOL_DOCUMENT_ROOT."/install")) 
    {
        $langs->load("other");
        print '<table width="100%"><tr><td>';
        print '<div class="warning">'.$langs->trans("WarningInstallDirExists",DOL_DOCUMENT_ROOT."/install").' ';
        print $langs->trans("WarningUntilDirRemoved",DOL_DOCUMENT_ROOT."/install").'</div>';   
        print '</td></tr></table>';
        print "<br>\n";
    }
}

print '<table width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" class="notopnoleft">';


/*
 * Informations
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Informations").'</td></tr>';
print '<tr '.$bc[false].'>';
$userstring=$user->fullname;
print '<td nowrap>'.$langs->trans("User").'</td><td>'.$userstring.'</td></tr>';
print '<tr '.$bc[true].'>';
print '<td nowrap>'.$langs->trans("LastAccess").'</td><td>';
if ($user->datelastaccess) print dolibarr_print_date($user->datelastaccess,"%d %B %Y %H:%M:%S");
else print $langs->trans("Unknown");
print '</td>';
print '</tr>';
print '</table>';


/*
 * Dolibarr State Board
 */
print '<br>';
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("DolibarrStateBoard").'</td>';
print '<td align="right">&nbsp;</td>';
print '</tr>';

$var=true;

// Nbre de sociétés clients/prospects
if ($conf->societe->enabled  && $user->rights->societe->lire )
{
  include_once("./client.class.php");
  $board=new Client($db);
  $board->load_state_board();
  
  foreach($board->nb as $key=>$val)
    {
      $var=!$var;
      print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Customers"),"company").'</td>';
      print '<td>';
      if ($key == "customers") print $langs->trans("Customers");
      if ($key == "prospects") print $langs->trans("Prospects");
      print '</td>';
      print '<td align="right">';
      if ($key == "customers") print '<a href="'.DOL_URL_ROOT.'/comm/clients.php">';
      if ($key == "prospects") print '<a href="'.DOL_URL_ROOT.'/comm/prospect/prospects.php">';
      print $val;
      print '</a></td>';
      print '</tr>';
    }
}

// Nbre de sociétés fournisseurs
if ($conf->fournisseur->enabled)
{
    include_once("./fourn/fournisseur.class.php");
    $board=new Fournisseur($db);
    $board->load_state_board();

    foreach($board->nb as $key=>$val)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Suppliers"),"company").'</td>';
        print '<td>';
        if ($key == "suppliers") print $langs->trans("Suppliers");
        print '</td>';
        print '<td align="right">';
        if ($key == "suppliers") print '<a href="'.DOL_URL_ROOT.'/fourn/liste.php">';
        print $val;
        print '</a></td>';
        print '</tr>';
    }
}

// Nbre d'adhérents
if ($conf->adherent->enabled)
{
    include_once("./adherents/adherent.class.php");
    $board=new Adherent($db);
    $board->load_state_board();

    foreach($board->nb as $key=>$val)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Adherent"),"user").'</td>';
        print '<td>';
        if ($key == "members") print $langs->trans("Adherents");
        print '</td>';
        print '<td align="right">';
        if ($key == "members") print '<a href="'.DOL_URL_ROOT.'/adherents/liste.php?statut=1&amp;mainmenu=members">';
        print $val;
        print '</a></td>';
        print '</tr>';
    }
}

// Nbre de produits
if ($conf->produit->enabled)
{
    //include_once("./product.class.php");
    $board=new Product($db);
    $board->load_state_board();

    foreach($board->nb as $key=>$val)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Products"),"product").'</td>';
        print '<td>';
        if ($key == "products") print $langs->trans("Products");
        print '</td>';
        print '<td align="right">';
        if ($key == "products") print '<a href="'.DOL_URL_ROOT.'/product/liste.php?type=0&amp;mainmenu=products">';
        print $val;
        print '</a></td>';
        print '</tr>';
    }
}

// Nbre de services
if ($conf->service->enabled)
{
    include_once("./service.class.php");
    $board=new Service($db);
    $board->load_state_board();

    foreach($board->nb as $key=>$val)
    {
        $var=!$var;
        print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Services"),"service").'</td>';
        print '<td>';
        if ($key == "services") print $langs->trans("Services");
        print '</td>';
        print '<td align="right">';
        if ($key == "services") print '<a href="'.DOL_URL_ROOT.'/product/liste.php?type=1&amp;mainmenu=products">';
        print $val;
        print '</a></td>';
        print '</tr>';
    }
}

print '</table>';


print '</td><td width="65%" valign="top" class="notopnoleftnoright">';


/*
 * Dolibarr Work Board
 */
print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print '<td colspan="2">'.$langs->trans("DolibarrWorkBoard").'</td>';
print '<td align="right">'.$langs->trans("Number").'</td>';
print '<td align="right">'.$langs->trans("Late").'</td>';
print '<td>&nbsp;</td>';
print '<td width="20">&nbsp;</td>';
print '</tr>';

$var=true;

// Nbre actions à faire (en retard)
if ($conf->commercial->enabled || $conf->compta->enabled)
{
    include_once("./actioncomm.class.php");
    $board=new ActionComm($db);
    $board->load_board();
    $board->warning_delay=$conf->actions->warning_delay/60/60/24;
    $board->label=$langs->trans("ActionsToDo");

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Actions"),"task").'</td><td>'.$board->label.'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/action/index.php?status=todo">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/comm/action/index.php?status=todo">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($board->warning_delay).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre commandes clients à honorer
if ($conf->commande->enabled && $user->rights->commande->lire)
{
    include_once(DOL_DOCUMENT_ROOT."/commande/commande.class.php");
    $board=new Commande($db);
    $board->load_board();

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Orders"),"order").'</td><td>'.$langs->trans("OrdersToProcess").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/commande/liste.php">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/commande/liste.php">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->commande->traitement->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre propales ouvertes (expirées)
if ($conf->propal->enabled && $user->rights->propale->lire)
{
    include_once("./propal.class.php");
    $board=new Propal($db);
    $board->load_board("opened");

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Propals"),"propal").'</td><td>'.$langs->trans("PropalsToClose").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=1">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=1">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->propal->cloture->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre propales fermées signées (à facturer)
if ($conf->propal->enabled && $user->rights->propale->lire)
{
    include_once("./propal.class.php");
    $board=new Propal($db);
    $board->load_board("signed");

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Propals"),"propal").'</td><td>'.$langs->trans("PropalsToBill").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=2">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/comm/propal.php?viewstatut=2">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->propal->facturation->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre services à activer (en retard)
if ($conf->contrat->enabled && $user->rights->contrat->lire)
{
    $langs->load("contracts");
    
    include_once("./contrat/contrat.class.php");
    $board=new Contrat($db);
    $board->load_board("inactives");

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Contract"),"contract").'</td><td>'.$langs->trans("BoardNotActivatedServices").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=0">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=0">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->contrat->services->inactifs->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre services actifs (à renouveler)
if ($conf->contrat->enabled && $user->rights->contrat->lire)
{
    $langs->load("contracts");

    include_once("./contrat/contrat.class.php");
    $board=new Contrat($db);
    $board->load_board("expired");

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Contract"),"contract").'</td><td>'.$langs->trans("BoardRunningServices").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=4">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/contrat/services.php?mainmenu=commercial&leftmenu=contracts&mode=4">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->contrat->services->expires->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre factures fournisseurs (à payer)
if ($conf->fournisseur->enabled && $conf->facture->enabled && $user->rights->facture->lire)
{
    $langs->load("bills");
    
    include_once("./fourn/fournisseur.facture.class.php");
    $board=new FactureFournisseur($db);
    $board->load_board();

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Bills"),"bill").'</td><td>'.$langs->trans("SupplierBillsToPay").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/fourn/facture/index.php?filtre=paye:0">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/fourn/facture/index.php?filtre=paye:0">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->facture->fournisseur->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre factures clients (à payer)
if ($conf->facture->enabled && $user->rights->facture->lire)
{
    include_once("./facture.class.php");
    $board=new Facture($db);
    $board->load_board();

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Bills"),"bill").'</td><td>'.$langs->trans("CustomerBillsUnpayed").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/compta/facture/impayees.php">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->facture->client->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre ecritures à rapprocher
if ($conf->banque->enabled && $user->rights->banque->lire)
{
    $langs->load("banks");

    include_once(DOL_DOCUMENT_ROOT."/compta/bank/account.class.php");
    $board=new Account($db);
    $board->load_board();

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("TransactionsToConciliate"),"payment").'</td><td>'.$langs->trans("TransactionsToConciliate").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/compta/bank/index.php?leftmenu=bank&mainmenu=bank">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php?leftmenu=bank&mainmenu=bank">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->bank->rappro->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

// Nbre adhérent valides (attente cotisation)
if ($conf->adherent->enabled && $user->rights->adherent->lire)
{
    $langs->load("members");

    include_once("./adherents/adherent.class.php");
    $board=new Adherent($db);
    $board->load_board();

    $var=!$var;
    print '<tr '.$bc[$var].'><td width="16">'.img_object($langs->trans("Members"),"user").'</td><td>'.$langs->trans("Members").'</td>';
    print '<td align="right"><a href="'.DOL_URL_ROOT.'/adherents/liste.php?mainmenu=members&statut=1">'.$board->nbtodo.'</a></td>';
    print '<td align="right">';
    print '<a href="'.DOL_URL_ROOT.'/adherents/liste.php?mainmenu=members&statut=1">';
    print $board->nbtodolate;
    print '</a></td><td nowrap align="right">';
    print ' (>'.ceil($conf->adherent->cotisation->warning_delay/60/60/24).' '.$langs->trans("days").')';
    print '</td>';
    print '<td>';
    if ($board->nbtodolate > 0) print img_picto($langs->trans("Late"),"warning");
    else print '&nbsp;';
    print '</td>';
    print '</tr>';
}

print '</table>';


print '</td></tr></table>'; 


/*
 * Affichage des boites
 *
 */
include_once("./boxes.php");
$infobox=new InfoBox($db);
$boxes=$infobox->listboxes("0");       // 0 = valeur pour la page accueil

$NBCOLS=2;      // Nombre de colonnes pour les boites

if (sizeof($boxes))
{
    print '<br>';
    print_fiche_titre($langs->trans("OtherInformationsBoxes"));
    print '<table width="100%" class="notopnoleftnoright">';
}
for ($ii=0, $ni=sizeof($boxes); $ii<$ni; $ii++)
{
  if ($ii % $NBCOLS == 0) print "<tr>\n";
  print '<td valign="top" width="50%">';

  // Affichage boite ii
  include_once(DOL_DOCUMENT_ROOT."/includes/boxes/".$boxes[$ii].".php");
  $box=new $boxes[$ii]();
  $box->loadBox();
  $box->showBox();

  print "</td>";
  if ($ii % $NBCOLS == ($NBCOLS-1)) print "</tr>\n";
}
if (sizeof($boxes))
{
    if ($ii % $NBCOLS == ($NBCOLS-1)) print "</tr>\n";
    print "</table>";
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>










