<?php


?>

This is a form to test a CSRF.<br>
<br>
Open this form into a Virtual server A.<br>
Change url to send request to into file to send request to virtual server B.<br>

<br>
<?php
	$urltosendrequest = "http://localhostgit/dolibarr_dev/htdocs/user/group/card.php";
	print 'urltosendrequest = '.$urltosendrequest.'<br><br>';
?>

<form method="POST" action="<?php echo $urltosendrequest; ?>" target="_blank">
<!-- <input type="hidden" name="token" value="123456789"> -->
<input type="text" name="action" value="add">
<input type="text" name="nom" value="New group test">
<input type="submit" name="submit" value="Submit">
</form>