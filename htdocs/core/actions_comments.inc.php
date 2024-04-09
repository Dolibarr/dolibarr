<?php
/* Copyright (C) 2011-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $elementype must be defined.
 */

/**
 *	\file			htdocs/core/actions_comments.inc.php
 *  \brief			Code for actions on comments pages
 */


require_once DOL_DOCUMENT_ROOT.'/core/class/comment.class.php';

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$comment = new Comment($db);

/*
 * Actions
 */

if ($action == 'addcomment') {
	$description = GETPOST('comment_description', 'restricthtml');
	if (!empty($description)) {
		$comment->description = $description;
		$comment->datec = dol_now();
		$comment->fk_element = GETPOSTINT('id');
		$comment->element_type = GETPOST('comment_element_type', 'alpha');
		$comment->fk_user_author = $user->id;
		$comment->entity = $conf->entity;
		if ($comment->create($user) > 0) {
			setEventMessages($langs->trans("CommentAdded"), null, 'mesgs');
			header('Location: '.$varpage.'?id='.$id.($withproject ? '&withproject=1' : ''));
			exit;
		} else {
			setEventMessages($comment->error, $comment->errors, 'errors');
			$action = '';
		}
	}
}
if ($action === 'updatecomment') {
	if ($comment->fetch($idcomment) >= 0) {
		$comment->description = GETPOST('comment_description', 'restricthtml');
		if ($comment->update($user) > 0) {
			setEventMessages($langs->trans("CommentAdded"), null, 'mesgs');
			header('Location: '.$varpage.'?id='.$id.($withproject ? '&withproject=1#comment' : ''));
			exit;
		} else {
			setEventMessages($comment->error, $comment->errors, 'errors');
			$action = '';
		}
	}
}
if ($action == 'deletecomment') {
	if ($comment->fetch($idcomment) >= 0) {
		if ($comment->delete($user) > 0) {
			setEventMessages($langs->trans("CommentDeleted"), null, 'mesgs');
			header('Location: '.$varpage.'?id='.$id.($withproject ? '&withproject=1' : ''));
			exit;
		} else {
			setEventMessages($comment->error, $comment->errors, 'errors');
			$action = '';
		}
	}
}
