<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Dolibarr 404 error page</title>
  </head>

  <body>
    <h2>Error</h2>

    <br>
    You requested a page that does not exists.

    <br>
    <?php print isset($_SERVER["HTTP_REFERER"])?'You come from '.$_SERVER["HTTP_REFERER"].'.':''; ?>

    <hr>
  </body>
</html>
