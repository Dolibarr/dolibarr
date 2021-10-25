README (english)
---------------------------------------------
Decription of htdocs/core/login directory
---------------------------------------------

This directory contains files that handle way to validate passwords.

If you want to add a new password checker function, just add a file in
this directory that follow example of already existing files.
This file must be called for example : 
functions_mypasschecker.php

Edit function name to call it:
check_user_mypasschecker

Change code of this function to return true if couple 
$usertotest / $passwordtotest is ok for you.

Then, you must edit you conf.php file to change the value of
$dolibarr_main_authentication
parameter to set it to :
mypasschecker

Once this is done, when you log in to Dolibarr, the function 
check_user_mypasschecker in this file is called.
If the function return true and login exists, login is accepted.
