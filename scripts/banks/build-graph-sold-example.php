#!/usr/bin/php
<?php
/**
 * Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *		\file       	scripts/banks/build-graph-sold-example.php
 *		\ingroup    	banque
 *		\brief      	Script to build graph of sold for each bank account
 *		\deprecated		Ce script n'est plus utilise car les graphiques sont generes dynamiquement maintenant.
 *		\version		$Id: build-graph-sold-example.php,v 1.11 2011/07/31 22:22:12 eldy Exp $
 */

$sapi_type = php_sapi_name();
$script_file = basename(__FILE__);
$path=dirname(__FILE__).'/';

// Test si mode batch
$sapi_type = php_sapi_name();
if (substr($sapi_type, 0, 3) == 'cgi') {
    echo "Erreur: Vous utilisez l'interpreteur PHP pour le mode CGI. Pour executer graph-solde.php en ligne de commande, vous devez utiliser l'interpreteur PHP pour le mode CLI.\n";
    exit;
}

require_once($path."../../htdocs/master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/dolgraph.class.php");


if (! isset($argv[1]) || ! $argv[1]) {
    print "Usage: ".$script_file." now\n";
    exit;
}
$id=$argv[1];


$error = 0;

// Initialise opt, tableau des parametres
if (function_exists("getopt"))
{
    // getopt existe sur ce PHP
    $opt = getopt("m:y:");
}
else
{
    // getopt n'existe sur ce PHP
    $opt=array('m'=>$argv[1]);
}


// Create output directory
create_exdir($conf->banque->dir_temp);


$datetime = time();

if ($opt['m'] > 0)
{
    $month = $opt['m'];
}
else
{
    $month = strftime("%m", $datetime);
}
$year = strftime("%Y", $datetime);

if ($month == 1)
{
    $monthprev = "12";
    $yearprev = $year - 1;
}
else
{
    $monthprev = substr("00".($month - 1), -2);
    $yearprev = $year ;
}

if ($month == 12)
{
    $monthnext = "01";
    $yearnext = $year + 1;
}
else
{
    $monthnext = substr("00".($month + 1), -2);
}

$sql = "SELECT distinct(fk_account)";
$sql .= " FROM ".MAIN_DB_PREFIX."bank";
$sql .= " WHERE fk_account IS NOT NULL";

$resql = $db->query($sql);

$accounts = array();

if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    while ($i < $num)
    {
        $row = $db->fetch_row($resql);
        array_push($accounts, $row[0]);
        $i++;
    }

}


$width = 750;
$height = 350;


foreach ($accounts as $account)
{
    $datetime = time();
    $year = strftime("%Y", $datetime);
    $month = strftime("%m", $datetime);
    $day = strftime("%d", $datetime);


    // Definition de $width et $height
    $width = 750;
    $height = 280;

    // Calcul de $min et $max
    $sql = "SELECT min(datev), max(datev)";
    $sql.= " FROM ".MAIN_DB_PREFIX."bank";
    $sql.= " WHERE fk_account = ".$account;
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $row = $db->fetch_row($resql);
        $min = $db->jdate($row[0]);
        $max = $db->jdate($row[1]);
    }
    else
    {
        dol_print_error($db);
    }
    //	print strftime("%Y%m%d",$max);

    // Chargement du tableau $amounts
    // TODO peut etre optimise en virant les date_format
    $amounts = array();
    $sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank";
    $sql .= " WHERE fk_account = ".$account;
    $sql .= " AND date_format(datev,'%Y%m') = '".$year.$month."'";
    $sql .= " GROUP BY date_format(datev,'%Y%m%d')";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $row = $db->fetch_row($resql);
            $amounts[$row[0]] = $row[1];
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
    }

    // Calcul de $solde avant le debut du graphe
    $solde = 0;
    $sql = "SELECT sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank";
    $sql .= " WHERE fk_account = ".$account;
    $sql .= " AND datev < '".$year."-".sprintf("%02s",$month)."-01'";
    $resql = $db->query($sql);
    if ($resql)
    {
        $row = $db->fetch_row($resql);
        $solde = $row[0];
    }
    else
    {
        dol_print_error($db);
    }

    // Chargement de labels et datas pour tableau 1
    $labels = array();
    $datas = array();

    $subtotal = 0;

    $day = mktime(1,1,1,$month,1,$year);

    $xmonth = substr("00".strftime("%m",$day), -2);
    $i = 0;
    while ($xmonth == $month)
    {
        //print strftime ("%e %d %m %y",$day)."\n";
        $subtotal = $subtotal + (isset($amounts[strftime("%Y%m%d",$day)]) ? $amounts[strftime("%Y%m%d",$day)] : 0);
        if ($day > time())
        {
            $datas[$i] = ''; // Valeur sp�ciale permettant de ne pas tracer le graph
        }
        else
        {
            $datas[$i] = $solde + $subtotal;
        }
        //$labels[$i] = strftime("%d",$day);
        $labels[$i] = strftime("%d",$day);

        $day += 86400;
        $xmonth = substr("00".strftime("%m",$day), -2);
        $i++;
    }


    // Fabrication tableau 1
    $file= $conf->banque->dir_temp."/solde.$account.$year.$month.png";
    $title=$langs->trans("Balance").' '.$langs->trans("Month").': '.$month.' '.$langs->trans("Year").': '.$year;
    $graph_datas=array();
    foreach($datas as $i => $val)
    {
        $graph_datas[$i]=array("$labels[$i]",$datas[$i]);
    }
    $px = new DolGraph();
    $px->SetData($graph_datas);
    $px->SetLegend(array($langs->trans("Balance")));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetTitle($title);
    $px->SetWidth($width);
    $px->SetHeight($height);
    $px->SetType('lines');
    $px->draw($file);


    // Chargement du tableau $amounts
    // TODO peut etre optimise en virant les date_format
    $amounts = array();
    $sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank";
    $sql .= " WHERE fk_account = ".$account;
    $sql .= " AND date_format(datev,'%Y') = '".$year."'";
    $sql .= " GROUP BY date_format(datev,'%Y%m%d')";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $row = $db->fetch_row($resql);
            $amounts[$row[0]] = $row[1];
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
    }

    // Calcul de $solde avant le debut du graphe
    $solde = 0;
    $sql = "SELECT sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank";
    $sql .= " WHERE fk_account = ".$account;
    $sql .= " AND datev < '".$year."-01-01'";
    $resql = $db->query($sql);
    if ($resql)
    {
        $row = $db->fetch_row($resql);
        $solde = $row[0];
    }
    else
    {
        dol_print_error($db);
    }

    // Chargement de labels et datas pour tableau 2
    $labels = array();
    $datas = array();

    $subtotal = 0;

    $day = mktime(1,1,1,1,1,$year);

    $xyear = strftime("%Y",$day);
    $i = 0;
    while ($xyear == $year)
    {
        $subtotal = $subtotal + (isset($amounts[strftime("%Y%m%d",$day)]) ? $amounts[strftime("%Y%m%d",$day)] : 0);
        //print strftime ("%e %d %m %y",$day)." ".$subtotal."\n<br>";
        if ($day > time())
        {
            $datas[$i] = ''; // Valeur sp�ciale permettant de ne pas tracer le graph
        }
        else
        {
            $datas[$i] = $solde + $subtotal;
        }
        if (strftime("%d",$day) == 15)
        {
            $labels[$i] = strftime("%m",$day);
        }

        $day += 86400;
        $xyear = strftime("%Y",$day);
        $i++;
    }

    // Fabrication tableau 2
    $file= $conf->banque->dir_temp."/solde.$account.$year.png";
    $title=$langs->trans("Balance").' '.$langs->trans("Year").': '.$year;
    $graph_datas=array();
    foreach($datas as $i => $val)
    {
        $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
    }
    $px = new DolGraph();
    $px->SetData($graph_datas);
    $px->SetLegend(array($langs->trans("Balance")));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetTitle($title);
    $px->SetWidth($width);
    $px->SetHeight($height);
    $px->SetType('lines');
    $px->draw($file);


    // Chargement du tableau $amounts
    // TODO peut etre optimise en virant les date_format
    $amounts = array();
    $sql = "SELECT date_format(datev,'%Y%m%d'), sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank";
    $sql .= " WHERE fk_account = ".$account;
    $sql .= " GROUP BY date_format(datev,'%Y%m%d')";
    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $row = $db->fetch_row($resql);
            $amounts[$row[0]] = $row[1];
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
    }

    // Calcul de $solde avant le debut du graphe
    $solde = 0;

    // Chargement de labels et datas pour tableau 3
    $labels = array();
    $datas = array();
    $subtotal = 0;

    $day = $min;

    $i = 0;
    while ($day <= ($max+1000000))	// On va bien au dela du dernier jour
    {
        $subtotal = $subtotal + (isset($amounts[strftime("%Y%m%d",$day)]) ? $amounts[strftime("%Y%m%d",$day)] : 0);
        //print strftime ("%e %d %m %y",$day)." ".$subtotal."\n<br>";
        if ($day > ($max+86400))
        {
            $datas[$i] = ''; // Valeur sp�ciale permettant de ne pas tracer le graph
        }
        else
        {
            $datas[$i] = $solde + $subtotal;
        }
        if (strftime("%d",$day) == 1)
        {
            $labels[$i] = strftime("%m",$day);
        }
        $day += 86400;
        $i++;
    }

    // Fabrication tableau 3
    $file= $conf->banque->dir_temp."/solde.$account.png";
    $title=$langs->trans("Balance");
    $graph_datas=array();
    foreach($datas as $i => $val)
    {
        $graph_datas[$i]=array(isset($labels[$i])?$labels[$i]:'',$datas[$i]);
    }
    $px = new DolGraph();
    $px->SetData($graph_datas);
    $px->SetLegend(array($langs->trans("Balance")));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetTitle($title);
    $px->SetWidth($width);
    $px->SetHeight($height);
    $px->SetType('lines');
    $px->draw($file);


    // Chargement du tableau $credits, $debits
    $credits = array();
    $debits = array();

    $sql = "SELECT date_format(datev,'%m'), sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank";
    $sql .= " WHERE fk_account = ".$account;
    $sql .= " AND date_format(datev,'%Y') = '".$year."'";
    $sql .= " AND amount > 0";
    $sql .= " GROUP BY date_format(datev,'%m');";

    $resql = $db->query($sql);
    if ($resql)
    {
        $num = $db->num_rows($resql);
        $i = 0;

        while ($i < $num)
        {
            $row = $db->fetch_row($resql);
            $credits[$row[0]] = $row[1];
            $i++;
        }
    }
    else
    {
        dol_print_error($db);
    }

    $sql = "SELECT date_format(datev,'%m'), sum(amount)";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank";
    $sql .= " WHERE fk_account = ".$account;
    $sql .= " AND date_format(datev,'%Y') = '".$year."'";
    $sql .= " AND amount < 0";
    $sql .= " GROUP BY date_format(datev,'%m');";

    $resql = $db->query($sql);
    if ($resql)
    {
        while ($row = $db->fetch_row($resql))
        {
            $debits[$row[0]] = abs($row[1]);
        }
    }
    else
    {
        dol_print_error($db);
    }

    // Chargement de labels et data_xxx pour tableau 4
    $labels = array();
    $data_credit = array();
    $data_debit = array();
    for ($i = 0 ; $i < 12 ; $i++)
    {
        $data_credit[$i] = isset($credits[substr("0".($i+1),-2)]) ? $credits[substr("0".($i+1),-2)] : 0;
        $data_debit[$i] = isset($debits[substr("0".($i+1),-2)]) ? $debits[substr("0".($i+1),-2)] : 0;
        $labels[$i] = $i+1;
    }

    // Fabrication tableau 4
    $file= $conf->banque->dir_temp."/mouvement.$account.$year.png";
    $title=$langs->trans("Movements").' '.$langs->trans("Year").': '.$year;
    $graph_datas=array();
    foreach($data_credit as $i => $val)
    {
        $graph_datas[$i]=array($labels[$i],$data_credit[$i],$data_debit[$i]);
    }
    $px = new DolGraph();
    $px->SetData($graph_datas);
    $px->SetLegend(array($langs->trans("Debit"),$langs->trans("Credit")));
    $px->SetMaxValue($px->GetCeilMaxValue());
    $px->SetMinValue($px->GetFloorMinValue());
    $px->SetTitle($title);
    $px->SetWidth($width);
    $px->SetHeight($height);
    $px->SetType('bars');
    $px->SetShading(3);
    $px->draw($file);
}

$db->close();

print 'Graph files generated into directory '.$conf->banque->dir_temp."\n";

?>
