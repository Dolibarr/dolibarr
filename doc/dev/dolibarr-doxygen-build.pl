#!/usr/bin/perl
#--------------------------------------------------
# Lance la generation de la doc dev doxygen
#--------------------------------------------------

# Detecte repertoire du script
($DIR=$0) =~ s/([^\/\\]+)$//;
$DIR||='.';
$DIR =~ s/([^\/\\])[\\\/]+$/$1/;


use Cwd;
my $dir = getcwd;
    
print "Current dir is: $dir\n";
print "Running dir for doxygen must be: $DIR/../..\n";

if (! -s "doc/dev/dolibarr-doxygen") {
    print "Error: current directory for building Dolibarr doxygen documentation is not correct.\n";
    exit 1;   
}

$result=`doxygen doc/dev/dolibarr-doxygen`;

print $result;

0;
