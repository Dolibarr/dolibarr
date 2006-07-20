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
#  File Name: sample01.cgi
#  	Sample page.
#  
#  File Authors:
#  		Takashi Yamaguchi (jack@omakase.net)
#####

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

require '../../fckeditor.pl';

# When $ENV{'PATH_INFO'} cannot be used by perl.
# $DefRootPath = "/XXXXX/_samples/perl/sample01.cgi"; Please write in script.

my $DefServerPath = "";
my $ServerPath;

	$ServerPath = &GetServerPath();
	print "Content-type: text/html\n\n";
	print <<"_HTML_TAG_";
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>FCKeditor - Sample</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
	</head>
	<body>
		<h1>FCKeditor - Perl - Sample 1</h1>
		This sample displays a normal HTML form with an FCKeditor with full features 
		enabled.
		<hr>
		<form action="sampleposteddata.cgi" method="post" target="_blank">
_HTML_TAG_

	#// Automatically calculates the editor base path based on the _samples directory.
	#// This is usefull only for these samples. A real application should use something like this:
	#// $oFCKeditor->BasePath = '/fckeditor/' ;	// '/fckeditor/' is the default value.

	$sBasePath = $ServerPath;
	$sBasePath = substr($sBasePath,0,index($sBasePath,"_samples"));
	&FCKeditor('FCKeditor1');
	$BasePath	= $sBasePath;
	$Value		= 'This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.';
	&Create();

	print <<"_HTML_TAG_";
			<br>
			<input type="submit" value="Submit">
		</form>
	</body>
</html>
_HTML_TAG_

################
#Please use this function, rewriting it depending on a server's environment.
################
sub GetServerPath
{
my $dir;

	if($DefServerPath) {
		$dir = $DefServerPath;
	} else {
		if($ENV{'PATH_INFO'}) {
			$dir  = $ENV{'PATH_INFO'};
		} elsif($ENV{'FILEPATH_INFO'}) {
			$dir  = $ENV{'FILEPATH_INFO'};
		}
	}
	return($dir);
}
