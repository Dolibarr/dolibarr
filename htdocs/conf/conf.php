<?php
if (php_uname ('s') == "Darwin")
{
	require __DIR__."/conf.GLC.mac.php";
}
else
{
	require __DIR__."/conf.GLC.ubuntu.php";
}
