<?php
/*
 * Script d'import des fournisseurs depuis le fichier data.csv
 * @author Emmanuel Haguet <ehaguet@teclib.com>
 */
const CSV_FILENAME = "fournisseurs_et_produits.csv";
const CSV_SEPARATOR = ',';
const NB_MIN_COLONNE = 5;
const USERNAME = 'dolibarr';

$path=dirname(__FILE__).'/';

require($path."../../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");

$langs->load('main');

function getCountryId($country) {
   $tab = array(
         'texas' => 'United States',
         'USA' => 'United States',
         'Suisse' => 'Swaziland',
         'Netherlands' => 'Nerderland',
         'UK' => 'United Kingdom',
         'Deutschland' => 'Germany',
         'Jordan' => 'Jordanie',
         '' => '-',
         );
   if (isset($tab[$country])) {
      $country = $tab[$country];
   }
   
   $countryid = getCountry('', 3, 0, '', 1, $country);
   
   if ($countryid == 'NotDefined') { //or not int
      return 0;
   }
   return $countryid;
}

function getStateId($name='') {
   global $db;
   
   if ($name != 'Luxembourg') {
      $resql = $db->query("SELECT rowid
                           FROM ".MAIN_DB_PREFIX."c_departements
                           WHERE nom='".$name."' AND active=1");
      $obj = $db->fetch_object($resql);
      if ($obj) {
         return $obj->rowid;
      }
   }
   return "NotDefined";
}

function import($data) {
   global $db;
   
   $colonne = count($data);
   if ($colonne <= NB_MIN_COLONNE) { // security
      exit;
   }
   $name = ucfirst($data[1]);
   if (empty($name)) {
      return;
   }
   
   $user = new User($db);
   $user->fetch('', USERNAME);
   
   $address = ucfirst($data[$colonne -6]);
   $zip     = $data[$colonne -5];
   $town    = ucfirst($data[$colonne -4]);
   $country = $data[$colonne -3];
   $phone   = str_replace("'", "", $data[$colonne -2]);
   $fax     = str_replace("'", "", $data[$colonne -1]);
   
   //echo "name : $name, address : $address, zip : $zip, city : $city, country : $country, tel : $tel, fax : $fax<br>";
   //echo "<tr><td>$name</td><td>$address</td><td>$zip</td><td>$city<td></td><td>$country</td><td>$tel</td><td>$fax</td></tr>";
   
   $tmpsociete = new Societe($db);
   $tmpsociete->name = $name;
   if ($town != 'NA') {
      $tmpsociete->town = $town;
   }
   if ($zip != 'NA') {
      $tmpsociete->zip = $zip;
   }
   
   if ($address != 'NA') {
   $tmpsociete->address = $address;
   }
   
   if ($phone != 'NA') {
      $tmpsociete->phone = $phone;
   }
   
   if ($fax != 'NA') {
      $tmpsociete->fax = $fax;
   }
   $tmpsociete->country_id = getCountryId($country);
   
   $returnvalue = $tmpsociete->create($user);
   if ($returnvalue >= 0) {
      //if ($country == 'texas') {
         $state_id = getStateId($country);
         if ($state_id != 'NotDefined') {
            $tmpsociete->state_id = getStateId($country);
            $tmpsociete->update($tmpsociete->id, $user);
         }
      //}
   } else {
      echo 'KO ';
   }
}

//$csv = array_map('str_getcsv', file(CSV_FILENAME));

//echo "<table>";

$row = 1;
if (($handle = fopen($path . CSV_FILENAME, "r")) !== FALSE) {
   while (($data = fgetcsv($handle, 1000, CSV_SEPARATOR)) !== FALSE) {
      $num = count($data);
      //echo "<p> $num champs à la ligne $row: <br /></p>\n";
      if ($row != 1 && $row != 2) {
         import($data);
      }
      $row++;
      for ($c=0; $c < $num; $c++) {
         //echo $data[$c] . "<br />\n";
      }
   }
   fclose($handle);
}

//echo "</table>";
