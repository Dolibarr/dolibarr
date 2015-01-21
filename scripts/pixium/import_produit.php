<?php
/*
 * Script d'import des produits depuis le fichier fournisseurs_et_produits.csv
 * @author Emmanuel Haguet <ehaguet@teclib.com>
 */
const CSV_FILENAME = "fournisseurs_et_produits.csv";
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
                        WHERE ref LIKE '".$name."\_%'
                        ORDER BY ref desc");
   $obj = $db->fetch_object($resql);
   if ($obj) {
      $num = substr(strstr($obj->ref, '_'), 1);
      $num++;
   } else {
      $num = 0;
   }
   return $name . '_' . str_pad($num, 4, '0', STR_PAD_LEFT);
}

/**
 * multi explode
 * @param array $delimiters
 * @param string $string
 * @return unknown
 */
function multiexplode($delimiters, $string) {
   $ready = str_replace($delimiters, $delimiters[0], $string);
   return explode($delimiters[0], $ready);
}

function constructRefLetter($libelle_produit) {
   $ref = ''; //init
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
      //echo "<tr><td>Nom vide</td></tr>";
      return;
   }
   
   $libelle_produit = $data[2];
   
   $ref_letter = constructRefLetter($libelle_produit);
   $ref = getRefOfProduct($ref_letter);
   
   /*echo "<tr>
            <td>$name</td>
            <td>$libelle_produit</td>
            <td>$ref</td>";*/
   
   $product = new Product($db);
   $product->ref = $ref;
   $product->libelle = $name;
   $product->status = 0; //tosell
   $product->status_buy = 1; //tobuy
   
   $user = new User($db);
   $user->fetch('', USERNAME);
   $returnvalue = $product->create($user);
   //echo "<td>";
   if ($returnvalue >= 0) {
      //echo 'OK';
   } else {
      //echo '<span style="color:red">KO</span>';
   }
   
   //echo "</td></tr>";
}

//$csv = array_map('str_getcsv', file(CSV_FILENAME));

/*
echo "<table>";
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
