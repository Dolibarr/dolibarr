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
#
# Génération des fiche d'intervention
#
use strict;
use POSIX;
use DBI;
use Sys::Syslog qw(:DEFAULT setlogsock);
use Getopt::Long;
 Getopt::Long::Configure("bundling");

Sys::Syslog::setlogsock('unix');
Sys::Syslog::openlog($0, 'pid', 'daemon');



my($debug, $verbose, $bgcolor, $idfiche, $do_fax, $do_pdf, $do_ps, 
   $templatesdir, $outputdir) = (0,0);

exit unless GetOptions("fichinter=i"    =>\$idfiche,
		       "fax"         =>\$do_fax,
		       "templates=s" =>\$templatesdir,
		       "output=s" =>\$outputdir,
		       "ps"          =>\$do_ps,
		       "pdf"         =>\$do_pdf,
		       "v+"          =>\$verbose);

Sys::Syslog::syslog('info', 'Start Fiche Inter '.$idfiche);
Sys::Syslog::syslog('info', '['.$idfiche.'] Start');
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
Sys::Syslog::syslog('info', '['.$idfiche.'] Fetch data');
my ($numfiche, $societe, $date, $ville, $destinataire, $address);

my $dbh = DBI->connect("","","") || die $DBI::errstr ;

my $sql = "SELECT f.rowid, f.ref, s.nom, s.address, s.cp, s.ville, unix_timestamp(f.datei) as di";
$sql .= " FROM llx_fichinter as f, societe as s";
$sql .= " WHERE s.idp = f.fk_soc AND f.rowid = $idfiche";

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
	$numfiche      = $hsr->{"ref"};
	$societe       = $hsr->{"nom"};
	$destinataire  = $hsr->{"firstname"} . " " . $hsr->{"name"};
	$date          = $hsr->{"di"};
	$address       = $hsr->{"address"};
	$ville         = $hsr->{"cp"} . " " . $hsr->{"ville"};
    }
    $sth->finish;
} else {
    die $dbh->errstr;
}

$outputdir .= "/".$numfiche;
Sys::Syslog::syslog('info', '['.$idfiche.'] Outputdir : ' . $outputdir);
unless (-d $outputdir) {
    print "Make dir : $outputdir\n" if $verbose > 1;
    mkdir($outputdir,0777) || die "cannot mkdir " . $outputdir . ": $!";
}

print "Output in : $outputdir\n" if $verbose > 1;
#
# Decoupage de l'adresse en 2 lignes
#
#
my ($adresse2, $adresse1) = ("",$address);
$_ = $address;
if (/^(.*)\n(.*)/) {
    $adresse1 = "$1";
    $adresse2 = "$2";
    print "|$adresse1|\n";
    print "|$adresse2|\n";
}
    print "|$address|\n";
#
#
#
my $bodyfilename = "$templatesdir/fichinter.tex";

unless (open (FC, ">$outputdir/$numfiche.tex") ) {
    print "can't write in $outputdir/$numfiche.tex: $!";
    Sys::Syslog::syslog('info', '['.$idfiche.'] ' . $!);
} else {
    Sys::Syslog::syslog('info', '['.$idfiche.'] ' . $outputdir.'/'.$numfiche.'.tex opened');
}

$date = strftime("%A %d %B %Y", localtime($date));
#
# Body
#

open (FH, "<$bodyfilename") || die "can't open $bodyfilename: $!";	
while (<FH>)  {
    s|\#SOCIETE\#|$societe|g;
    s|\#ADRESSE1\#|$adresse1|g;
    s|\#ADRESSE2\#|$adresse2|g;
    s|\#VILLE\#|$ville|g;
    s|\#DATE\#|$date|g;
    s|\#NUMFICHE\#|$numfiche|g;
    
    print FC $_;
}
close (FH);

#
#
#


close (FC);

$dbh->disconnect if $dbh;
#
system("cd $outputdir/ ; recode -d iso8859-1..ltex < $numfiche.tex > recode-$numfiche.tex"); 
#
#
print "Generate dvi file<br>\n";
system("cd $outputdir/ ; latex recode-$numfiche.tex "); 
#
#
#



if ($do_pdf) {
    print "<p>Generate pdf file<br>\n";
    system("cd $outputdir/ ; pdflatex recode-$numfiche.tex > /dev/null");
    system("cd $outputdir/ ; mv recode-$numfiche.pdf $numfiche.pdf > /dev/null");
}
#
#
#
if ($do_ps) {
    print "Generate ps file\n";
    system("cd $outputdir/ ; dvips recode-$numfiche.dvi -o $numfiche.ps > /dev/null");
}
#
# $outputdir/$numfiche.tex
#
if ($do_fax) {
    print "Generate fax file\n";
    system("gs -q -sDEVICE=tiffg3 -dNOPAUSE -sOutputFile=$outputdir/$numfiche.%03d $outputdir/$numfiche.ps </dev/null");
}
Sys::Syslog::syslog('info', 'End propale '.$idfiche);
Sys::Syslog::closelog();

