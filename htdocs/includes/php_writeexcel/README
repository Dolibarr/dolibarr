What is it all about?
---------------------
php_writeexcel is a port of John McNamara's excellent Spreadsheet::WriteExcel
Perl package to PHP. It allows you to generate Microsoft Excel documents on
your PHP enabled Web server without any other tools.



Where to start
--------------
I've included six example PHP scripts which are also taken out of the
Spreadsheet::WriteExcel package and ported to PHP:
- example-simple.php
- example-merge2.php
- example-stocks.php
- example-textwrap.php
- example-demo.php
- example-bigfile.php
All you have to do is to tar xzvf the package to a directory accessible
via a web server. Then fetch any of the example scripts with your
favourite web browser and Excel should come into place and show you
a sheet.



Problems of porting Perl code to PHP
------------------------------------
When you take a first look at both languages, they seem to be very
similar: Variable names begin with a dollar sign, control structures
look like in C. You don't have to change most of these things.

Then, there are things which can be done by find and replace: Perl subs
are functions in PHP. Perl properties like $this->{prop} are $this->prop
in PHP. "elsif" in Perl is "elseif" in PHP. Even porting Perl's so called
OOP is not very difficult: just delete the "my $this=shift;" at the
beginning of each method and then put all the methods into a class {} block.
Just be aware that in Perl not the function declaration decides if the
function is a method or not, but the call of the function. If you say
myfunc(), it's a function, if you say $obj->myfunc(), it's a method.

Then the trouble begins. There were three major things I had problems
with while porting SpreadSheet::WriteExcel to PHP:
- function (sub) handling:
  In Perl's list philosophy the number of paramaters and return values
  are variable by design: Just use myfunc(@myarray) to pass a complete
  array to the function and the array's values will be the function's
  arguments.
  In PHP this is possible though undesirable as you have to use ugly
  things like call_user_function_array() and call_user_method_array()
  if you want to translate the Perl code literally.
- Reference juggling:
  This can be very confusing when reading Perl code making
  excessive use of it. References are supported by PHP, but it's
  hard to translate them mainly due to to PHP's habit to copy
  EVERYTHING on any assignment. Even if you do a "$obj=new myclass();",
  PHP will create a copy of the instantiated class. It's sometimes
  hard to recognize when you have to use the "=&" operator to create
  a reference instead of a copy. And, hey, when the hell will the
  PHP developers implement a foreach loop which assigns the array
  values by reference?? :-(
- Perl's AUTOLOAD method - PHP does not have such a thing. When
  a class has many properties and the AUTOLOAD method is used
  to simulate a set_xxx() for each property, you'll have to define all
  possible function calls manually.



What features are currently supported?
--------------------------------------
Basically all features of Spreadsheet::WriteExcel are supported and
thanks to a lot of people (see CHANGELOG) who contributed code and bug
fixes, most things seem to work. However, please remember that it's still
in beta stage, so there are probably some bugs left.

Spreadsheet::WriteExcel uses the Parse::RecDescent package for formula
support. Andreas Brodowski has imported and changed the PEAR::Spreadsheet
Parser.php file, so formulas are supported since version 0.2.2.

Spreadsheet::WriteExcel uses the OLE::Storage_Lite package for
supporting Excel files bigger than approx. 7 MB. I have ported this
package already and called it php_ole. But I really don't know how
reliable it is, so use it with care!



Documentation
-------------
Sorry, there is no documentation yet. You'll have to use the documentation
for Spreadsheet::WriteExcel available at
http://search.cpan.org/doc/JMCNAMARA/Spreadsheet-WriteExcel-0.37/WriteExcel/doc/WriteExcel.html
for now. Try to translate the Perl code into PHP with the help of the
examples. Please note that you have to assign variables by reference
(using the =& operator) quite often!



Reporting bugs
--------------
If you've found a bug, please send a bug report to jonny@nurfuerspam.de.
Please include "php_writeexcel" in the subject so that I can search for bug
reports by searching for that string.
Please note that my email address has changed. Several mails have been lost,
so if you patch didn't get it, please resend it.
If you get error messages, please include them in your report. Please also
check your Apache error_log for messages coming from php_writeexcel.
If you get corrupted Excel files, please include them in your email. If
one of the demos generates a corrupted file please try to find out the
differences between your file and the file you are getting from the
online demo at the php_writeexcel homepage!




Syntax different from SpreadSheet::WriteExcel
---------------------------------------------
The worksheet methods set_h_pagebreaks() and set_v_pagebreaks() take
exactly one argument which must be an array with the desired page breaks.
