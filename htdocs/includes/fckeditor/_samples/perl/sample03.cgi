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
#  File Name: sample03.cgi
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
# $DefRootPath = "/XXXXX/_samples/perl/sample03.cgi"; Please write in script.

my $DefServerPath = "";
my $ServerPath;

	$ServerPath = &GetServerPath();

	if($ENV{'REQUEST_METHOD'} eq "POST") {
		read(STDIN, $buffer, $ENV{'CONTENT_LENGTH'});
	} else {
		$buffer = $ENV{'QUERY_STRING'};
	}
	@pairs = split(/&/,$buffer);
	foreach $pair (@pairs) {
		($name,$value) = split(/=/,$pair);
		$value =~ tr/+/ /;
		$value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
		$value =~ s/\t//g;
		$value =~ s/\r\n/\n/g;
		$FORM{$name} .= "\0"			if(defined($FORM{$name}));
		$FORM{$name} .= $value;
	}

	print "Content-type: text/html\n\n";
	print <<"_HTML_TAG_";
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
	<head>
		<title>FCKeditor - Sample</title>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<meta name="robots" content="noindex, nofollow">
		<link href="../sample.css" rel="stylesheet" type="text/css" />
		<script type="text/javascript">

function FCKeditor_OnComplete( editorInstance )
{
	var oCombo = document.getElementById( 'cmbToolbars' ) ;
	oCombo.value = editorInstance.ToolbarSet.Name ;
	oCombo.style.visibility = '' ;
}

function ChangeToolbar( toolbarName )
{
	window.location.href = window.location.pathname + "?Toolbar=" + toolbarName ;
}

		</script>
	</head>
	<body>
		<h1>FCKeditor - Perl - Sample 3</h1>
		This sample shows how to change the editor toolbar.
		<hr>
		<table cellpadding="0" cellspacing="0" border="0">
			<tr>
				<td>
					Select the toolbar to load:&nbsp;
				</td>
				<td>
					<select id="cmbToolbars" onchange="ChangeToolbar(this.value);" style="VISIBILITY: hidden">
						<option value="Default" selected>Default</option>
						<option value="Basic">Basic</option>
					</select>
				</td>
			</tr>
		</table>
		<br>
		<form action="sampleposteddata.cgi" method="post" target="_blank">
_HTML_TAG_

	#// Automatically calculates the editor base path based on the _samples directory.
	#// This is usefull only for these samples. A real application should use something like this:
	#// $oFCKeditor->BasePath = '/fckeditor/' ;	// '/fckeditor/' is the default value.

	$sBasePath = $ServerPath;
	$sBasePath = substr($sBasePath, 0, index( $sBasePath, "_samples" ));

	&FCKeditor('FCKeditor1') ;
	$BasePath = $sBasePath ;

	if($FORM{'Toolbar'} ne "") {
		$ToolbarSet = &specialchar_cnv( $FORM{'Toolbar'} );
	}
	$Value = 'This is some <strong>sample text</strong>. You are using <a href="http://www.fckeditor.net/">FCKeditor</a>.' ;
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
