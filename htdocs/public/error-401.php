<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
	<title>Dolibarr 401 error page</title>
  </head>

  <body>

	<div>

	<!-- <div data-role="header" data-theme="b">
			<h1>Introduction</h1>
			<a href="../../" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
		</div>
	-->
	<div>

	<h1>Error</h1>

	<br>
	Sorry. You are not allowed access to this resource.

	<br>
	<br>

	No esta autorizado para acceder a este recurso.

	<br>
	<?php print isset($_SERVER["HTTP_REFERER"]) ? 'You arrived from '.htmlentities($_SERVER["HTTP_REFERER"], ENT_COMPAT, 'UTF-8').'.' : ''; ?>

	<hr>

	</div>
	</div>

  </body>
</html>
