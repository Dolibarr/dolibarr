<?PHP
$subject = "Subject: EUCD.INFO promesse de don de $don->amount euros pour sauver la copie privée";

$body ="
EUCD.INFO  vous  remercie  de  la  promesse  de  don  que  vous  venez
d'enregistrer:

Prénom: $don->prenom
Nom: $don->nom
Montant: $don->amount
Au plus tard le: $date_limite

L'initiative EUCD.INFO[1]  a pour objet de  préserver l'intérêt public
menacé  par  la   transposition  de  la  directive  du   22  mai  2001
(EUCD)[2]. Pour y parvenir elle entend:

    * Produire et proposer un avant-projet de loi alternatif.
    * Le promouvoir auprès des personnes responsables.
    * Entrer dans le cercle de consultation.

Votre  contribution  financière  servira  principalement à  payer  des
juristes dont les tâches seront d'analyser la situation et de proposer
un avant-projet  de loi transposant  la directive dans le  respect des
droits des utilisateurs et du public.

Vous pourrez suivre  les progrès de notre action  grâce à l'échéancier
que nous remettons  régulièrement à jour sur la page  de garde du site
http://eucd.info/. N'hésitez  pas à nous poser des  questions par mail
si vous le souhaitez, à l'adresse contact@eucd.info.

L'association loi 1901 FSF France est d'intérêt général[3] et les dons
qui lui sont fait ouvrent  droit à une réduction d'impôt. Vous pourrez
en bénéficier grâce au reçu[4]  qui vous sera adressé dès reception du
montant promis de $don->amount euros.


[1] EUCD.INFO: http://eucd.info/
[2] Directive du 22 mai 2001:
    http://europa.eu.int/smartapi/cgi/sga_doc?smartapi!celexapi!prod!CELEXnumdoc&lg=fr&numdoc=32001L0029&model=guichett
[3] FSF France et dons: http://france.fsfeurope.org/donations/
[4] Modèle de reçu: http://france.fsfeurope.org/donations/formulaire.fr.html
";
?>
