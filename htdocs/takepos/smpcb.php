<?php
    require '../main.inc.php';

if (isset($_GET['status'])) {
	die(strtoupper($_SESSION['SMP_CURRENT_PAYMENT']));
}

if ($_GET['smp-status']) {
	print '<html lang="en">
<head>
    <meta charset="utf-8">

    <title>The HTML5 Herald</title>
    <meta name="description" content="The HTML5 Herald">
    <meta name="author" content="SitePoint">

    <link rel="stylesheet" href="css/styles.css?v=1.0">

</head>

<body>';
	$_SESSION['SMP_CURRENT_PAYMENT'] = $_GET['smp-status'];

	print '<script type="application/javascript">
                window.onload = function() {
                    window.close();
                };
            </script>';

	print "Transaction status registered, you can close this";

	die('</body></html>');
}

    print 'NOOP';
