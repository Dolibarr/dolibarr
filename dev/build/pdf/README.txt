To make htmldoc working from wiki.dolibarr.org, the wiki must be modified to have

To disable part of content, add:
class="htmldoc-ignore"
with css
.htmldoc-ignore { display: none; }

Note:
$_SERVER["HTTP_USER_AGENT"] is "HTMLDOC/x.y.z"
$_COOKIE["htmldoc"] may also be defined if set on command line.
