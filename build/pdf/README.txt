To make htmldoc working from wiki.dolibarr.org, them must be modified to have

$_SERVER["HTTP_USER_AGENT"] is "HTMLDOC/x.y.z"
$_COOKIE["htmldoc"] may also be defined if set on command line.

To disable part, add 
class="htmldoc-ignore" with css
.htmldoc-ignore { display: none; }

