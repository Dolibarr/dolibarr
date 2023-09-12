<?php
    // Protection to avoid direct call of template
    if (empty($context) || ! is_object($context)) {
        print "Error, template page can't be called as URL";
        exit;
    }

    global $langs;
?>
<!DOCTYPE html>
<?php print '<html lang="' . substr($langs->defaultlang, 0, 2) . '">' . "\n" ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>
        <?php
        if (!empty($context->title)) {
            print $context->title;
        } else {
            print 'WebPortal';
        }
        ?>
    </title>
    <link rel="stylesheet" href="<?php print $context->rootUrl.'css/global.css'; ?>">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.2.0/css/all.css">
    <?php
        // JNotify
        print '<link rel="stylesheet" href="'.$context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.css">';
    ?>
    <style>
        .login-page {
            /* comment this var to remove image and see adaptative linear background */
            /*--login-background : rgba(0,0,0,0.4) url("https://images.unsplash.com/photo-1552526408-fa252623b415?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w0MDEyNzJ8MHwxfHNlYXJjaHwxNnx8cHVycGxlJTIwZmxvd2VyfGVufDB8MHx8fDE2ODc5NDY5NjB8MA&ixlib=rb-4.0.3&q=80&w=1080&w=1920");*/
            --login-background: rgba(0, 0, 0, 0.4) url("https://images.unsplash.com/photo-1609607224685-44a36db58b10?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=1080&w=1920");
            --login-logo: url("../tpl/dolibarr_logo.svg");/* for relative path, must be relative od the css file or use full url starting by http:// */
        }
    </style>
    <?php
        // JQuery
        print '<script src="'.$context->rootUrl.'includes/jquery/js/jquery.js"></script>';
        // JNotify
        print '<script src="'.$context->rootUrl.'includes/jquery/plugins/jnotify/jquery.jnotify.js"></script>';
    ?>
</head>
<body class="login-page">