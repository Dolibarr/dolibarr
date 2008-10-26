<?php
/* Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>
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

		protected $login;
		protected $passwd;

		protected $host;
		protected $user;
		protected $name;
		protected $base;

		protected $reponse;

		protected $sql;

		public function __construct ($aHost, $aUser, $aPass, $aBase) {

			$this->host = $aHost;
			$this->user = $aUser;
			$this->pass = $aPass;
			$this->base = $aBase;

			$this->reponse (null);

		}

		public function login ($aLogin) {

			$this->login = $aLogin;

		}

		public function passwd ($aPasswd) {

			$this->passwd = $aPasswd;


		}

		public function host ($aHost) {

			$this->host = $aHost;


		}

		public function user ($aUser) {

			$this->user = $aUser;


		}

		public function pass ($aPass) {

			$this->pass = $aPass;


		}

		public function base ($aBase) {

			$this->base = $aBase;


		}

		public function reponse ($aReponse) {

			$this->reponse = $aReponse;

		}

			/**
			* Authentification d'un demandeur
			* @return (int) 0 = Ok; -1 = login incorrect; -2 = login ok, mais compte désactivé; -10 = aucune entrée trouvée dans la base
			*/
			protected function verif_utilisateurs () {

				$sql = new Sql ($this->host, $this->user, $this->pass, $this->base);

				// Vérification des informations dans la base
				$res = $sql->query ($this->sql);

				if ( $sql->numRows ($res) ) {

					$tab = $sql->fetchFirst ($res);

					if ( ($tab['pass_crypted'] == md5 ($this->passwd)) || (($tab['pass'] == $this->passwd) && ($tab['pass'] != ''))) {

						// On vérifie que le compte soit bien actif
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

		public function verif ($aLogin, $aPasswd) {

			$this->login ($aLogin);
			$this->passwd ($aPasswd);

			$this->sql = "SELECT rowid, pass_crypted, statut
					FROM ".MAIN_DB_PREFIX."user
					WHERE login = '".$this->login."'";


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
