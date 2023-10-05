<?php
if (!defined('NOREQUIRESOC')) {
	define('NOREQUIRESOC', '1');
}
if (!defined('NOCSRFCHECK')) {
	define('NOCSRFCHECK', 1);
}
if (!defined('NOTOKENRENEWAL')) {
	define('NOTOKENRENEWAL', 1);
}
if (!defined('NOLOGIN')) {
	define('NOLOGIN', 1); // File must be accessed by logon page so without login
}
if (!defined('NOREQUIREHTML')) {
	define('NOREQUIREHTML', 1);
}
if (!defined('NOREQUIREAJAX')) {
	define('NOREQUIREAJAX', '1');
}
if (!defined('NOSESSION')) {
	define('NOSESSION', '1');
}
if (!defined('NOREQUIREMENU')) {
	define('NOREQUIREMENU', '1');
}
session_cache_limiter('public');

require_once '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

// Security
if ($dolibarr_main_prod) {
	accessforbidden();
}


/*
 * View
 */

llxHeader('', 'Documentation and examples for theme');
?>
<main  role="main"  >
	<h1 class="bd-title" id="content">Button for action</h1>
	<p class="bd-lead">Documentation and examples for buttons.</p>

	<h2 id="example01">Example of simple usage</h2>

	<p>Buttons for user allowed to click.</p>

	<div class="bd-example">
	<?php
		$n = 1;
		$label = 'My action label used for accessibility visually for impaired people';
		$html = '<span class="fa fa-clone" ></span> My default action';
		$actionType = 'default';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = '#'.$id;
		$userRight = 1;
		$params = array();

		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight);


		$html = '<span class="fa fa-clone" ></span> My delete action';
		$actionType = 'delete';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = $_SERVER['PHP_SELF'] . '?token='.newToken().'#'.$id;
		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight);


		$html = '<span class="fa fa-clone" ></span> My danger action';
		$actionType = 'danger';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = $_SERVER['PHP_SELF'] . '?token='.newToken().'#'.$id;
		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight);

	?>
	</div>

	<p>Buttons for user <strong>NOT</strong> allowed to click.</p>

	<div class="bd-example">
	<?php
		$label = 'My action label used for accessibility visually for impaired people';
		$html = '<span class="fa fa-clone" ></span> My default action';
		$actionType = 'default';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = '#'.$id;
		$userRight = 0;

		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight);


		$html = '<span class="fa fa-clone" ></span> My delete action';
		$actionType = 'delete';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = $_SERVER['PHP_SELF'] . '?token='.newToken().'#'.$id;
		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight);


		$html = '<span class="fa fa-clone" ></span> My danger action';
		$actionType = 'danger';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = $_SERVER['PHP_SELF'] . '?token='.newToken().'#'.$id;
		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight);

	?>
	</div>


	<h2 id="example01">Example of confirm dialog</h2>

	<p>Buttons for user allowed to click.</p>

	<div class="bd-example">
		<?php
		$label = 'My action label used for accessibility visually for impaired people';
		$html = '<span class="fa fa-clone" ></span> My default action';
		$actionType = 'default';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = '#'.$id;
		$userRight = 1;
		$params = array(
			'confirm' => true
		);

		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);


		$html = '<span class="fa fa-clone" ></span> My delete action';
		$actionType = 'delete';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = $_SERVER['PHP_SELF'] . '?token='.newToken().'#'.$id;

		$params = array(
			'confirm' => array(
				'url' => 'your confirm action url',
				'title' => 'Your title to display',
				'action-btn-label' => 'Your confirm label',
				'cancel-btn-label' => 'Your cancel label',
				'content' => 'Content to display  with <strong>HTML</strong> compatible <ul><li>test 01</li><li>test 02</li><li>test 03</li></ul>'
			)
		);

		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);

		?>
	</div>

	<p>Buttons for user <strong>NOT</strong> allowed to click.</p>

	<div class="bd-example">
		<?php
		$label = 'My action label used for accessibility visually for impaired people';
		$html = '<span class="fa fa-clone" ></span> My default action';
		$actionType = 'default';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = '#'.$id;
		$userRight = 0;
		$params = array(
			'confirm' => true
		);

		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);


		$html = '<span class="fa fa-clone" ></span> My delete action';
		$actionType = 'delete';
		$n++;
		$id = 'mybuttonid'.$n;
		$url = $_SERVER['PHP_SELF'] . '?token='.newToken().'#'.$id;

		$params = array(
			'confirm' => array(
				'url' => 'your confirm action url',
				'title' => 'Your title to display',
				'action-btn-label' => 'Your confirm label',
				'cancel-btn-label' => 'Your cancel label',
				'content' => 'Content to display  with <strong>HTML</strong> compatible <ul><li>test 01</li><li>test 02</li><li>test 03</li></ul>'
			)
		);

		print dolGetButtonAction($label, $html, $actionType, $url, $id, $userRight, $params);

		?>
	</div>


</main>

<?php llxFooter();
