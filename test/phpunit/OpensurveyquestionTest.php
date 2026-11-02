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
 *      \file       test/phpunit/OpensurveyquestionTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test for the questions of an opensurvey of format 'F' (form)
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db,$mysoc;
require_once dirname(__FILE__).'/CommonClassTest.class.php';
require_once dirname(__FILE__).'/../../htdocs/opensurvey/class/opensurveysondage.class.php';
require_once dirname(__FILE__).'/../../htdocs/opensurvey/class/opensurveyquestion.class.php';
require_once dirname(__FILE__).'/../../htdocs/opensurvey/lib/opensurvey.lib.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class OpensurveyquestionTest extends CommonClassTest
{
	/**
	 * Create a survey of format F for a test
	 *
	 * @return Opensurveysondage
	 */
	private function createFormSurvey()
	{
		global $user, $db;

		$survey = new Opensurveysondage($db);
		$survey->initAsSpecimen();
		$survey->id_sondage = dol_survey_random(16);
		$survey->format = 'F';
		$survey->create($user);
		$this->assertEmpty($survey->errors, $survey->errorsToString());

		return $survey;
	}

	/**
	 * Add the answer of a respondent to a question
	 *
	 * @param	int		$fkuser		Id of the respondent
	 * @param	int		$fkquestion	Id of the question
	 * @param	string	$value		Answer
	 * @return	void
	 */
	private function addAnswer($fkuser, $fkquestion, $value)
	{
		global $db;

		$db->query("INSERT INTO ".MAIN_DB_PREFIX."opensurvey_user_formanswers (fk_user_survey, fk_question, reponses) VALUES (".((int) $fkuser).", ".((int) $fkquestion).", '".$db->escape($value)."')");
	}

	/**
	 * Return the first column of the first row of a query
	 *
	 * @param	string	$sql	SQL query
	 * @return	string			Value
	 */
	private function countRows($sql)
	{
		global $db;

		$resql = $db->query($sql);
		$row = $db->fetch_row($resql);
		$db->free($resql);

		return (string) $row[0];
	}

	/**
	 * testParseAnswers
	 *
	 * @return void
	 */
	public function testParseAnswers()
	{
		$this->assertSame(array('a', 'b c', 'd'), Opensurveyquestion::parseAnswers(" a \r\n\r\nb c\nd\na\n"));
		$this->assertSame(array(), Opensurveyquestion::parseAnswers(''));
		$this->assertSame(array('0', '1'), Opensurveyquestion::parseAnswers("0\n1"));
	}

	/**
	 * testCheck
	 *
	 * @return void
	 */
	public function testCheck()
	{
		global $db;

		$question = new Opensurveyquestion($db);
		$question->id_sondage = 'abc';
		$this->assertLessThan(0, $question->check());
		$this->assertSame('ErrorOpenSurveyQuestionEmpty', (string) $question->error);

		$question->question = 'Color ?';
		$question->type = 'unknown';
		$this->assertLessThan(0, $question->check());
		$this->assertSame('ErrorOpenSurveyQuestionBadType', (string) $question->error);

		$question->type = Opensurveyquestion::TYPE_RADIO;
		$question->available_answers = "red\nred\n";
		$this->assertLessThan(0, $question->check());
		$this->assertSame('ErrorOpenSurveyQuestionNeedTwoAnswers', (string) $question->error);

		$question->available_answers = " red \n\nblue\nred";
		$this->assertGreaterThan(0, $question->check());
		$this->assertSame("red\nblue", $question->available_answers);

		// The list of answers is not kept for a free text
		$question->type = Opensurveyquestion::TYPE_TEXT;
		$this->assertGreaterThan(0, $question->check());
		$this->assertSame('', $question->available_answers);
	}

	/**
	 * testCheckAnswer
	 *
	 * @return void
	 */
	public function testCheckAnswer()
	{
		global $db;

		$question = new Opensurveyquestion($db);
		$question->id_sondage = 'abc';
		$question->question = 'Color ?';
		$question->type = Opensurveyquestion::TYPE_RADIO;
		$question->available_answers = "red\nblue\ngreen";
		$question->check();

		$this->assertSame('blue', $question->checkAnswer(' blue '));
		$this->assertFalse($question->checkAnswer('yellow'));
		$this->assertSame('ErrorOpenSurveyAnswerNotValid', (string) $question->error);
		$this->assertFalse($question->checkAnswer(array('red', 'blue')), 'Several answers for a radio');
		$this->assertNull($question->checkAnswer(''), 'Not mandatory and not answered');
		$this->assertNull($question->checkAnswer(null));

		$question->mandatory = 1;
		$this->assertFalse($question->checkAnswer(''));
		$this->assertSame('ErrorOpenSurveyAnswerMandatory', (string) $question->error);

		// Checkbox
		$question->type = Opensurveyquestion::TYPE_CHECKBOX;
		$this->assertSame("red\ngreen", $question->checkAnswer(array('red', 'green', 'red')));
		$this->assertFalse($question->checkAnswer(array('red', 'yellow')));
		$this->assertFalse($question->checkAnswer(array('red', array('green'))), 'Nested array');

		// Free text
		$question->type = Opensurveyquestion::TYPE_TEXT;
		$this->assertSame('hello', $question->checkAnswer(' hello '));
		$this->assertFalse($question->checkAnswer(str_repeat('a', Opensurveyquestion::MAXLENGTH_TEXT + 1)));
		$this->assertSame('ErrorOpenSurveyAnswerTooLong', (string) $question->error);
		$this->assertFalse($question->checkAnswer(array('a')), 'An array for a free text');
		$question->type = Opensurveyquestion::TYPE_TEXTAREA;
		$this->assertSame(str_repeat('a', 300), $question->checkAnswer(str_repeat('a', 300)));
	}

	/**
	 * testCrud
	 *
	 * @return void
	 */
	public function testCrud()
	{
		global $db;

		$survey = $this->createFormSurvey();

		$q1 = new Opensurveyquestion($db);
		$q1->id_sondage = $survey->id_sondage;
		$q1->question = 'Second';
		$q1->type = Opensurveyquestion::TYPE_CHECKBOX;
		$q1->available_answers = "a\nb\nc";
		$q1->position = 2;
		$q1->mandatory = 1;
		$id1 = $q1->create();
		$this->assertGreaterThan(0, $id1, (string) $q1->error);

		$q2 = new Opensurveyquestion($db);
		$q2->id_sondage = $survey->id_sondage;
		$q2->question = 'First';
		$q2->type = Opensurveyquestion::TYPE_TEXT;
		$q2->position = 1;
		$id2 = $q2->create();
		$this->assertGreaterThan(0, $id2, (string) $q2->error);

		// Fetch
		$fetched = new Opensurveyquestion($db);
		$this->assertSame(1, $fetched->fetch($id1));
		$this->assertSame('Second', $fetched->question);
		$this->assertSame(Opensurveyquestion::TYPE_CHECKBOX, $fetched->type);
		$this->assertSame(2, $fetched->position);
		$this->assertSame(1, $fetched->mandatory);
		$this->assertSame(array('a', 'b', 'c'), $fetched->getAvailableAnswers());
		$this->assertSame(0, $fetched->fetch(-1));

		// List of the survey, in order
		$list = $fetched->fetchAllBySurvey($survey->id_sondage);
		$this->assertIsArray($list);
		$this->assertCount(2, $list);
		$this->assertSame('First', $list[0]->question);
		$this->assertSame('Second', $list[1]->question);
		$this->assertSame(array(), $list[0]->getAvailableAnswers());
		$this->assertCount(0, $fetched->fetchAllBySurvey('doesnotexist'));

		// Update
		$fetched->question = 'Second renamed';
		$fetched->mandatory = 0;
		$this->assertGreaterThan(0, $fetched->update());
		$again = new Opensurveyquestion($db);
		$again->fetch($id1);
		$this->assertSame('Second renamed', $again->question);
		$this->assertSame(0, $again->mandatory);

		// Delete of a question removes its answers
		$this->addAnswer(987654, (int) $id2, 'x');
		$this->assertGreaterThan(0, $q2->delete());
		$this->assertSame(0, $again->fetch($id2));
		$this->assertSame('0', $this->countRows("SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."opensurvey_user_formanswers WHERE fk_question = ".((int) $id2)));

		return $survey;
	}

	/**
	 * testDeleteSurveyDeletesQuestionsAndAnswers
	 *
	 * @return void
	 */
	public function testDeleteSurveyDeletesQuestionsAndAnswers()
	{
		global $user, $db;

		$survey = $this->createFormSurvey();

		$question = new Opensurveyquestion($db);
		$question->id_sondage = $survey->id_sondage;
		$question->question = 'Q';
		$question->type = Opensurveyquestion::TYPE_TEXT;
		$qid = $question->create();
		$this->assertGreaterThan(0, $qid, (string) $question->error);

		// One respondent with one answer
		$db->query("INSERT INTO ".MAIN_DB_PREFIX."opensurvey_user_studs (nom, id_sondage, reponses, ip, date_creation) VALUES ('Bob', '".$db->escape($survey->id_sondage)."', '', '127.0.0.1', '".$db->idate(dol_now())."')");
		$studid = (int) $db->last_insert_id(MAIN_DB_PREFIX.'opensurvey_user_studs');
		$this->assertGreaterThan(0, $studid);
		$this->addAnswer($studid, (int) $qid, 'hello');

		$this->assertSame('1', $this->countRows("SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."opensurvey_user_formanswers WHERE fk_user_survey = ".$studid));

		$this->assertGreaterThan(0, $survey->delete($user));

		$this->assertSame('0', $this->countRows("SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."opensurvey_user_formanswers WHERE fk_user_survey = ".$studid));
		$this->assertSame('0', $this->countRows("SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."opensurvey_formquestions WHERE id_sondage = '".$db->escape($survey->id_sondage)."'"));
		$this->assertSame('0', $this->countRows("SELECT COUNT(*) FROM ".MAIN_DB_PREFIX."opensurvey_user_studs WHERE id_sondage = '".$db->escape($survey->id_sondage)."'"));
	}
}
