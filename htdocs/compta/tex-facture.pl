#!/usr/bin/perl

# Copyright (C) 2000-2003 Rodolphe Quiedeville
#
# This program is free software; you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation; either version 2 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program; if not, write to the Free Software
# Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
# or see http://www.gnu.org/

# $Id$
# $Source$

use strict;
use DBI;
use Getopt::Long;
 Getopt::Long::Configure("bundling");


my($outputdir, $templatesdir, $debug, $verbose, $bgcolor, $idfacture, $do_fax, $do_pdf, $do_ps, $html) = (0,0);

exit unless GetOptions("facture=i"   =>\$idfacture,
		       "fax"         =>\$do_fax,
		       "html"        =>\$html,
		       "templates=s" =>\$templatesdir,
		       "output=s"    =>\$outputdir,
		       "ps"          =>\$do_ps,
		       "pdf"         =>\$do_pdf,
		       "v+"          =>\$verbose);

unless (defined $ENV{"DBI_DSN"}) { print "Missing ENV var: DBI_DSN is not defined\n"; exit 0; }

#
#
#

unless (-d $outputdir) {
    mkdir($outputdir,0777) || die "cannot mkdir " . $outputdir . ": $!";
}

#
# Fetch datas
#
my ($numpropale, $numfacture, $societe, $remiseht, $date, $destinataire, $ville, $address);
my ($totalht, $francsht,$remise,$tva,$total,$francsttc,$description);

print "Fetch data\n" if $verbose;
print "<br>" if ($verbose && $html); 

my $dbh = DBI->connect() || die $DBI::errstr ; # We use env var DBI_DSN to connect to DB

my $sql = "SELECT f.rowid, f.facnumber, s.nom, f.amount, f.remise, f.tva, f.total, f.datef, s.ville, s.cp, s.address";
$sql .= " FROM llx_facture as f, societe as s ";
$sql .= " WHERE s.idp = f.fk_soc AND f.rowid = $idfacture";

my $sth = $dbh->prepare("$sql") || die $dbh->errstr ;

if ( $sth->execute )
{

    while (my $hsr = $sth->fetchrow_hashref )
    {
	$numfacture   = $hsr->{"facnumber"};
	$societe      = $hsr->{"nom"};
	$remiseht     = $hsr->{"remise"};
	$date         = $hsr->{"datef"};
	$destinataire = $hsr->{"datep"};
	$address      = $hsr->{"address"};
	$ville        = $hsr->{"cp"} . " " . $hsr->{"ville"};

	$totalht = sprintf("%.2f", $hsr->{"amount"});
	$remise = sprintf("%.2f", $hsr->{"remise"});
	$tva = sprintf("%.2f", $hsr->{"tva"});
	$total = sprintf("%.2f", $hsr->{"total"});
	$francsttc = sprintf("%.2f", ($hsr->{"total"} * 6.55957));

	$numpropale = $hsr->{"propalref"};

    }
    $sth->finish;
}
else
{
    print "db error\n";
}
#
#
#
$outputdir .= "/" . $numfacture;

print "outputdir is $outputdir\n" if $verbose ;
print "<br>" if ($verbose && $html); 

unless (-d $outputdir) {
    print "make $outputdir\n" if $verbose ;
    print "<br>" if ($verbose && $html); 

    unless ( mkdir($outputdir,0777) ) {
	print "failed : $!\n" if $verbose ;
    }
}


#
#
#
my $headerfilename = "$templatesdir/header.tex";
my $footerfilename = "$templatesdir/footer.tex";
my $bodyfilename   = "$templatesdir/body.tex";


open (FC, ">$outputdir/$numfacture.tex") || die "can't write in $outputdir/$numfacture.tex: $!";

##########################################################################################
#
# Header
#
##########################################################################################
open (FH, "<$headerfilename") || die "can't open $headerfilename: $!";	
while (<FH>)  {
    s|\#SOCIETE\#|$societe|g;
    s|\#ADDRESS\#|$address|g;

    s|\#VILLE\#|$ville|g;
    s|\#DESTINATAIRE\#|$destinataire|g;


    s|\#NUMFACTURE\#|$numfacture|g;
    s|\#DATE\#|$date|g;

    print FC $_;
}
close (FH);
##########################################################################################
#
# BODY
#
##########################################################################################


my ($qty, $ref, $pu, $pricep, $label);
#
$sql = "SELECT l.price, l.qty, l.description";
$sql .= " FROM llx_facturedet as l";
$sql .= " WHERE l.fk_facture = $idfacture";


$sth = $dbh->prepare("$sql") || die $dbh->errstr ;
if ( $sth->execute )
{
    while (my $hsr = $sth->fetchrow_hashref )
    {
	$label = $hsr->{"label"};
	$ref = $hsr->{"ref"};
	$societe = $hsr->{"nom"};
	$qty = $hsr->{"qty"};
	
	$pu     = sprintf("%.2f", $hsr->{"price"});
	$pricep = sprintf("%.2f", $hsr->{"price"} * $qty);
	
	open (FB, "<$bodyfilename") || die "can't open $bodyfilename: $!";	
	while (<FB>) 
	{
	    s|\#LABEL\#|$hsr->{"description"}|g;
	    s|\#QTY\#|$qty|g;
	    s|\#REF\#|$ref|g;
	    s|\#PU\#|$pu|g;
	    s|\#PRICE\#|$pricep|g;
	    print FC $_;
	}
	close (FB);
    }
    
    $sth->finish;
}
else
{
    print "** ERROR\n";
    print "<br>" if ( $html); 
}

##########################################################################################
#
# Footer
#
##########################################################################################
open (FF, "<$footerfilename") || die "can't open $footerfilename: $!";	
while (<FF>)  {
    s|\#SOCIETE\#|$societe|g;

    s|\#DATE\#|$date|g;
    s|\#TOTALHT\#|$totalht|g;

    s|\#NUMPROPALE\#|$numpropale|g;

    s|\#FRANCSHT\#|$francsht|g;

    s|\#REMISEHT\#|$remise|g;
    s|\#TVA\#|$tva|g;
    s|\#TOTALTTC\#|$total|g;

    s|\#FRANCS\#|$francsttc|g;
	    
    print FC $_;
}
close (FF);
##########################################################################################
#
#
#
##########################################################################################

close (FC);

$dbh->disconnect if $dbh;
#
#
#
print "**\n** Generate dvi file\n**\n";
print "<br>" if ($verbose && $html); 
system("cd $outputdir/ ; latex $numfacture.tex "); 
#
#exit;

print "<p>Generate pdf file<br>\n";
print "<br>" if ($verbose && $html); 
system("cd $outputdir/ ; pdflatex $numfacture.tex > /dev/null");


#
print "\n**\n** Generate ps file\n**\n";
    print "<br>" if ($verbose && $html); 
system("cd $outputdir/ ; dvips $numfacture.dvi -o $numfacture.ps > /dev/null");

#
# export DBI_DSN="dbi:mysql:dbname=lolixfr:host=espy" ; ../scripts/facture-tex.pl 
#
