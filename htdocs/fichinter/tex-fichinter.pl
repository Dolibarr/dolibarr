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



my($html, $debug, $verbose, $idfiche, $do_pdf, $do_ps, $templatesdir, $outputdir) = (0,0);

exit unless GetOptions("fichinter=i"    =>\$idfiche,
		       "templates=s" =>\$templatesdir,
		       "output=s" =>\$outputdir,
		       "ps"          =>\$do_ps,
		       "pdf"         =>\$do_pdf,
		       "html"         =>\$html,
		       "v+"          =>\$verbose);

Sys::Syslog::syslog('info', 'Start Fiche Inter '.$idfiche);
Sys::Syslog::syslog('info', '['.$idfiche.'] Start');
my $DEBUG = 1;



my $mdir = "$outputdir";
unless (-d $mdir) {
    mkdir($mdir,0777) || die "cannot mkdir " . $mdir . ": $!";
}
print "Output in : $outputdir\n" if $verbose > 1;
print "<br>\n" if ($verbose > 1 && $html);
#
#
# Fetch datas
#
Sys::Syslog::syslog('info', '['.$idfiche.'] Fetch data');
my ($numfiche, $societe, $date, $ville, $destinataire, $address, $note, $duree);

my $dbh = DBI->connect("","","") || die $DBI::errstr ;

my $sql = "SELECT f.rowid, f.ref, s.nom, s.address, s.cp, s.ville, unix_timestamp(f.datei) as di, f.duree, f.note";
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
	$duree          = $hsr->{"duree"};
	$note          = $hsr->{"note"};
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
print "<br>\n" if ($verbose > 1 && $html);
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
print "<br>\n" if ($verbose > 1 && $html);

#
#

my $tempfilename = "$outputdir/temp";
open (FT, ">$tempfilename") || die "can't open $tempfilename: $!";	
print FT $note;
close (FT);

#


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
my $bodyfilename = "$templatesdir/header.tex";
open (FH, "<$bodyfilename") || die "can't open $bodyfilename: $!";	
while (<FH>)  {
    s|\_SOCIETE\_|$societe|g;
    s|\_ADRESSE1\_|$adresse1|g;
    s|\_ADRESSE2\_|$adresse2|g;
    s|\_VILLE\_|$ville|g;
    s|\_DATE\_|$date|g;
    s|\_DUREE\_|$duree|g;
    s|\_NUMFICHE\_|$numfiche|g;
    
    print FC $_;
}
close (FH);

#
# Body
#
my ($line) = (0);
open (FH, "<$tempfilename") || die "can't open $tempfilename: $!";	
while (<FH>)  {
    print FC "\n";
    print FC $_;
    print FC "\\\\";
    print FC "\n";
    $line++;
}
close (FH);
print FC "\n";


#
# Footer
#
my $footfilename = "$templatesdir/footer.tex";
open (FH, "<$footfilename") || die "can't open $footfilename: $!";	
while (<FH>)  {
    s|\_SOCIETE\_|$societe|g;
    s|\_ADRESSE1\_|$adresse1|g;
    s|\_ADRESSE2\_|$adresse2|g;
    s|\_VILLE\_|$ville|g;
    s|\_DATE\_|$date|g;
    s|\_NUMFICHE\_|$numfiche|g;
    
    print FC $_;
}
close (FH);

close (FC);

$dbh->disconnect if $dbh;
#
#
# Generation des documents 
#
#
if (-r "$outputdir/$numfiche.tex" ) {

    system("cd $outputdir/ ; recode -d iso8859-1..ltex < $numfiche.tex > recode-$numfiche.tex"); 
}
#
#
if (-r "$outputdir/recode-$numfiche.tex") {
    print "Generate dvi file\n";
    system("cd $outputdir/ ; latex recode-$numfiche.tex "); 
}
#
#
#


print "<p>Generate pdf file\n";
if (-r "$outputdir/recode-$numfiche.tex") {
    system("cd $outputdir/ ; pdflatex recode-$numfiche.tex > /dev/null");
}

if (-r "$outputdir/recode-$numfiche.tex") {
    system("cd $outputdir/ ; mv recode-$numfiche.pdf $numfiche.pdf > /dev/null");
}

if (-r "$outputdir/recode-$numfiche.dvi") {
    print "Generate ps file\n";
    system("cd $outputdir/ ; dvips recode-$numfiche.dvi -o $numfiche.ps ");
}


Sys::Syslog::syslog('info', 'End ficheinter '.$idfiche);
Sys::Syslog::closelog();

