<?php
/* Copyright (C) 2022      Brice
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *  \file       htdocs/core/triggers/interface_70_modpropale.MailPropalSigne.class.php
 *  \ingroup    core
 *  \brief      Trigger pour notif lors de changement d'état par signature en ligne d'une proposition commercial
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 *  Class of triggers for MyModule module
 */

class InterfaceMailPropalSigne extends DolibarrTriggers
{
	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->name = preg_replace('/^Interface/i', '', get_class($this));
		$this->family = "notification";
		$this->description = "Trigger permettant la notification lors de la signature d'un devis en ligne.";
		// 'development', 'experimental', 'dolibarr' or version
		$this->version = self::VERSION_DOLIBARR;
		$this->picto = 'email';
	}

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		//Définition du numéro d'identifiant du devis
		$idPropal=$object->id;

		//Le devis est signe.

			//Mail au commercial
			/*
			*Definition de l'adresse du commercial en charge du devis
			*fk_c_type_contact`=31 indique que nous rechercher le "Commercial suivi proposition" indiqué dans "Contacts/Adresses" de la fiche du devis.
			*/

		if ($action == 'PROPAL_CLOSE_SIGNED_ONLINE') {
			$sql="SELECT `lastname`,`firstname`,`email` FROM ".MAIN_DB_PREFIX."user WHERE `rowid` IN (SELECT `fk_socpeople` FROM ".MAIN_DB_PREFIX."element_contact WHERE `element_id`=$idPropal AND `fk_c_type_contact`=31)";
			$resql = $this->db->query($sql);
			$obj = $this->db->fetch_object($resql);
			$liste_commercial_lastname = array($obj->lastname);
			$liste_commercial_firstname = array($obj->firstname);
			$liste_commercial_email = array($obj->email);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 1;
				//Création de la liste du ou des commercial/commerciaux.
				while ($i < $num) {
					$row = $this->db->fetch_row($resql);
					array_push($liste_commercial_lastname, $row['0']);
					array_push($liste_commercial_firstname, $row['1']);
					array_push($liste_commercial_email, $row['2']);
					$i++;
				}
				//Boucle d'envoie des emails a chaque commercial définie plus haut.
				$c=0;
				foreach ($liste_commercial_email as $emailcommercial) {
					$subject = $conf->global->MAIN_INFO_SOCIETE_NOM." - Devis " .$object->ref;
					$filepath = array(DOL_DATA_ROOT."/".$object->last_main_doc);
					$mimetype = array("application/pdf");
					$filename = array($object->ref.'_Signe.pdf');
					$messagecommercial = "Bonjour ".$liste_commercial_firstname[$c].",<br><br>Le devis ".$object->ref." (ci-joint) vient d'être signé en ligne.<br><br>Cordialement<br>".$conf->global->MAIN_INFO_SOCIETE_NOM;
					$sendto = $liste_commercial_email[$c];
					$from = (empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? '' : $conf->global->MAIN_INFO_SOCIETE_NOM.' ').'<'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
					$mailfile = new CMailFile($subject, $sendto, $from, $messagecommercial, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', '', '', 'test');
					$mailfile->sendfile();
					$c++;
				}
			}
		}

			//Mail au client
			/*
			*Definition de l'adresse du client affecté devis
			*fk_c_type_contact`=41 indique que nous rechercher le "Contact client suivi propale"
			*/

		if ($action == 'PROPAL_CLOSE_SIGNED_ONLINE') {
			$sql="SELECT `civility`,`lastname`,`firstname`,`email` FROM ".MAIN_DB_PREFIX."socpeople WHERE `rowid` IN (SELECT `fk_socpeople` FROM ".MAIN_DB_PREFIX."element_contact WHERE `element_id`=$idPropal AND `fk_c_type_contact`=41)";
			$resql = $this->db->query($sql);
			$obj = $this->db->fetch_object($resql);
			$liste_client_civility = array($obj->civility);
			$liste_client_lastname = array($obj->lastname);
			$liste_client_firstname = array($obj->firstname);
			$liste_client_email = array($obj->email);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$j = 1;
				//Création de la liste du ou des client(s).
				while ($j < $num) {
					$row = $this->db->fetch_row($resql);
					array_push($liste_client_civility, $row['0']);
					array_push($liste_client_lastname, $row['1']);
					array_push($liste_client_firstname, $row['2']);
					array_push($liste_client_email, $row['3']);
					$j++;
				}
				//Boucle d'envoie des emails a chaque client définie plus haut.
				$h=0;
				foreach ($liste_client_email as $emailclient) {
					$subject = $conf->global->MAIN_INFO_SOCIETE_NOM." - Devis " .$object->ref;
					$filepath = array(DOL_DATA_ROOT."/".$object->last_main_doc);
					$mimetype = array("application/pdf");
					$filename = array($object->ref.'_Signe.pdf');
					$messageclient = "Bonjour ".$liste_client_civility[$h]." ".$liste_client_lastname[$h].",<br><br>Nous avons réceptionné votre devis signé (ci-joint). Le technicien en charge de votre projet vous recontactera dans les plus bref délais.<br><br>Cordialement<br>".$conf->global->MAIN_INFO_SOCIETE_NOM;
					$sendto = $liste_client_email[$h];
					$from = (empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? '' : $conf->global->MAIN_INFO_SOCIETE_NOM.' ').'<'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
					$mailfile = new CMailFile($subject, $sendto, $from, $messageclient, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', '', '', 'test');
					$mailfile->sendfile();
					$h++;
				}
			}
		}

		//Le devis est refuse.
			//Mail au commercial
			/*
			*Definition de l'adresse du commercial en charge du devis
			*fk_c_type_contact`=31 indique que nous rechercher le "Commercial suivi proposition"
			*/
		if ($action == 'PROPAL_CLOSE_REFUSED_ONLINE') {
			$sql="SELECT `lastname`,`firstname`,`email` FROM ".MAIN_DB_PREFIX."user WHERE `rowid` IN (SELECT `fk_socpeople` FROM ".MAIN_DB_PREFIX."element_contact WHERE `element_id`=$idPropal AND `fk_c_type_contact`=31)";
			$resql = $this->db->query($sql);
			$obj = $this->db->fetch_object($resql);
			$liste_commercial_lastname = array($obj->lastname);
			$liste_commercial_firstname = array($obj->firstname);
			$liste_commercial_email = array($obj->email);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$i = 1;
				//Création de la liste du ou des commercial/commerciaux.
				while ($i < $num) {
					$row = $this->db->fetch_row($resql);
					array_push($liste_commercial_lastname, $row['0']);
					array_push($liste_commercial_firstname, $row['1']);
					array_push($liste_commercial_email, $row['2']);
					$i++;
				}
				//Boucle d'envoie des emails a chaque commercial définie plus haut.
				$c=0;
				foreach ($liste_commercial_email as $emailcommercial) {
					$subject = $conf->global->MAIN_INFO_SOCIETE_NOM." - Devis " .$object->ref;
					$filepath = array(DOL_DATA_ROOT."/".$object->last_main_doc);
					$mimetype = array("application/pdf");
					$filename = array($object->ref.'.pdf');
					$messagecommercial = "Bonjour ".$liste_commercial_firstname[$c].",<br><br>Le devis ".$object->ref."(ci-joint) a été refusé par le client.<br><br>Cordialement<br>".$conf->global->MAIN_INFO_SOCIETE_NOM;
					$sendto = $liste_commercial_email[$c];
					$from = (empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? '' : $conf->global->MAIN_INFO_SOCIETE_NOM.' ').'<'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
					$mailfile = new CMailFile($subject, $sendto, $from, $messagecommercial, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', '', '', 'test');
					$mailfile->sendfile();
					$c++;
				}
			}
		}
			//Mail au client
			/*
			*Definition de l'adresse du client affecté devis
			*fk_c_type_contact`=41 indique que nous rechercher le "Contact client suivi propale"
			*/

		if ($action == 'PROPAL_CLOSE_REFUSED_ONLINE') {
			$sql="SELECT `civility`,`lastname`,`firstname`,`email` FROM ".MAIN_DB_PREFIX."socpeople WHERE `rowid` IN (SELECT `fk_socpeople` FROM ".MAIN_DB_PREFIX."element_contact WHERE `element_id`=$idPropal AND `fk_c_type_contact`=41)";
			$resql = $this->db->query($sql);
			$obj = $this->db->fetch_object($resql);
			$liste_client_civility = array($obj->civility);
			$liste_client_lastname = array($obj->lastname);
			$liste_client_firstname = array($obj->firstname);
			$liste_client_email = array($obj->email);
			if ($resql) {
				$num = $this->db->num_rows($resql);
				$j = 1;
				//Création de la liste du ou des client(s).
				while ($j < $num) {
					$row = $this->db->fetch_row($resql);
					array_push($liste_client_civility, $row['0']);
					array_push($liste_client_lastname, $row['1']);
					array_push($liste_client_firstname, $row['2']);
					array_push($liste_client_email, $row['3']);
					$j++;
				}
				//Boucle d'envoie des emails a chaque client définie plus haut.
				$h=0;
				foreach ($liste_client_email as $emailclient) {
					$subject = $conf->global->MAIN_INFO_SOCIETE_NOM." - Devis " .$object->ref;
					$filepath = array(DOL_DATA_ROOT."/".$object->last_main_doc);
					$mimetype = array("application/pdf");
					$filename = array($object->ref.'.pdf');
					$messageclient = "Bonjour ".$liste_client_civility[$h]." ".$liste_client_lastname[$h].",<br><br>Nous sommes désolé que le devis $object->ref (ci-joint) ne vous convienne pas. Le technicien en charge de votre projet vous recontactera dans les plus bref délais.<br><br>Cordialement<br>".$conf->global->MAIN_INFO_SOCIETE_NOM;
					$sendto = $liste_client_email[$h];
					$from = (empty($conf->global->MAIN_INFO_SOCIETE_NOM) ? '' : $conf->global->MAIN_INFO_SOCIETE_NOM.' ').'<'.$conf->global->MAIN_INFO_SOCIETE_MAIL.'>';
					$mailfile = new CMailFile($subject, $sendto, $from, $messageclient, $filepath, $mimetype, $filename, '', '', 0, -1, '', '', '', '', 'test');
					$mailfile->sendfile();
					$h++;
				}
			}
		}
	}
}
