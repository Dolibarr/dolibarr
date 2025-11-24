#!/usr/bin/perl
##no critic (InputOutput::RequireBriefOpen)
use strict;
use warnings;

#--------------------------------------------------------------------
# Start the generation of the development documentation with doxygen
#--------------------------------------------------------------------

# Determine the patho of this script
( my $DIR = $0 ) =~ s/([^\/\\]+)$//;
$DIR ||= '.';
$DIR =~ s/([^\/\\])[\\\/]+$/$1/;

my $OPTIONS = "";

#$OPTIONS="-d Preprocessor";

my $CONFFILE = "dolibarr-doxygen.doxyfile";

use Cwd;
my $dir = getcwd;

print "Current dir is: $dir\n";

#print "Running dir for doxygen must be: $DIR\n";

if ( !-s "dev/build/doxygen/$CONFFILE" ) {
	print
"Error: current directory for building Dolibarr doxygen documentation is not correct.\n";
	print "\n";
	print "Change your current directory then, to launch the script, run:\n";
	print '> perl .\dolibarr-doxygen-build.pl  (on Windows)' . "\n";
	print '> perl ../dolibarr-doxygen-build.pl  (on Linux or BSD)' . "\n";
	sleep 4;
	exit 1;
}

my $SOURCE = ".";

# Get version $MAJOR, $MINOR and $BUILD
my $result = open( my $IN, "<", $SOURCE . "/htdocs/filefunc.inc.php" );
if ( !$result ) {
	die "Error: Can't open descriptor file " . $SOURCE
	  . "/htdocs/filefunc.inc.php\n";
}
my $PROJVERSION = "";
while (<$IN>) {
	if ( $_ =~ /define\('DOL_VERSION', '([\d\.a-z\-]+)'\)/ ) {
		$PROJVERSION = $1;
		last;
	}
}
close $IN;

if ( $PROJVERSION eq "" ) {
	my $DOL_MAJOR_VERSION;
	my $DOL_MINOR_VERSION;
	my @VERSION_FILES = ( "filefunc.inc.php", "version.inc.php" );
	foreach my $file (@VERSION_FILES) {
		$result = open( my $IN, "<", $SOURCE . "/htdocs/$file" );
		if ( !$result ) {
			die "Error: Can't open descriptor file " . $SOURCE
			  . "/htdocs/$file\n";
		}
		while (<$IN>) {
			if ( $_ =~ /define\('DOL_MAJOR_VERSION', '([\d\.a-z\-]+)'\)/ ) {
				$DOL_MAJOR_VERSION = $1;
			}
			if ( $_ =~ /define\('DOL_MINOR_VERSION', '([\d\.a-z\-]+)'\)/ ) {
				$DOL_MINOR_VERSION = $1;
			}
		}
		close $IN;
	}
	$PROJVERSION = $DOL_MAJOR_VERSION . '.' . $DOL_MINOR_VERSION;
}

( my $MAJOR, my $MINOR, my $BUILD ) = split( /\./, $PROJVERSION, 3 );
if ( !defined($MINOR) || $MINOR eq '' ) {
	die "Error can't detect version from " . $SOURCE
	  . "/htdocs/filefunc.inc.php";
}

my $version = $MAJOR . "." . $MINOR . "." . $BUILD;

print "Running doxygen for version " . $version . ", please wait...\n";
print "cat dev/build/doxygen/$CONFFILE | sed -e 's/x\.y\.z/" . $version
  . "/' | doxygen $OPTIONS - 2>&1\n";
$result =
`cat dev/build/doxygen/$CONFFILE | sed -e 's/x\.y\.z/$version/' | doxygen $OPTIONS - 2>&1`;

print $result;

0;
