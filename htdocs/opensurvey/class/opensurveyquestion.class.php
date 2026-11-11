<?php
/* Copyright (C) 2026 Dolibarr development team
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
 *  \file       htdocs/opensurvey/class/opensurveyquestion.class.php
 *  \ingroup    opensurvey
 *  \brief      Class for a question of an opensurvey of format 'F' (form)
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class for a question of a survey of format 'F' (form).
 *	The answers of the respondents are into the table opensurvey_user_formanswers.
 */
class Opensurveyquestion extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'opensurvey_formquestions';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'opensurvey_formquestions';

	/**
	 * @var string String with name of icon for the object
	 */
	public $picto = 'poll';

	const TYPE_TEXT = 'text';			// Short free text
	const TYPE_TEXTAREA = 'textarea';	// Long free text
	const TYPE_RADIO = 'radio';			// One answer among the available answers
	const TYPE_CHECKBOX = 'checkbox';	// One or several answers among the available answers

	/**
	 * Max length of an answer in free text
	 */
	const MAXLENGTH_TEXT = 255;

	/**
	 * Max length of an answer in long free text
	 */
	const MAXLENGTH_TEXTAREA = 5000;

	/**
	 * @var string Id of the survey (id_sondage of the table opensurvey_sondage)
	 */
	public $id_sondage = '';

	/**
	 * @var string Text of the question
	 */
	public $question = '';

	/**
	 * @var string Type of answer (one of the TYPE_* constants)
	 */
	public $type = self::TYPE_RADIO;

	/**
	 * @var int Order of the question into the survey
	 */
	public $position = 0;

	/**
	 * @var int<0,1> 1 if the respondent must answer
	 */
	public $mandatory = 0;

	/**
	 * @var string List of available answers, one per line (for the types radio and checkbox)
	 */
	public $available_answers = '';


	/**
	 *	Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}

	/**
	 * Return the types of question, with the label of each type
	 *
	 * @param	Translate	$langs	Language object
	 * @return	array<string,string>	Array of label with the type as key
	 */
	public static function getTypes($langs)
	{
		$langs->load('opensurvey');

		return array(
			self::TYPE_TEXT => $langs->trans('OpenSurveyQuestionTypeText'),
			self::TYPE_TEXTAREA => $langs->trans('OpenSurveyQuestionTypeTextarea'),
			self::TYPE_RADIO => $langs->trans('OpenSurveyQuestionTypeRadio'),
			self::TYPE_CHECKBOX => $langs->trans('OpenSurveyQuestionTypeCheckbox'),
		);
	}

	/**
	 * Return the types of question that have a list of available answers
	 *
	 * @return	string[]	List of types
	 */
	public static function getTypesWithAnswers()
	{
		return array(self::TYPE_RADIO, self::TYPE_CHECKBOX);
	}

	/**
	 * Convert a text with one answer per line into a clean list of answers (trimmed, no empty line, no duplicate)
	 *
	 * @param	string	$text	Text with one answer per line
	 * @return	string[]		List of answers
	 */
	public static function parseAnswers($text)
	{
		$list = array();
		foreach (preg_split('/\r\n|\r|\n/', (string) $text) as $line) {
			$line = trim($line);
			if ($line !== '' && !in_array($line, $list, true)) {
				$list[] = $line;
			}
		}

		return $list;
	}

	/**
	 * Return the list of available answers of the question
	 *
	 * @return	string[]	List of answers (empty for a question in free text)
	 */
	public function getAvailableAnswers()
	{
		if (!in_array($this->type, self::getTypesWithAnswers())) {
			return array();
		}

		return self::parseAnswers($this->available_answers);
	}

	/**
	 * Check the properties of the question and clean the list of available answers.
	 *
	 * @param	int<0,1>	$needsurvey		1 (default) = the question must be linked to a survey, 0 = the survey is not known yet
	 * @return	int			Return integer <0 if KO (the reason is into $this->error), >0 if OK
	 */
	public function check($needsurvey = 1)
	{
		$this->question = trim($this->question);
		if ($this->question === '') {
			$this->error = 'ErrorOpenSurveyQuestionEmpty';
			return -1;
		}
		if (!in_array($this->type, array(self::TYPE_TEXT, self::TYPE_TEXTAREA, self::TYPE_RADIO, self::TYPE_CHECKBOX), true)) {
			$this->error = 'ErrorOpenSurveyQuestionBadType';
			return -1;
		}
		if ($needsurvey && empty($this->id_sondage)) {
			$this->error = 'ErrorOpenSurveyQuestionNoSurvey';
			return -1;
		}

		if (in_array($this->type, self::getTypesWithAnswers())) {
			$answers = self::parseAnswers($this->available_answers);
			if (count($answers) < 2) {
				$this->error = 'ErrorOpenSurveyQuestionNeedTwoAnswers';
				return -1;
			}
			$this->available_answers = implode("\n", $answers);
		} else {
			$this->available_answers = '';
		}

		$this->position = (int) $this->position;
		$this->mandatory = empty($this->mandatory) ? 0 : 1;

		return 1;
	}

	/**
	 * Check an answer given by a respondent for this question, and return the value to store.
	 * The answer is checked against the available answers: a value that is not one of them is refused.
	 *
	 * @param	string|string[]|null	$posted	Value posted (an array of values for the type checkbox)
	 * @return	string|null|false				Value to store, null if the question is not mandatory and not answered, false if the answer is not valid (the reason is into $this->error)
	 */
	public function checkAnswer($posted)
	{
		$this->error = '';

		if (is_array($posted)) {
			if ($this->type != self::TYPE_CHECKBOX) {
				$this->error = 'ErrorOpenSurveyAnswerNotValid';
				return false;
			}
			$values = array();
			foreach ($posted as $value) {
				if (!is_string($value)) {
					$this->error = 'ErrorOpenSurveyAnswerNotValid';
					return false;
				}
				$values[] = trim($value);
			}
		} else {
			$values = array(trim((string) $posted));
		}
		$values = array_values(array_filter($values, function (string $value): bool {
			return $value !== '';
		}));

		if (empty($values)) {
			if ($this->mandatory) {
				$this->error = 'ErrorOpenSurveyAnswerMandatory';
				return false;
			}
			return null;
		}

		switch ($this->type) {
			case self::TYPE_TEXT:
			case self::TYPE_TEXTAREA:
				$value = $values[0];
				$maxlength = ($this->type == self::TYPE_TEXT ? self::MAXLENGTH_TEXT : self::MAXLENGTH_TEXTAREA);
				if (dol_strlen($value) > $maxlength) {
					$this->error = 'ErrorOpenSurveyAnswerTooLong';
					return false;
				}
				return $value;
			case self::TYPE_RADIO:
			case self::TYPE_CHECKBOX:
				$available = $this->getAvailableAnswers();
				if ($this->type == self::TYPE_RADIO && count($values) > 1) {
					$this->error = 'ErrorOpenSurveyAnswerNotValid';
					return false;
				}
				foreach ($values as $value) {
					if (!in_array($value, $available, true)) {
						$this->error = 'ErrorOpenSurveyAnswerNotValid';
						return false;
					}
				}
				return implode("\n", array_values(array_unique($values)));
		}

		$this->error = 'ErrorOpenSurveyAnswerNotValid';
		return false;
	}

	/**
	 * Build the questions of a survey from the rows of a form. A row without text of question is ignored.
	 * The questions are not saved, call create() on each of them.
	 *
	 * @param	DoliDB								$db			Database handler
	 * @param	string								$idsondage	Id of the survey (id_sondage), can be empty if the survey is not created yet
	 * @param	array<array{question?:string,type?:string,available_answers?:string,mandatory?:int|string}>	$rows	Rows of the form
	 * @param	string[]							$errors		List of errors (key of translation), filled by the method
	 * @return	Opensurveyquestion[]							Questions in the order of the rows, with the position set
	 */
	public static function buildList($db, $idsondage, $rows, &$errors = array())
	{
		$list = array();
		foreach ($rows as $row) {
			$text = trim(isset($row['question']) ? (string) $row['question'] : '');
			if ($text === '') {
				continue;
			}
			$question = new Opensurveyquestion($db);
			$question->id_sondage = $idsondage;
			$question->question = $text;
			$question->type = isset($row['type']) ? (string) $row['type'] : self::TYPE_RADIO;
			$question->available_answers = isset($row['available_answers']) ? (string) $row['available_answers'] : '';
			$question->mandatory = empty($row['mandatory']) ? 0 : 1;
			$question->position = count($list) + 1;
			if ($question->check(0) < 0) {
				$errors[] = $question->error;
				continue;
			}
			$list[] = $question;
		}

		return $list;
	}

	/**
	 * Create the question into the database
	 *
	 * @return	int		Return integer <0 if KO, Id of the new question if OK
	 */
	public function create()
	{
		if ($this->check() < 0) {
			return -1;
		}

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (id_sondage, question, type, position, mandatory, available_answers)";
		$sql .= " VALUES ('".$this->db->escape($this->id_sondage)."', '".$this->db->escape($this->question)."', '".$this->db->escape($this->type)."',";
		$sql .= " ".((int) $this->position).", ".((int) $this->mandatory).", ";
		$sql .= ($this->available_answers !== '' ? "'".$this->db->escape($this->available_answers)."'" : "null").")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

		return $this->id;
	}

	/**
	 * Load a question from the database
	 *
	 * @param	int		$id		Id of the question
	 * @return	int				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id)
	{
		$sql = "SELECT rowid, id_sondage, question, type, position, mandatory, available_answers";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}
		$obj = $this->db->fetch_object($resql);
		$this->db->free($resql);
		if (!$obj) {
			return 0;
		}

		$this->setFromObject($obj);

		return 1;
	}

	/**
	 * Return all the questions of a survey, in the order of the survey
	 *
	 * @param	string	$idsondage	Id of the survey (id_sondage)
	 * @return	Opensurveyquestion[]|int<-1,-1>	Array of questions, or -1 if error
	 */
	public function fetchAllBySurvey($idsondage)
	{
		$sql = "SELECT rowid, id_sondage, question, type, position, mandatory, available_answers";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE id_sondage = '".$this->db->escape($idsondage)."'";
		$sql .= " ORDER BY position, rowid";

		dol_syslog(get_class($this)."::fetchAllBySurvey", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$list = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$question = new Opensurveyquestion($this->db);
			$question->setFromObject($obj);
			$list[] = $question;
		}
		$this->db->free($resql);

		return $list;
	}

	/**
	 * Return the respondents of a survey with their answers, in one query.
	 *
	 * @param	string	$idsondage	Id of the survey (id_sondage)
	 * @return	array<int,array{id:int,name:string,date:int|string,ip:string,answers:array<int,string>}>|int<-1,-1>	Respondents with the id of the respondent as key (answers: value with the id of the question as key), or -1 if error
	 */
	public function fetchRespondents($idsondage)
	{
		$sql = "SELECT us.id_users, us.nom, us.ip, us.date_creation, fa.fk_question, fa.reponses";
		$sql .= " FROM ".MAIN_DB_PREFIX."opensurvey_user_studs as us";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."opensurvey_user_formanswers as fa ON fa.fk_user_survey = us.id_users";
		$sql .= " WHERE us.id_sondage = '".$this->db->escape($idsondage)."'";
		$sql .= " ORDER BY us.id_users, fa.fk_question";

		dol_syslog(get_class($this)."::fetchRespondents", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		$list = array();
		while ($obj = $this->db->fetch_object($resql)) {
			$idusers = (int) $obj->id_users;
			if (!isset($list[$idusers])) {
				$list[$idusers] = array('id' => $idusers, 'name' => (string) $obj->nom, 'date' => $this->db->jdate($obj->date_creation), 'ip' => (string) $obj->ip, 'answers' => array());
			}
			if ($obj->fk_question !== null) {
				$list[$idusers]['answers'][(int) $obj->fk_question] = (string) $obj->reponses;
			}
		}
		$this->db->free($resql);

		return $list;
	}

	/**
	 * Count the answers given to a question with a choice of answers (type radio or checkbox).
	 *
	 * @param	array<int,array{answers:array<int,string>}>	$respondents	Respondents, as returned by fetchRespondents()
	 * @return	array{counts:array<string,int>,answered:int}				Number of respondents for each available answer, and number of respondents that answered the question
	 */
	public function countAnswers($respondents)
	{
		$counts = array();
		foreach ($this->getAvailableAnswers() as $answer) {
			$counts[$answer] = 0;
		}

		$answered = 0;
		foreach ($respondents as $respondent) {
			if (!isset($respondent['answers'][$this->id]) || $respondent['answers'][$this->id] === '') {
				continue;
			}
			$answered++;
			foreach (preg_split('/\n/', $respondent['answers'][$this->id]) as $value) {
				if (isset($counts[$value])) {	// The answers that are not (any more) into the available answers are not counted
					$counts[$value]++;
				}
			}
		}

		return array('counts' => $counts, 'answered' => $answered);
	}

	/**
	 * Update the question into the database
	 *
	 * @return	int		Return integer <0 if KO, >0 if OK
	 */
	public function update()
	{
		if ($this->check() < 0) {
			return -1;
		}

		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " question = '".$this->db->escape($this->question)."',";
		$sql .= " type = '".$this->db->escape($this->type)."',";
		$sql .= " position = ".((int) $this->position).",";
		$sql .= " mandatory = ".((int) $this->mandatory).",";
		$sql .= " available_answers = ".($this->available_answers !== '' ? "'".$this->db->escape($this->available_answers)."'" : "null");
		$sql .= " WHERE rowid = ".((int) $this->id);
		$sql .= " AND id_sondage = '".$this->db->escape($this->id_sondage)."'";

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return 1;
	}

	/**
	 * Delete the question and the answers given to it
	 *
	 * @return	int		Return integer <0 if KO, >0 if OK
	 */
	public function delete()
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."opensurvey_user_formanswers WHERE fk_question = ".((int) $this->id);
		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
			$resql = $this->db->query($sql);
		}
		if (!$resql) {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}

		$this->db->commit();

		return 1;
	}

	/**
	 * Set the properties from a row of the database
	 *
	 * @param	object	$obj	Row of the table opensurvey_formquestions
	 * @return	void
	 */
	private function setFromObject($obj)
	{
		$this->id = (int) $obj->rowid;
		$this->id_sondage = $obj->id_sondage;
		$this->question = $obj->question;
		$this->type = $obj->type;
		$this->position = (int) $obj->position;
		$this->mandatory = (int) $obj->mandatory;
		$this->available_answers = (string) $obj->available_answers;
	}
}
