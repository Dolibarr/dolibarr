#!/usr/bin/env perl 

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
#  File Name: connector.cgi
#  	This is the File Manager Connector for Perl.
#  
#  File Authors:
#  		Takashi Yamaguchi (jack@omakase.net)
#  		Frederico Caldeira Knabben (fredck@fckeditor.net)
#####

##
# ATTENTION: To enable this connector, look for the "SECURITY" comment in this file.
##

## START: Hack for Windows (Not important to understand the editor code... Perl specific).
if(Windows_check()) {
	chdir(GetScriptPath($0));
}

sub Windows_check
{
	# IIS,PWS(NT/95)
	$www_server_os = $^O;
	# Win98 & NT(SP4)
	if($www_server_os eq "") { $www_server_os= $ENV{'OS'}; }
	# AnHTTPd/Omni/IIS
	if($ENV{'SERVER_SOFTWARE'} =~ /AnWeb|Omni|IIS\//i) { $www_server_os= 'win'; }
	# Win Apache
	if($ENV{'WINDIR'} ne "") { $www_server_os= 'win'; }
	if($www_server_os=~ /win/i) { return(1); }
	return(0);
}

sub GetScriptPath {
	local($path) = @_;
	if($path =~ /[\:\/\\]/) { $path =~ s/(.*?)[\/\\][^\/\\]+$/$1/; } else { $path = '.'; }
	$path;
}
## END: Hack for IIS

require 'util.pl';
require 'io.pl';
require 'basexml.pl';
require 'commands.pl';
require 'upload_fck.pl';

##
# SECURITY: REMOVE/COMMENT THE FOLLOWING LINE TO ENABLE THIS CONNECTOR.
##
&SendError( 1, 'This connector is disabled. Please check the "editor/filemanager/browser/default/connectors/perl/connector.cgi" file' ) ;

	&read_input();

	if($FORM{'ServerPath'} ne "") {
		$GLOBALS{'UserFilesPath'} = $FORM{'ServerPath'};
		if(!($GLOBALS{'UserFilesPath'} =~ /\/$/)) {
			$GLOBALS{'UserFilesPath'} .= '/' ;
		}
	} else {
		$GLOBALS{'UserFilesPath'} = '/UserFiles/';
	}

	# Map the "UserFiles" path to a local directory.
	$rootpath = &GetRootPath();
	$GLOBALS{'UserFilesDirectory'} = $rootpath . $GLOBALS{'UserFilesPath'};

	&DoResponse();

sub DoResponse
{

	if($FORM{'Command'} eq "" || $FORM{'Type'} eq "" || $FORM{'CurrentFolder'} eq "") {
		return ;
	}
	# Get the main request informaiton.
	$sCommand		= $FORM{'Command'};
	$sResourceType	= $FORM{'Type'};
	$sCurrentFolder	= $FORM{'CurrentFolder'};

	# Check the current folder syntax (must begin and start with a slash).
	if(!($sCurrentFolder =~ /\/$/)) {
		$sCurrentFolder .= '/';
	}
	if(!($sCurrentFolder =~ /^\//)) {
		$sCurrentFolder = '/' . $sCurrentFolder;
	}
	
	# Check for invalid folder paths (..)
	if ( $sCurrentFolder =~ /\.\./ ) {
		SendError( 102, "" ) ;
	}

	# File Upload doesn't have to Return XML, so it must be intercepted before anything.
	if($sCommand eq 'FileUpload') {
		FileUpload($sResourceType,$sCurrentFolder);
		return ;
	}

	print << "_HTML_HEAD_";
Content-Type:text/xml; charset=utf-8
Pragma: no-cache
Cache-Control: no-cache
Expires: Thu, 01 Dec 1994 16:00:00 GMT

_HTML_HEAD_

	&CreateXmlHeader($sCommand,$sResourceType,$sCurrentFolder);
	
	# Execute the required command.
	if($sCommand eq 'GetFolders') {
		&GetFolders($sResourceType,$sCurrentFolder);
	} elsif($sCommand eq 'GetFoldersAndFiles') {
		&GetFoldersAndFiles($sResourceType,$sCurrentFolder);
	} elsif($sCommand eq 'CreateFolder') {
		&CreateFolder($sResourceType,$sCurrentFolder);
	}
	
	&CreateXmlFooter();
	
	exit ;
}

