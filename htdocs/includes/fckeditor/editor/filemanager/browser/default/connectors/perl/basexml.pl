#####
#  FCKeditor - The text editor for internet
#  Copyright (C) 2003-2006 Frederico Caldeira Knabben
#  
#  Licensed under the terms of the GNU Lesser General Public License:
#  		http://www.opensource.org/licenses/lgpl-license.php
#  
#  For further information visit:
#  		http://www.fckeditor.net/
#  
#  "Support Open Source software. What about a donation today?"
#  
#  File Name: basexml.pl
#  	This is the File Manager Connector for Perl.
#  
#  File Authors:
#  		Takashi Yamaguchi (jack@omakase.net)
#####

sub CreateXmlHeader
{
	local($command,$resourceType,$currentFolder) = @_;

	# Create the XML document header.
	print '<?xml version="1.0" encoding="utf-8" ?>';

	# Create the main "Connector" node.
	print '<Connector command="' . $command . '" resourceType="' . $resourceType . '">';

	# Add the current folder node.
	print '<CurrentFolder path="' . ConvertToXmlAttribute($currentFolder) . '" url="' . ConvertToXmlAttribute(GetUrlFromPath($resourceType,$currentFolder)) . '" />';
}

sub CreateXmlFooter
{
	print '</Connector>';
}

sub SendError
{
	local( $number, $text ) = @_;

	print << "_HTML_HEAD_";
Content-Type:text/xml; charset=utf-8
Pragma: no-cache
Cache-Control: no-cache
Expires: Thu, 01 Dec 1994 16:00:00 GMT

_HTML_HEAD_

	# Create the XML document header
	print '<?xml version="1.0" encoding="utf-8" ?>' ;
	
	print '<Connector><Error number="' . $number . '" text="' . &specialchar_cnv( $text ) . '" /></Connector>' ;
	
	exit ;
}

1;
