#!/usr/bin/perl

# Copyright (C) 2000-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
#
# $Id$
# $Source$
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

use strict;
use POSIX;
use DBI;
use Sys::Syslog qw(:DEFAULT setlogsock);
use Getopt::Long;
 Getopt::Long::Configure("bundling");

Sys::Syslog::setlogsock('unix');
Sys::Syslog::openlog($0, 'pid', 'daemon');



my($debug, $verbose, $bgcolor, $idpropal, $do_fax, $do_pdf, $do_ps, 
   $templatesdir, $outputdir) = (0,0);

exit unless GetOptions("propal=i"    =>\$idpropal,
		       "fax"         =>\$do_fax,
		       "templates=s" =>\$templatesdir,
		       "output=s" =>\$outputdir,
		       "ps"          =>\$do_ps,
		       "pdf"         =>\$do_pdf,
		       "v+"          =>\$verbose);

Sys::Syslog::syslog('info', 'Start propale '.$idpropal);
Sys::Syslog::syslog('info', '['.$idpropal.'] Start');
my $DEBUG = 1;

my $mdir = "$outputdir";
unless (-d $mdir) {
    mkdir($mdir,0777) || die "cannot mkdir " . $mdir . ": $!";
}
print "Output in : $outputdir\n" if $verbose > 1;
#
#
# Fetch datas
#
Sys::Syslog::syslog('info', '['.$idpropal.'] Fetch data');
my ($numpropale, $societe, $date, $ville, $destinataire, $price, $remise, $tva, $total);

my $dbh = DBI->connect("","","") || die $DBI::errstr ;

my $sql = "SELECT p.rowid, p.ref, s.nom, s.cp, s.ville, unix_timestamp(p.datep) as dp, c.name, c.firstname";
$sql .= " ,p.price, p.remise, p.tva, p.total";
$sql .= " FROM llx_propal as p, societe as s , socpeople as c";
$sql .= " WHERE s.idp = p.fk_soc AND p.fk_soc_contact = c.idp AND p.rowid = $idpropal";

my $sth = $dbh->prepare("$sql") || die $dbh->errstr ;
if ( $sth->execute ) {

    if (! $sth->rows ) {
	$sth->finish;
	$dbh->disconnect if $dbh;
	print "\n" . $dbh->errstr;
	print "\n$sql\n";
	exit ;
    }

    while (my $hsr = $sth->fetchrow_hashref ) {
	$numpropale    = $hsr->{"ref"};
	$societe       = $hsr->{"nom"};
	$destinataire  = $hsr->{"firstname"} . " " . $hsr->{"name"};
	$date          = $hsr->{"dp"};
	$ville         = $hsr->{"cp"} . " " . $hsr->{"ville"};

	$price         = $hsr->{"price"};
	$remise        = $hsr->{"remise"};
	$tva           = $hsr->{"tva"};
	$total         = $hsr->{"total"};
    }
    $sth->finish;
} else {
    die $dbh->errstr;
}

$outputdir .= "/".$numpropale;
Sys::Syslog::syslog('info', '['.$idpropal.'] Outputdir : ' . $outputdir);
unless (-d $outputdir) {
    print "Make dir : $outputdir\n" if $verbose > 1;
    mkdir($outputdir,0777) || die "cannot mkdir " . $outputdir . ": $!";
}

print "Output in : $outputdir\n" if $verbose > 1;

my $adresse1 = "";
my $adresse2 = "";

#
#
#
my $headerfilename = "$templatesdir/header.tex";
my $footerfilename = "$templatesdir/footer.tex";
my $bodyfilename = "$templatesdir/body.tex";

unless (open (FC, ">$outputdir/$numpropale.tex") ) {
    print "can't write in $outputdir/$numpropale.tex: $!";
    Sys::Syslog::syslog('info', '['.$idpropal.'] ' . $outputdir/$numpropale.'.tex opened');
    Sys::Syslog::syslog('info', '['.$idpropal.'] ' . $!);
}
#
# Header
#
open (FH, "<$headerfilename") || die "can't open $headerfilename: $!";	
while (<FH>)  {
    s|\#SOCIETE\#|$societe|g;
    s|\#DESTINATAIRE\#|$destinataire|g;
    s|\#ADRESSE1\#|$adresse1|g;
    s|\#ADRESSE2\#|$adresse2|g;
    s|\#VILLE\#|$ville|g;

    s|\#NUMPROPALE\#|$numpropale|g;
    s|\#DATE\#|$date|g;

    print FC $_;
}
close (FH);
#
# Body
#
my $totalht = 0;
my ($qty, $ref, $pu, $pricep, $label);
#
my $sql = "SELECT p.price, pr.ref, pr.label, pr.description";
$sql .= " FROM llx_propaldet as p, llx_product as pr WHERE p.fk_propal = $idpropal AND p.fk_product = pr.rowid";


$sth = $dbh->prepare("$sql") || die $dbh->errstr ;
if ( $sth->execute ) {
    while (my $hsr = $sth->fetchrow_hashref ) {
	$label = $hsr->{"label"};
	$ref = $hsr->{"ref"};
	$societe = $hsr->{"nom"};	
	$qty = 1 ;
	
	$pu     = sprintf("%.2f", $hsr->{"price"});
	$pricep = sprintf("%.2f", $hsr->{"price"});
	
	open (FH, "<$bodyfilename") || die "can't open $bodyfilename: $!";	
	while (<FH>)  {
	    s|\#LABEL\#|$hsr->{"description"}|g;
	    s|\#QTY\#|$qty|g;
	    s|\#REF\#|$ref|g;
	    s|\#PU\#|$pu|g;
	    s|\#PRICE\#|$pricep|g;
	    print FC $_;
	}
	close (FH);
    }
    
    $sth->finish;
}
#
#
#
$totalht  = $price - $remise ;
my $francsht  = $totalht * 6.55957;
my $francsttc = $total * 6.55957;
#
# Footer
#
$price    = sprintf("%.2f", $price);
$remise   = sprintf("%.2f", $remise);
$totalht  = sprintf("%.2f", $totalht);
$tva      = sprintf("%.2f", $tva);
$total    = sprintf("%.2f", $total);

$francsttc  = sprintf("%.2f", $francsttc);
$francsht   = sprintf("%.2f", $francsht);


$date = strftime("%d/%m/%Y", localtime($date));
#
open (FF, "<$footerfilename") || die "can't open $footerfilename: $!";	
while (<FF>)  {
    s|\#SOCIETE\#|$societe|g;

    s|\#DATE\#|$date|g;
    s|\#TOTALHT\#|$totalht|g;

    s|\#FRANCSHT\#|$francsht|g;

    s|\#REMISEHT\#|$remise|g;
    s|\#TVA\#|$tva|g;
    s|\#TOTALTTC\#|$total|g;

    s|\#FRANCSTTC\#|$francsttc|g;
	    
    print FC $_;
}
close (FF);


close (FC);

$dbh->disconnect if $dbh;
#
#
#
print "Generate dvi file<br>\n";
system("cd $outputdir/ ; latex $numpropale.tex "); 
#
#
#
if ($do_pdf) {
    print "<p>Generate pdf file<br>\n";
    system("cd $outputdir/ ; pdflatex $numpropale.tex > /dev/null");
}
#
#
#
if ($do_ps) {
    print "Generate ps file\n";
    system("cd $outputdir/ ; dvips $numpropale.dvi -o $numpropale.ps > /dev/null");
}
#
# $outputdir/$numpropale.tex
#
if ($do_fax) {
    print "Generate fax file\n";
    system("gs -q -sDEVICE=tiffg3 -dNOPAUSE -sOutputFile=$outputdir/$numpropale.%03d $outputdir/$numpropale.ps </dev/null");
}
Sys::Syslog::syslog('info', 'End propale '.$idpropal);
Sys::Syslog::closelog();
#
#
#

#
# export DBI_DSN="dbi:mysql:dbname=lolixfr:host=espy" ; ../scripts/propal-tex.pl 
#
