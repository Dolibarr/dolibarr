<?php
// Authors:
// - cadorn, Christoph Dorn <christoph@christophdorn.com>, Copyright 2007, New BSD License
// - cadorn, Christoph Dorn <christoph@christophdorn.com>, Copyright 2011, MIT License

/* *** BEGIN LICENSE BLOCK *****
 *  
 * [MIT License](http://www.opensource.org/licenses/mit-license.php)
 * 
 * Copyright (c) 2007+ [Christoph Dorn](http://www.christophdorn.com/)
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 * 
 * ***** END LICENSE BLOCK ***** */


/* NOTE: You must have the FirePHPCore library in your include path */

set_include_path(dirname(dirname(__FILE__)).'/lib'.PATH_SEPARATOR.get_include_path());


require('FirePHPCore/fb.php');

/* NOTE: You must have Output Buffering enabled via
         ob_start() or output_buffering ini directive. */

fb('Hello World'); /* Defaults to FirePHP::LOG */

fb('Log message'  ,FirePHP_LOG);
fb('Info message' ,FirePHP_INFO);
fb('Warn message' ,FirePHP_WARN);
fb('Error message',FirePHP_ERROR);

fb('Message with label','Label',FirePHP_LOG);

fb(array('key1'=>'val1',
         'key2'=>array(array('v1','v2'),'v3')),
   'TestArray',FirePHP_LOG);

fb('Backtrace to here',FirePHP_TRACE);

fb(array('2 SQL queries took 0.06 seconds',array(
   array('SQL Statement','Time','Result'),
   array('SELECT * FROM Foo','0.02',array('row1','row2')),
   array('SELECT * FROM Bar','0.04',array('row1','row2'))
  )),FirePHP_TABLE);

/* Will show only in "Server" tab for the request */
fb(apache_request_headers(),'RequestHeaders',FirePHP_DUMP);


print 'Hello World';

