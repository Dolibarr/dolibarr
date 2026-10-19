<!-- file hero-header-banner.tpl.php -->
<?php
// Protection to avoid direct call of template
if (empty($context) || !is_object($context)) {
	print "Error, template page can't be called as URL";
	exit(1);
}
'@phan-var-force Context $context';
?>
<section class="hero-header" <?php print !empty($context->theme->bannerUseDarkTheme) ? ' data-theme="dark" ': '' ?> >
	<div class="container">
		<h1 class="hero-header__title"><?php print $context->title; ?></h1>
		<div class="hero-header__desc"><?php print $context->desc; ?></div>
	</div>
</section>
