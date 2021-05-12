<?php
/* Copyright (C) 2006-2011	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2014		Teddy Andreotti		<125155@supinfo.com>
<<<<<<< HEAD
 * Copyright (C) 2017		Regis Houssin		<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2017		Regis Houssin		<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *      \file       htdocs/core/modules/security/generate/modGeneratePassPerso.class.php
 *      \ingroup    core
 *      \brief      File to manage no password generation.
 */

require_once DOL_DOCUMENT_ROOT .'/core/modules/security/generate/modules_genpassword.php';


/**
 *	    \class      modGeneratePassPerso
 *		\brief      Class to generate a password according to personal rules
 */
class modGeneratePassPerso extends ModeleGenPassword
{
<<<<<<< HEAD
	var $id;
	var $length;
	var $length2; // didn't overright display
	var $NbMaj;
	var $NbNum;
	var $NbSpe;
	var $NbRepeat;
	var $WithoutAmbi;

	var $db;
	var $conf;
	var $lang;
	var $user;

	var $Maj;
	var $Min;
	var $Nb;
	var $Spe;
	var $Ambi;
	var $All;
=======
	/**
	 * @var int ID
	 */
	public $id;

	public $length;
	public $length2; // didn't overright display
	public $NbMaj;
	public $NbNum;
	public $NbSpe;
	public $NbRepeat;
	public $WithoutAmbi;

	/**
     * @var DoliDB Database handler.
     */
    public $db;

	public $conf;
	public $lang;
	public $user;

	public $Maj;
	public $Min;
	public $Nb;
	public $Spe;
	public $Ambi;
	public $All;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db			Database handler
	 *	@param		Conf		$conf		Handler de conf
	 *	@param		Translate	$langs		Handler de langue
	 *	@param		User		$user		Handler du user connecte
	 */
<<<<<<< HEAD
	function __construct($db, $conf, $langs, $user)
=======
	public function __construct($db, $conf, $langs, $user)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$this->id = "Perso";
		$this->length = $langs->trans("SetupPerso");

		$this->db=$db;
		$this->conf=$conf;
		$this->langs=$langs;
		$this->user=$user;

<<<<<<< HEAD
		if(empty($conf->global->USER_PASSWORD_PATTERN)){
			// default value (8carac, 1maj, 1digit, 1spe,  3 repeat, no ambi at auto generation.
			dolibarr_set_const($db, "USER_PASSWORD_PATTERN", '8;1;1;1;3;1','chaine',0,'',$conf->entity);
=======
		if (empty($conf->global->USER_PASSWORD_PATTERN)) {
			// default value (8carac, 1maj, 1digit, 1spe,  3 repeat, no ambi at auto generation.
			dolibarr_set_const($db, "USER_PASSWORD_PATTERN", '8;1;1;1;3;1', 'chaine', 0, '', $conf->entity);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}

		$this->Maj = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		$this->Min = strtolower($this->Maj);
		$this->Nb = "0123456789";
		$this->Spe = "!@#$%&*()_-+={}[]\\|:;'/";
		$this->Ambi = array("1","I","l","|","O","0");

<<<<<<< HEAD
		$tabConf = explode(";",$conf->global->USER_PASSWORD_PATTERN);
=======
		$tabConf = explode(";", $conf->global->USER_PASSWORD_PATTERN);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		$this->length2 = $tabConf[0];
		$this->NbMaj = $tabConf[1];
		$this->NbNum = $tabConf[2];
		$this->NbSpe = $tabConf[3];
		$this->NbRepeat = $tabConf[4];
		$this->WithoutAmbi = $tabConf[5];

		if ($this->WithoutAmbi)
		{
<<<<<<< HEAD
			$this->Maj = str_replace($this->Ambi,"",$this->Maj);
			$this->Min = str_replace($this->Ambi,"",$this->Min);
			$this->Nb  = str_replace($this->Ambi,"",$this->Nb);
			$this->Spe = str_replace($this->Ambi,"",$this->Spe);
=======
			$this->Maj = str_replace($this->Ambi, "", $this->Maj);
			$this->Min = str_replace($this->Ambi, "", $this->Min);
			$this->Nb  = str_replace($this->Ambi, "", $this->Nb);
			$this->Spe = str_replace($this->Ambi, "", $this->Spe);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}

		$pattern = $this->Min . (! empty($this->NbMaj)?$this->Maj:'') . (! empty($this->NbNum)?$this->Nb:'') . (! empty($this->NbSpe)?$this->Spe:'');
		$this->All = str_shuffle($pattern);

		//$this->All = str_shuffle($this->Maj. $this->Min. $this->Nb. $this->Spe);
		//$this->All = $this->Maj. $this->Min. $this->Nb. $this->Spe;
		//$this->All =  $this->Spe;
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}

	/**
	 *		Return description of module
	 *
	 *      @return     string      Description of text
	 */
<<<<<<< HEAD
	function getDescription()
=======
	public function getDescription()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return $langs->trans("PasswordGenerationPerso");
	}

	/**
	 * 		Return an example of password generated by this module
	 *
	 *      @return     string      Example of password
	 */
<<<<<<< HEAD
	function getExample()
=======
	public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return $this->getNewGeneratedPassword();
	}

	/**
<<<<<<< HEAD
	 * 		Build new password
	 *
	 *      @return     string      Return a new generated password
	 */
	function getNewGeneratedPassword()
	{
		$pass = "";
		for($i=0; $i<$this->NbMaj; $i++){ // Y
			$pass .= $this->Maj[mt_rand(0,strlen($this->Maj) - 1)];
		}

		for($i=0; $i<$this->NbNum; $i++){ // X
			$pass .= $this->Nb[mt_rand(0,strlen($this->Nb) - 1)];
		}

		for($i=0; $i<$this->NbSpe; $i++){ // @
			$pass .= $this->Spe[mt_rand(0,strlen($this->Spe) - 1)];
		}

		for($i=strlen($pass);$i<$this->length2; $i++){ // y
			$pass .= $this->All[mt_rand(0,strlen($this->All) -1)];
=======
	 *  Build new password
	 *
	 *  @return     string      Return a new generated password
	 */
	public function getNewGeneratedPassword()
	{
		$pass = "";
		for ($i=0; $i<$this->NbMaj; $i++) {
            // Y
			$pass .= $this->Maj[mt_rand(0, strlen($this->Maj) - 1)];
		}

		for ($i=0; $i<$this->NbNum; $i++) {
            // X
			$pass .= $this->Nb[mt_rand(0, strlen($this->Nb) - 1)];
		}

		for ($i=0; $i<$this->NbSpe; $i++) {
            // @
			$pass .= $this->Spe[mt_rand(0, strlen($this->Spe) - 1)];
		}

		for ($i=strlen($pass);$i<$this->length2; $i++) {
            // y
			$pass .= $this->All[mt_rand(0, strlen($this->All) -1)];
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}

		$pass = str_shuffle($pass);

<<<<<<< HEAD
		if ($this->validatePassword($pass))
		{
=======
		if ($this->validatePassword($pass)) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return $pass;
		}

		return $this->getNewGeneratedPassword();
	}

	/**
<<<<<<< HEAD
	 * 		Validate a password
	 *
	 *		@param		string	$password	Password to check
	 *      @return     int					0 if KO, >0 if OK
	 */
	function validatePassword($password)
=======
	 *  Validate a password
	 *
	 *  @param      string  $password   Password to check
	 *  @return     int                 0 if KO, >0 if OK
	 */
	public function validatePassword($password)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		$password_a = str_split($password);
		$maj = str_split($this->Maj);
		$num = str_split($this->Nb);
		$spe = str_split($this->Spe);

<<<<<<< HEAD
		if(count(array_intersect($password_a, $maj)) < $this->NbMaj){
			return 0;
		}

		if(count(array_intersect($password_a, $num)) < $this->NbNum){
			return 0;
		}

		if(count(array_intersect($password_a, $spe)) < $this->NbSpe){
			return 0;
		}

		if(!$this->consecutiveInterationSameCharacter($password)){
=======
		if (count(array_intersect($password_a, $maj)) < $this->NbMaj) {
			return 0;
		}

		if (count(array_intersect($password_a, $num)) < $this->NbNum) {
			return 0;
		}

		if (count(array_intersect($password_a, $spe)) < $this->NbSpe) {
			return 0;
		}

		if (!$this->consecutiveInterationSameCharacter($password)) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return 0;
		}

		return 1;
	}

	/**
<<<<<<< HEAD
	 * 		consecutive iterations of the same character
	 *
	 *		@param		string	$password	Password to check
	 *      @return     int					0 if KO, >0 if OK
	 */
	function consecutiveInterationSameCharacter($password){
		$last = "";
		$count = 0;
		$char = str_split($password);
		foreach($char as $c){
			if($c != $last){
				$last = $c;
				$count = 0;
			}else{
				$count++;
			}

			if($count >= $this->NbRepeat) {
=======
	 *  consecutive iterations of the same character
	 *
	 *  @param		string	$password	Password to check
	 *  @return     int					0 if KO, >0 if OK
	 */
    public function consecutiveInterationSameCharacter($password)
    {
		$last = "";
		$count = 0;
		$char = str_split($password);
		foreach($char as $c) {
			if($c != $last) {
				$last = $c;
				$count = 0;
			} else {
				$count++;
			}

			if ($count >= $this->NbRepeat) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
				return 0;
			}
		}
		return 1;
	}
}
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
