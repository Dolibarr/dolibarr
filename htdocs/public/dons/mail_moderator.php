<?PHP
$subject = "EUCD.INFO Nouvelle promesse de don de $don->amount euros";

$body ="
Une nouvelle promesse de don à valider :


Prénom:  $don->prenom
Nom:     $don->nom
Montant: $don->amount

http://dolibarr.fsffrance.org/compta/dons/

";
?>
