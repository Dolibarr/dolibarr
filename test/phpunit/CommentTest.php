<?php
/* Copyright (C) 2026  Frédéric France         <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *      \file       test/phpunit/CommentTest.php
 *      \ingroup    test
 *      \brief      PHPUnit test
 *      \remarks    To run this script as CLI:  phpunit filename.php
 */

global $conf,$user,$langs,$db;
require_once dirname(__FILE__).'/../../htdocs/master.inc.php';
require_once dirname(__FILE__).'/../../htdocs/core/class/comment.class.php';
require_once dirname(__FILE__).'/../../htdocs/projet/class/project.class.php';
require_once dirname(__FILE__).'/CommonClassTest.class.php';

if (empty($user->id)) {
	print "Load permissions for admin user nb 1\n";
	$user->fetch(1);
	$user->loadRights();
}
$conf->global->MAIN_DISABLE_ALL_MAILS = 1;


/**
 * Class for PHPUnit tests
 *
 * Comment is generic/polymorphic (fk_element + element_type, no DB foreign key): it is attached to a
 * freshly created Project here, matching the real usage in core/actions_comments.inc.php (used by
 * several object cards to power their "Comments" tab).
 *
 * @backupGlobals disabled
 * @backupStaticAttributes enabled
 * @remarks	backupGlobals must be disabled to have db,conf,user and lang not erased.
 */
class CommentTest extends CommonClassTest
{
	/**
	 * testCommentCreate
	 *
	 * Comment has no initAsSpecimen(), so the object is built by hand.
	 *
	 * @return array{0:int,1:int} Id of the comment and id of the project it is attached to
	 */
	public function testCommentCreate()
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$project = new Project($db);
		$project->initAsSpecimen();
		$projectid = $project->create($user);
		$this->assertGreaterThan(0, $projectid, $project->errorsToString());

		$localobject = new Comment($db);
		$localobject->description = 'PHPUnit comment';
		$localobject->datec = dol_now();
		$localobject->fk_element = $projectid;
		$localobject->element_type = 'project';
		$localobject->fk_user_author = $user->id;
		$localobject->entity = $conf->entity;
		$result = $localobject->create($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." result=".$result." fk_element=".$projectid."\n";
		return array($result, $projectid);
	}

	/**
	 * testCommentFetch
	 *
	 * @param	array{0:int,1:int}	$data	Id of the comment and id of the project it is attached to
	 * @return	Comment
	 *
	 * @depends	testCommentCreate
	 * The depends says test is run only if previous is ok
	 */
	public function testCommentFetch($data)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		list($id, $projectid) = $data;

		$localobject = new Comment($db);
		$result = $localobject->fetch($id);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		$this->assertSame('PHPUnit comment', $localobject->description);
		$this->assertEquals($projectid, $localobject->fk_element);
		$this->assertSame('project', $localobject->element_type);
		$this->assertEquals($user->id, $localobject->fk_user_author);

		return $localobject;
	}

	/**
	 * testCommentUpdate
	 *
	 * @param	Comment	$localobject	Comment
	 * @return	Comment
	 *
	 * @depends	testCommentFetch
	 * The depends says test is run only if previous is ok
	 */
	public function testCommentUpdate($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$localobject->description = 'Updated comment after update';
		$result = $localobject->update($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$localobject->id." result=".$result."\n";

		$localobject->fetch($localobject->id);
		$this->assertSame('Updated comment after update', $localobject->description);

		return $localobject;
	}

	/**
	 * testCommentFetchAllFor
	 *
	 * @param	Comment	$localobject	Comment
	 * @return	Comment
	 *
	 * @depends	testCommentUpdate
	 * The depends says test is run only if previous is ok
	 */
	public function testCommentFetchAllFor($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$lister = new Comment($db);
		$result = $lister->fetchAllFor('project', (int) $localobject->fk_element);

		$this->assertGreaterThan(0, $result, $lister->errorsToString());
		$ids = array_map(function ($c) {
			return $c->id;
		}, $lister->comments);
		$this->assertContains($localobject->id, $ids);

		return $localobject;
	}

	/**
	 * testCommentDelete
	 *
	 * @param	Comment	$localobject	Comment
	 * @return	int
	 *
	 * @depends	testCommentFetchAllFor
	 * The depends says test is run only if previous is ok
	 */
	public function testCommentDelete($localobject)
	{
		global $conf,$user,$langs,$db;
		$conf = $this->savconf;
		$user = $this->savuser;
		$langs = $this->savlangs;
		$db = $this->savdb;

		$id = $localobject->id;
		$result = $localobject->delete($user);

		$this->assertGreaterThan(0, $result, $localobject->errorsToString());
		print __METHOD__." id=".$id." result=".$result."\n";
		return $result;
	}
}
