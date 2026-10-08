<!-- file login.tpl.php -->
<?php
/* Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025       Frédéric France         <frederic.france@free.fr>
 */
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'@phan-var-force Context $context';
/**
 * @var Context $context
 * @var Translate $langs
 */

?>
<div class="login-page__container">
	<div class="login-screen">
		<div class="login-screen__content">
			<form class="login" method="POST">
				<?php echo $context->getFormToken(); ?>
				<input type="hidden" name="action_login" value="login">

				<div class="login__logo"><!-- see --login-logo css var to change logo --></div>

				<div class="login__field">
					<i class="login__icon fas fa-user"></i>
					<input type="text" class="login__input" name="login" placeholder="<?php print dol_escape_htmltag($langs->trans('loginWebportalUserName')); ?>">
				</div>
				<div class="login__field">
					<i class="login__icon fas fa-key"></i>
					<input type="password" class="login__input" name="password" placeholder="<?php print dol_escape_htmltag($langs->trans('Password')) ?>">
				</div>
				<?php if (getDolGlobalString('MAIN_SECURITY_ENABLECAPTCHA_WEBPORTAL')) { ?>
					<div class="login__field">
						<i class="login__icon fas fa-unlock"></i>
						<input type="password" class="login__input" style="width: 32%;" name="security_code" placeholder="<?php print dol_escape_htmltag($langs->trans('SecurityCode')) ?>">
						<img class="inline-block valignmiddle" src="<?php print  dol_buildpath('/public/webportal/antispamimage.php', 1); ?>" border="0" width="80" height="32" id="img_securitycode" />
						<a class="inline-block valignmiddle" href="<?php print $_SERVER['PHP_SELF']; ?>" tabindex="4" data-role="button"><?php print img_picto($langs->trans("Refresh"), 'refresh', 'id="captcha_refresh_img"'); ?></a>
					</div>
				<?php } ?>
				<button class="button login__submit">
					<span class="button__text"><?php print dol_escape_htmltag($langs->trans('Connection')) ?></span>
					<i class="button__icon fas fa-chevron-right"></i>
				</button>
			</form>
<!--			<div class="social-login">-->
<!--				<span class="social-login__title">Follow us on</span>-->
<!--				<div class="social-icons">-->
<!--					<a href="#" class="social-login__icon fab fa-instagram"></a>-->
<!--					<a href="#" class="social-login__icon fab fa-facebook"></a>-->
<!--					<a href="#" class="social-login__icon fab fa-twitter"></a>-->
<!--				</div>-->
<!--			</div>-->
		</div>
		<div class="login-screen__background">
			<span class="login-screen__background__shape login-screen__background__shape4"></span>
			<span class="login-screen__background__shape login-screen__background__shape3"></span>
			<span class="login-screen__background__shape login-screen__background__shape2"></span>
			<span class="login-screen__background__shape login-screen__background__shape1"></span>
		</div>
	</div>
</div>
