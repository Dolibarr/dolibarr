#!/usr/bin/perl
#--------------------------------------------------------------------
# \brief	This script is a preprocessor for PHP files to be used
#			on PHP source files before running Doxygen.
# \author	Laurent Destailleur
#--------------------------------------------------------------------

# Usage: dolibarr-doxygen-filter.pl pathtofilefromdolibarrroot

$file=$ARGV[0];
if (! $file) 
{
	print "Usage: dolibarr-doxygen-filter.pl pathtofilefromdolibarrroot\n";
	exit;
}

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
	$_ =~ s/exit\s*;/exit(0);/i;
	$i=0;
	$len=length($_);
	$s="";
	$insidequote=0;
	$insidedquote=0;
	$ignore="";
	while ($i < $len)
	{
		$c=substr($_,$i,1);
		if ($c eq "\\")
		{
			if ($insidequote)  { $ignore="'";  };
			if ($insidedquote) { $ignore="\""; };
		}
		else
		{
			if ($c eq "'")
			{
				if (! $insidedquote)
				{
					$c="\"";
					#print "X".$ignore;
					if ($ignore ne "'")
					{
						#print "Z".$ignore;
						$insidequote++;
						if ($insidequote == 2)
						{
							$insidequote=0;
						}
					}
				}
				#print "X".$insidequote;
			}
			elsif ($c eq "\"")
			{
				#print "Y".$insidequote;
				if ($insidequote)
				{
					$c="'";
				}
				else
				{
					if ($ignore ne "\"")
					{
						$insidedquote++;
						if ($insidedquote == 2)
						{
							$insidedquote=0;
						}
					}				
				}
			}
			$ignore="";
		}
		$s.=$c;
		$i++;
	}
	print $s;
}
close(FILE);
