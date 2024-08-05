<!-- file login.tpl.php -->
<?php
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}

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
					<i class="login__icon fas fa-lock"></i>
					<input type="password" class="login__input" name="password" placeholder="<?php print dol_escape_htmltag($langs->trans('Password')) ?>">
				</div>
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
