#!/usr/bin/perl
#--------------------------------------------------------------------
# Start the generation of the development documentation with doxygen
#--------------------------------------------------------------------

# Determine the patho of this script
($DIR=$0) =~ s/([^\/\\]+)$//;
$DIR||='.';
$DIR =~ s/([^\/\\])[\\\/]+$/$1/;

$OPTIONS="";
#$OPTIONS="-d Preprocessor";

$CONFFILE="dolibarr-doxygen.doxyfile";

use Cwd;
my $dir = getcwd;

print "Current dir is: $dir\n";
#print "Running dir for doxygen must be: $DIR\n";

if (! -s "build/doxygen/$CONFFILE")
{
    print "Error: current directory for building Dolibarr doxygen documentation is not correct.\n";
    print "\n";
	print "Change your current directory then, to launch the script, run:\n";
	print '> perl .\dolibarr-doxygen-build.pl  (on Windows)'."\n";
	print '> perl ../dolibarr-doxygen-build.pl  (on Linux or BSD)'."\n";
    sleep 4;
    exit 1;   
}

$SOURCE=".";

# Get version $MAJOR, $MINOR and $BUILD
$result = open( IN, "< " . $SOURCE . "/htdocs/filefunc.inc.php" );
if ( !$result ) { die "Error: Can't open descriptor file " . $SOURCE . "/htdocs/filefunc.inc.php\n"; }
while (<IN>) {
	if ( $_ =~ /define\('DOL_VERSION', '([\d\.a-z\-]+)'\)/ ) { $PROJVERSION = $1; break; }
}
close IN;
($MAJOR,$MINOR,$BUILD)=split(/\./,$PROJVERSION,3);
if ($MINOR eq '') { die "Error can't detect version into ".$SOURCE . "/htdocs/filefunc.inc.php"; }


$version=$MAJOR.".".$MINOR.".".$BUILD;


print "Running doxygen for version ".$version.", please wait...\n";
print "cat build/doxygen/$CONFFILE | sed -e 's/x\.y\.z/".$version."/' | doxygen $OPTIONS - 2>&1\n";
$result=`cat build/doxygen/$CONFFILE | sed -e 's/x\.y\.z/$version/' | doxygen $OPTIONS - 2>&1`;

print $result;

0;
