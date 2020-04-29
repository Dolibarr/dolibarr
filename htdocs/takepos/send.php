<?php
/* Copyright (C) 2019	Thibault FOUCART      <support@ptibogxiv.net>
 * Copyright (C) 2020	Andreu Bisquerra Gaya <jove@bisquerra.com>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/takepos/send.php
 *	\ingroup	takepos
 *	\brief      Page with the content of the popup to enter payments
 */

//if (! defined('NOREQUIREUSER'))	define('NOREQUIREUSER', '1');	// Not disabled cause need to load personalized language
//if (! defined('NOREQUIREDB'))		define('NOREQUIREDB', '1');		// Not disabled cause need to load personalized language
//if (! defined('NOREQUIRESOC'))		define('NOREQUIRESOC', '1');
//if (! defined('NOREQUIRETRAN'))		define('NOREQUIRETRAN', '1');
if (!defined('NOCSRFCHECK'))		define('NOCSRFCHECK', '1');
if (!defined('NOTOKENRENEWAL'))	define('NOTOKENRENEWAL', '1');
if (!defined('NOREQUIREMENU'))		define('NOREQUIREMENU', '1');
if (!defined('NOREQUIREHTML'))		define('NOREQUIREHTML', '1');
if (!defined('NOREQUIREAJAX'))		define('NOREQUIREAJAX', '1');

require '../main.inc.php'; // Load $user and permissions
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';

$facid = GETPOST('facid', 'int');
$action = GETPOST('action', 'alpha');
$email = GETPOST('email', 'alpha');

if (empty($user->rights->takepos->run)) {
	accessforbidden();
}

$langs->loadLangs(array("main", "bills", "cashdesk"));

$invoice = new Facture($db);
$invoice->fetch($facid);
$customer = new Societe($db);
$customer->fetch($invoice->socid);

if ($action=="send")
{
    include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
    include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
    $formmail = new FormMail($db);
    $outputlangs = new Translate('', $conf);
    $model_id = $conf->global->TAKEPOS_EMAIL_TEMPLATE_INVOICE;
    $arraydefaultmessage = $formmail->getEMailTemplate($db, 'facture_send', $user, $outputlangs, $model_id);
    $subject = $arraydefaultmessage->topic;
    ob_start(); // turn on output receipt
    include 'receipt.php';
    $receipt = ob_get_contents(); // get the contents of the output buffer
    ob_end_clean();
    $msg="<html>".$arraydefaultmessage->content."<br>".$receipt."</html>";
    $sendto=$email;
    $from=$mysoc->email;
    $mail = new CMailFile($subject, $sendto, $from, $msg, array(), array(), array(), '', '', 0, 1);
    if ($mail->error || $mail->errors) {
        setEventMessages($mail->error, $mail->errors, 'errors');
    } else {
        $result = $mail->sendfile();
    }
    exit;
}
$arrayofcss = array('/takepos/css/pos.css.php');
$arrayofjs  = array();
top_htmlhead($head, $title, $disablejs, $disablehead, $arrayofjs, $arrayofcss);
?>
</head>
<body class="center">

<script>
function SendMail() {
	$.ajax({
        type: "GET",
        url: "<?php print dol_buildpath('/takepos/send.php', 1).'?action=send&facid='.$facid.'&email='; ?>" + $("#email"). val(),
    });
	parent.$.colorbox.close();
}

</script>

<div class="center">
<center>
<center>
<input type="email" id="email" name="email" style="width:60%;font-size: 200%;" value="<?php echo $customer->email; ?>"></center>
</center>
</div>
<br>
<div class="center">

<button type="button" class="calcbutton"  onclick="SendMail()"><?php print $langs->trans("SendTicket"); ?></button>

</div>

</body>
</html>
