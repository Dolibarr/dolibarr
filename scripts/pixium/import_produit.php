<?php
/*
 * Script d'import des produits depuis le fichier data.csv
 * @author Emmanuel Haguet <ehaguet@teclib.com>
 */
const CSV_FILENAME = "fournisseurs_et_produit.csv";
const CSV_SEPARATOR = ',';
const NB_MIN_COLONNE = 5;
const USERNAME = 'dolibarr';

$path=dirname(__FILE__).'/';

require($path."../../htdocs/master.inc.php");
require($path."../../htdocs/product/class/product.class.php");
require_once (DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");

$langs->load('main');

function getRefOfProduct($name) {
   global $db;
   $resql = $db->query("SELECT *
                        FROM ".MAIN_DB_PREFIX."product
                        WHERE ref LIKE '".$name."_%'
                        ORDER BY ref desc");
   $obj = $db->fetch_object($resql);
   return $name . '_' . ($obj) ? (substr(strstr($obj->ref, '_'), 1) +1) : 0;
}

/**
 * multi explode
 * @param array $delimiters
 * @param string $string
 * @return unknown
 */
function multiexplode($delimiters, $string) {
   $ready = str_replace($delimiters, $delimiters[0], $string);
   $launch = explode($delimiters[0], $ready);
   return $launch;
}

function constructRef($libelle_produit) {
   $ref = '';
   $i = 0;
   $parts_ref = multiexplode(array(' ', '-'), $libelle_produit);
   foreach ($parts_ref as $part_ref) {
      if ($i < 3) {
         $ref .= substr(preg_replace('/[^a-za-zA-Z]/', '', $part_ref), 0, 2);
      }
      $i++;
   }
   return strtoupper($ref);
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
   
   $libelle_produit = $data[2];
   
   $ref = getRefOfProduct(constructRef($libelle_produit));
   
   /*echo "<tr>
            <td>$name</td>
            <td>$libelle_produit</td>
            <td>$ref</td>
         </tr>";*/
   
   $product = new Product($db);
   $product->ref = $ref;
   $product->libelle = $name;
   
   //Important : tosell, tobuy
   $product->status = 0;
   $product->status_buy = 1;
   
   $user = new User($db);
   $user->fetch('', USERNAME);
   $returnvalue = $product->create($user);
   if ($returnvalue >= 0) {
      echo 'OK ';
   } else {
      //TODO : Meilleur gestion des messages d'erreur
      echo '<span style="color:red">KO</span> ';
   }
}

//$csv = array_map('str_getcsv', file(CSV_FILENAME));

/*echo "<table>";
echo "<tr>
         <td>name</td>
         <td>libelle_produit</td>
         <td>ref</td>
      </tr>";*/

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

/* echo "</table>"; */
