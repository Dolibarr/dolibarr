<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <title>Dolibarr 404 error page</title>
  </head>

  <body>

    <div>

    <!-- <div data-role="header" data-theme="b">
            <h1>Introduction</h1>
            <a href="../../" data-icon="home" data-iconpos="notext" data-direction="reverse" class="ui-btn-right jqm-home">Home</a>
        </div>
    -->
    <div>

    <h2>Error</h2>

    <br>
    You requested a website or a page that does not exists.

    <br>
    <?php print isset($_SERVER["HTTP_REFERER"]) ? 'You come from '.htmlentities($_SERVER["HTTP_REFERER"]).'.' : ''; ?>

    <hr>

    </div>
    </div>

  </body>
</html>
