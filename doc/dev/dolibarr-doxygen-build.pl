#!/usr/bin/perl
#--------------------------------------------------------------------
# Lance la generation de la doc dev doxygen
#
# \version	$Id$
#--------------------------------------------------------------------

# Detecte repertoire du script
($DIR=$0) =~ s/([^\/\\]+)$//;
$DIR||='.';
$DIR =~ s/([^\/\\])[\\\/]+$/$1/;

$OPTIONS="";
#$OPTIONS="-d Preprocessor";

$CONFFILE="doc/dev/dolibarr-doxygen.conf";
#$CONFFILE="doc/dev/dolibarr-doxygen2.conf";

use Cwd;
my $dir = getcwd;
    
print "Current dir is: $dir\n";
print "Running dir for doxygen must be: $DIR/../..\n";

if (! -s "doc/dev/dolibarr-doxygen.conf") {
    print "Error: current directory for building Dolibarr doxygen documentation is not correct.\n";
    sleep 4;
    exit 1;   
}

print "Running doxygen, please wait...\n";
$result=`doxygen $OPTIONS $CONFFILE 2>&1`;

print $result;

0;
