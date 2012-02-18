<?php
/* Copyright (C) 2009-2010 Regis Houssin <regis@dolibarr.fr>
 * Copyright (C) 2011      Laurent Destailleur <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

header('Cache-Control: Public, must-revalidate');
header("Content-type: text/html; charset=".$conf->file->character_set_client);

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!-- BEGIN PHP TEMPLATE -->
<html>

<?php
print '<head>
<meta name="robots" content="noindex,nofollow" />
<meta name="author" content="Dolibarr Development Team">
<link rel="shortcut icon" type="image/x-icon" href="'.$favicon.'"/>
<title>'.$langs->trans('Login').' '.$title.'</title>'."\n";
print '<!-- Includes for JQuery (Ajax library) -->'."\n";
if (constant('JS_JQUERY_UI')) print '<link rel="stylesheet" type="text/css" href="'.JS_JQUERY_UI.'css/'.$jquerytheme.'/jquery-ui.min.css" />'."\n";  // JQuery
else print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.'/includes/jquery/css/'.$jquerytheme.'/jquery-ui-latest.custom.css" />'."\n";    // JQuery
// JQuery. Must be before other includes
print '<!-- Includes JS for JQuery -->'."\n";
if (constant('JS_JQUERY')) print '<script type="text/javascript" src="'.JS_JQUERY.'jquery.min.js"></script>'."\n";
else print '<script type="text/javascript" src="'.DOL_URL_ROOT.'/includes/jquery/js/jquery-latest.min'.$ext.'"></script>'."\n";
print '<link rel="stylesheet" type="text/css" href="'.$conf_css.'" />
<style type="text/css">
<!--
#login {
	margin-top: 70px;
	margin-bottom: 30px;
}
#login table {
	width: 512px;
	border: 1px solid #C0C0C0;
	background: #F0F0F0 url('.$login_background.') repeat-x;
}
-->
</style>'."\n";
if (! empty($conf->global->MAIN_HTML_HEADER)) print $conf->global->MAIN_HTML_HEADER;
print '<!-- HTTP_USER_AGENT = '.$_SERVER['HTTP_USER_AGENT'].' -->
</head>';

?>

<body class="body">

<!-- Javascript code on logon page only to detect user tz, dst_observed, dst_first, dst_second -->
<script type="text/javascript">
function DisplayDstSwitchDates(firstsecond)
{
    var year = new Date().getYear();
    if (year < 1000) year += 1900;

    var firstSwitch = 0;
    var secondSwitch = 0;
    var lastOffset = 99;

    // Loop through every month of the current year
    for (i = 0; i < 12; i++)
    {
        // Fetch the timezone value for the month
        var newDate = new Date(Date.UTC(year, i, 0, 0, 0, 0, 0));
        var tz = -1 * newDate.getTimezoneOffset() / 60;

        // Capture when a timzezone change occurs
        if (tz > lastOffset)
            firstSwitch = i-1;
        else if (tz < lastOffset)
            secondSwitch = i-1;

        lastOffset = tz;
    }

    // Go figure out date/time occurences a minute before
    // a DST adjustment occurs
    var secondDstDate = FindDstSwitchDate(year, secondSwitch);
    var firstDstDate = FindDstSwitchDate(year, firstSwitch);

	if (firstsecond == 'first') return firstDstDate;
	if (firstsecond == 'second') return secondDstDate;

    if (firstDstDate == null && secondDstDate == null)
        return 'Daylight Savings is not observed in your timezone.';
    else
        return 'Last minute before DST change occurs in ' +
           year + ': ' + firstDstDate + ' and ' + secondDstDate;
}

function FindDstSwitchDate(year, month)
{
    // Set the starting date
    var baseDate = new Date(Date.UTC(year, month, 0, 0, 0, 0, 0));
    var changeDay = 0;
    var changeMinute = -1;
    var baseOffset = -1 * baseDate.getTimezoneOffset() / 60;
    var dstDate;

    // Loop to find the exact day a timezone adjust occurs
    for (day = 0; day < 50; day++)
    {
        var tmpDate = new Date(Date.UTC(year, month, day, 0, 0, 0, 0));
        var tmpOffset = -1 * tmpDate.getTimezoneOffset() / 60;

        // Check if the timezone changed from one day to the next
        if (tmpOffset != baseOffset)
        {
            var minutes = 0;
            changeDay = day;

            // Back-up one day and grap the offset
            tmpDate = new Date(Date.UTC(year, month, day-1, 0, 0, 0, 0));
            tmpOffset = -1 * tmpDate.getTimezoneOffset() / 60;

            // Count the minutes until a timezone chnage occurs
            while (changeMinute == -1)
            {
                tmpDate = new Date(Date.UTC(year, month, day-1, 0, minutes, 0, 0));
                tmpOffset = -1 * tmpDate.getTimezoneOffset() / 60;

                // Determine the exact minute a timezone change
                // occurs
                if (tmpOffset != baseOffset)
                {
                    // Back-up a minute to get the date/time just
                    // before a timezone change occurs
                    tmpOffset = new Date(Date.UTC(year, month,
                                         day-1, 0, minutes-1, 0, 0));
                    changeMinute = minutes;
                    break;
                }
                else
                    minutes++;
            }

            // Add a month (for display) since JavaScript counts
            // months from 0 to 11
            dstDate = tmpOffset.getMonth() + 1;

            // Pad the month as needed
            if (dstDate < 10) dstDate = "0" + dstDate;

            // Add the day and year
            dstDate = year + '-' + dstDate + '-' + tmpOffset.getDate() + 'T';

            // Capture the time stamp
            tmpDate = new Date(Date.UTC(year, month,
                               day-1, 0, minutes-1, 0, 0));
            dstDate += tmpDate.toTimeString().split(' ')[0] + 'Z';
            return dstDate;
        }
    }
}

jQuery(document).ready(function () {
	// Set focus on correct field
	<?php if ($focus_element) { ?>jQuery('#<?php echo $focus_element; ?>').focus(); <?php } ?>		// Warning to use this only on visible element
	// Detect and save TZ and DST
	var rightNow = new Date();
	var jan1 = new Date(rightNow.getFullYear(), 0, 1, 0, 0, 0, 0);
	var temp = jan1.toGMTString();
	var jan2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
	var std_time_offset = (jan1 - jan2) / (1000 * 60 * 60);
	var june1 = new Date(rightNow.getFullYear(), 6, 1, 0, 0, 0, 0);
	temp = june1.toGMTString();
	var june2 = new Date(temp.substring(0, temp.lastIndexOf(" ")-1));
	var daylight_time_offset = (june1 - june2) / (1000 * 60 * 60);
	var dst;
	if (std_time_offset == daylight_time_offset) {
	    dst = "0"; // daylight savings time is NOT observed
	} else {
	    dst = "1"; // daylight savings time is observed
	}
	var dst_first=DisplayDstSwitchDates('first');
	var dst_second=DisplayDstSwitchDates('second');
	//alert(dst);
	jQuery('#tz').val(std_time_offset);   				  // returns TZ
	jQuery('#dst_observed').val(dst);   				  // returns if DST is observed on summer
	jQuery('#dst_first').val(dst_first);   				  // returns DST first switch in year
	jQuery('#dst_second').val(dst_second);   			  // returns DST second switch in year
	// Detect and save screen resolution
	jQuery('#screenwidth').val(jQuery(window).width());   // returns width of browser viewport
	jQuery('#screenheight').val(jQuery(window).height());   // returns width of browser viewport
});
</script>

<form id="login" name="login" method="post" action="<?php echo $php_self; ?>">
<input type="hidden" name="token" value="<?php echo $_SESSION['newtoken']; ?>" />
<input type="hidden" name="loginfunction" value="loginfunction" />
<!-- Add fields to send local user information -->
<input type="hidden" name="tz" id="tz" value="" />
<input type="hidden" name="dst_observed" id="dst_observed" value="" />
<input type="hidden" name="dst_first" id="dst_first" value="" />
<input type="hidden" name="dst_second" id="dst_second" value="" />
<input type="hidden" name="screenwidth" id="screenwidth" value="" />
<input type="hidden" name="screenheight" id="screenheight" value="" />

<table class="login" summary="<?php echo $title; ?>" cellpadding="0" cellspacing="0" border="0" align="center">
<tr class="vmenu"><td align="center"><?php echo $title; ?></td></tr>
</table>
<br>

<table class="login" summary="Login area" cellpadding="2" align="center">

<tr><td colspan="3">&nbsp;</td></tr>

<tr>

<td valign="bottom"> &nbsp; <strong><label for="username"><?php echo $langs->trans('Login'); ?></label></strong> &nbsp; </td>
<td valign="bottom" nowrap="nowrap">
<input type="text" id="username" name="username" class="flat" size="15" maxlength="40" value="<?php echo GETPOST('username')?GETPOST('username'):$login; ?>" tabindex="1" /></td>

<td rowspan="<?php echo $rowspan; ?>" align="center" valign="top">
<img alt="Logo" title="" src="<?php echo $urllogo; ?>" />

</td>
</tr>

<tr><td valign="top" nowrap="nowrap"> &nbsp; <strong><label for="password"><?php echo $langs->trans('Password'); ?></label></strong> &nbsp; </td>
<td valign="top" nowrap="nowrap">
<input id="password" name="password" class="flat" type="password" size="15" maxlength="30" value="<?php echo $password; ?>" tabindex="2" />
</td></tr>

	<?php if ($select_entity) { ?>
		<tr><td valign="top" nowrap="nowrap"> &nbsp; <strong><?php echo $langs->trans('Entity'); ?></strong> &nbsp; </td>
		<td valign="top" nowrap="nowrap">
		<?php echo $select_entity; ?>
		</td></tr>
	<?php } ?>

	<?php if ($captcha) { ?>
		<tr><td valign="middle" nowrap="nowrap"> &nbsp; <b><?php echo $langs->trans('SecurityCode'); ?></b></td>
		<td valign="top" nowrap="nowrap" align="left" class="none">

		<table style="width: 100px;"><tr>
		<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="4" /></td>
		<td><img src="<?php echo DOL_URL_ROOT ?>/core/antispamimage.php" border="0" width="80" height="32" /></td>
		<td><a href="<?php echo $php_self; ?>"><?php echo $captcha_refresh; ?></a></td>
		</tr></table>

		</td></tr>
	<?php } ?>

<tr><td colspan="3">&nbsp;</td></tr>

<tr><td colspan="3" style="text-align:center;"><br>
<input type="submit" class="button" value="&nbsp; <?php echo $langs->trans('Connection'); ?> &nbsp;" tabindex="5" />
</td></tr>

<?php
	if ($forgetpasslink || $helpcenterlink) {
		echo '<tr><td colspan="3" align="center">';
		if ($forgetpasslink) {
			echo '<a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/user/passwordforgotten.php">(';
			echo $langs->trans('PasswordForgotten');
			if (! $helpcenterlink) {
				echo ')';
			}
			echo '</a>';
		}

		if ($helpcenterlink) {
			echo '<a style="color: #888888; font-size: 10px" href="'.DOL_URL_ROOT.'/support/index.php" target="_blank">';
			if ($forgetpasslink) {
				echo '&nbsp;-&nbsp;';
			} else {
				echo '(';
			}
			echo $langs->trans('NeedHelpCenter').')</a>';
		}
		echo '</td></tr>';
	}
?>

</table>

</form>

	<?php if (! empty($_SESSION['dol_loginmesg']))
	{
	?>
		<center><table width="60%"><tr><td align="center"><div class="error">
		<?php echo $_SESSION['dol_loginmesg']; ?>
		</div></td></tr></table></center>
	<?php
    }
    ?>

	<?php if ($main_home)
	{
	?>
		<center><table summary="info" cellpadding="0" cellspacing="0" border="0" align="center" width="750">
		<tr><td align="center">
		<?php echo $main_home; ?>
		</td></tr></table></center><br>
	<?php
    }
    ?>

	<?php
	if (! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
	{
	?>
		<div align="center">
			<script type="text/javascript"><!--
				google_ad_client = "<?php echo $conf->global->MAIN_GOOGLE_AD_CLIENT ?>";
				google_ad_slot = "<?php echo $conf->global->MAIN_GOOGLE_AD_SLOT ?>";
				google_ad_width = <?php echo $conf->global->MAIN_GOOGLE_AD_WIDTH ?>;
				google_ad_height = <?php echo $conf->global->MAIN_GOOGLE_AD_HEIGHT ?>;
				//-->
			</script>
			<script type="text/javascript"
				src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>
		</div>
	<?php
    }
    ?>

<!-- authentication mode = <?php echo $main_authentication ?> -->
<!-- cookie name used for this session = <?php echo $session_name ?> -->
<!-- urlfrom in this session = <?php echo $_SESSION["urlfrom"] ?> -->

<?php if (! empty($conf->global->MAIN_HTML_FOOTER)) print $conf->global->MAIN_HTML_FOOTER; ?>

</body>
</html>
<!-- END PHP TEMPLATE -->
