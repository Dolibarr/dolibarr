#!/usr/bin/env python

"""
FCKeditor - The text editor for internet
Copyright (C) 2003-2006 Frederico Caldeira Knabben

Licensed under the terms of the GNU Lesser General Public License:
		http://www.opensource.org/licenses/lgpl-license.php

For further information visit:
		http://www.fckeditor.net/

"Support Open Source software. What about a donation today?"

File Name: sampleposteddata.py
	This page lists the data posted by a form.

File Authors:
		Andrew Liu (andrew@liuholdings.com)
"""

import cgi
import os

# Tell the browser to render html
print "Content-Type: text/html"
print ""

try:
	# Create a cgi object
	form = cgi.FieldStorage()
except Exception, e:
	print e

# Document header
print """<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>FCKeditor - Samples - Posted Data</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
"""

# This is the real work 
print """
		<h1>FCKeditor - Samples - Posted Data</h1>
		This page lists all data posted by the form.
		<hr>
		<table width="100%" border="1" cellspacing="0" bordercolor="#999999">
			<tr style="FONT-WEIGHT: bold; COLOR: #dddddd; BACKGROUND-COLOR: #999999">
				<td nowrap>Field Name&nbsp;&nbsp;</td>
				<td>Value</td>
			</tr>
"""
for key in form.keys():
	try:
		value = form[key].value
		print """
				<tr>
					<td valign="top" nowrap><b>%s</b></td>
					<td width="100%%">%s</td>
				</tr>
			""" % (key, value)
	except Exception, e:
		print e
print "</table>"

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
