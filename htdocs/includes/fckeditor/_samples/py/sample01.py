#!/usr/bin/env python

"""
FCKeditor - The text editor for internet
Copyright (C) 2003-2006 Frederico Caldeira Knabben

Licensed under the terms of the GNU Lesser General Public License:
		http://www.opensource.org/licenses/lgpl-license.php

For further information visit:
		http://www.fckeditor.net/

"Support Open Source software. What about a donation today?"

File Name: sample01.py
	Sample page.

File Authors:
		Andrew Liu (andrew@liuholdings.com)
"""

import cgi
import os

# Ensure that the fckeditor.py is included in your classpath
import fckeditor

# Tell the browser to render html
print "Content-Type: text/html"
print ""

# Document header
print """<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>FCKeditor - Sample</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<h1>FCKeditor - Python - Sample 1</h1>
		This sample displays a normal HTML form with an FCKeditor with full features 
		enabled.
		<hr>
		<form action="sampleposteddata.py" method="post" target="_blank">
"""

# This is the real work
try: 
	sBasePath = os.environ.get("SCRIPT_NAME")
	sBasePath = sBasePath[0:sBasePath.find("_samples")]

	oFCKeditor = fckeditor.FCKeditor('FCKeditor1')
	oFCKeditor.BasePath = sBasePath
	oFCKeditor.Value = """This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>."""
	print oFCKeditor.Create()
except Exception, e:
	print e
print """
			<br>
			<input type="submit" value="Submit">
		</form>
"""

# For testing your environments
print "<hr>"
for key in os.environ.keys():
	print "%s: %s<br>" % (key, os.environ.get(key, ""))
print "<hr>"

# Document footer
print """
	</body>
</html>
"""


