#!/usr/bin/perl
#--------------------------------------------------------------------
# Script filtrage des sources Dolibarr pour doxygen
#
# \version $Id$
#--------------------------------------------------------------------

# Usage: dolibarr-doxygen-getversion.pl pathtofilefromdolibarrroot

$file=$ARGV[0];

open(FILE,$file) || die "Failed to open file $file";
while (<FILE>)
{
	if ($_ =~ /\\version\s/i)
	{
		$_ =~ s/\$Id://i;
		$_ =~ s/(Exp|)\s\$$//i;
		$_ =~ s/(\\version\s+)[^\s]+\s/$1/i;
		$_ =~ s/(\w)\s(\w)/$1_$2/g;
	}
	print $_;
}
close(FILE);
