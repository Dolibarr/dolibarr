<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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
 */

	class Auth {

		protected $db;
		
		protected $login;
		protected $passwd;

		protected $reponse;

		protected $sqlQuery;

		public function __construct ($DB) {

			$this->db = $DB;
			$this->reponse (null);

		}

		public function login ($aLogin) {

			$this->login = $aLogin;

		}

		public function passwd ($aPasswd) {

			$this->passwd = $aPasswd;


		}

		public function reponse ($aReponse) {

			$this->reponse = $aReponse;

		}

			/**
			* Authentification d'un demandeur
			* @return (int) 0 = Ok; -1 = login incorrect; -2 = login ok, mais compte desactive; -10 = aucune entree trouvee dans la base
			*/
			protected function verif_utilisateurs () {

				global $conf;

				// Verification des informations dans la base
				$resql = $this->db->query ($this->sqlQuery);
				if ($resql)
				{
					$num = $this->db->num_rows ($resql);

					if ( $num ) {

						// fetchFirst
						$ret=array();
						$tab = $this->db->fetch_array($resql);
						foreach ( $tab as $cle => $valeur )
						{
							$ret[$cle] = $valeur;
						}
						$tab=$ret;

						if ( ($tab['pass_crypted'] == md5 ($this->passwd)) || (($tab['pass'] == $this->passwd) && ($tab['pass'] != ''))) {

							// On verifie que le compte soit bien actif
							if ( $tab['statut'] ) {

								$this->reponse(0);

							} else {

								$this->reponse(-2);

							}

						} else {

							$this->reponse(-1);

						}

					} else {

						$this->reponse(-10);

					}
				}
				else
				{

				}

			}

		public function verif ($aLogin, $aPasswd) {
			global $conf;

			$this->login ($aLogin);
			$this->passwd ($aPasswd);

			$this->sqlQuery = "SELECT rowid, pass_crypted, statut";
			$this->sqlQuery.= " FROM ".MAIN_DB_PREFIX."user";
			$this->sqlQuery.= " WHERE login = '".$this->login."'";
			$this->sqlQuery.= " AND entity IN (0,".$conf->entity.")";

			$this->verif_utilisateurs();

			switch ($this->reponse) {

				default:
					$ret = '-1';
					break;

				case 0:
					$ret = '0';
					break;

				case -1:
					$ret = '-1';
					break;

				case -2:
					$ret = '-2';
					break;

				case -10:
					$ret = '-10';
					break;

			}

			return $ret;

		}

	}

?>
