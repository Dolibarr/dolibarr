<?php

set_include_path(dirname(dirname(dirname(__FILE__))).'/lib'.PATH_SEPARATOR.get_include_path());
require('FirePHPCore/fb.php');


fb('Hello\nWorld');
fb(array('Hello\nWorld'));
fb(array('Table cell with newline',array(
   array('Header'),
   array('Hello\nWorld'),
)),FirePHP::TABLE);