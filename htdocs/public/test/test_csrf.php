<?php


?>

This is a form to test if a CSRF exists into a Dolibarr page.<br>
<br>
- Change url to send request to into this file (server B, hard coded page)<br>
- Open this form into a virtual server A.<br>
- Send the request to the virtual server B by clicking submit.<br>
- Check that Anticsrf protection is triggered.<br>

<br>
<?php
	$urltosendrequest = "http://127.0.0.1/dolibarr/htdocs/user/group/card.php";
	print 'urltosendrequest = '.$urltosendrequest.'<br><br>';
?>

<form method="POST" action="<?php echo $urltosendrequest; ?>" target="_blank">
<!-- <input type="hidden" name="token" value="123456789"> -->
<input type="text" name="action" value="add">
<input type="text" name="nom" value="New group test">
<input type="submit" name="submit" value="Submit">
</form>