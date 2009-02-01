#!/usr/bin/perl

# Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
#
# $Id$
# $Source$

$SYSLOG_LEVEL = 'local3';

use strict;
use vars qw($SYSLOG_LEVEL);
use DBI;
use Net::SMTP;
use Text::Wrap;
use Getopt::Long;
use Sys::Syslog qw(:DEFAULT setlogsock);
 Getopt::Long::Configure("bundling");

my($debug,$verbose, $help) = (0,0,0);

exit unless GetOptions("v+", \$verbose, "debug", \$debug, "help", \$help);

print_help() if $help;

my($dbh, $sth, $hsr, $sthi, $i, $sqli, $sql, $stha, $digest);

print "Running in verbose mode level $verbose\n" if $verbose>0;

my $sl = Sys::Syslog::setlogsock('unix');
$sl = Sys::Syslog::openlog('import-csv.pl', 'pid', $SYSLOG_LEVEL);
$sl = Sys::Syslog::syslog('info', 'Start');

print "Start\n"  if $verbose>0;

print "DBI connection : open\n" if $verbose>3;
$dbh = DBI->connect() || die $DBI::errstr;

my $file = "/tmp/importf.csv";
my @line;
my $i = 0;
my ($civ,$civilite,$nom,$prenom,$num,$type,$voie,$complement,$cp,$ville,$tel,$fax,$mobile,$email,$web,$commentaire);
open (FH, "<$file") || die "can't open $file: $!";
while (<FH>) 
{
    $civ = 0;
    $civilite = '';

    s|\'|\\\'|g;
    @line = split /\t/, $_;
    $civilite    = $line[0];
    $nom         = $line[1];
    $prenom      = $line[2];
    $num         = $line[3];
    $type        = $line[4];
    $voie        = $line[5];
    $complement  = $line[6];
    $cp          = $line[7];
    $ville       = $line[8];
    $tel         = $line[9];
    $fax         = $line[10];
    $mobile      = $line[11];
    $email       = $line[12];
    $web         = $line[13];
    $commentaire = $line[14];

    if ($i > 0 )
    {	
	my $sql = "INSERT INTO llx_societe (datec, client, nom, address,cp,ville,tel,fax,url,note,fk_user_creat) ";
	$sql .= "VALUES (now(),2,'$nom $prenom','$num $type $voie\n$complement','$cp','$ville','$tel','$fax','$web','$commentaire',1)";

	$stha = $dbh->prepare($sql);
	$stha->execute;
	
	$sql = "SELECT MAX(rowid) as co FROM llx_societe";
	$sth = $dbh->prepare("$sql") || die $dbh->errstr ;
	if ( $sth->execute ) {
	    if ( $sth->rows ) {
	        $hsr = $sth->fetchrow_hashref;
	    }
	    $sth->finish;
	}

	if ($civilite = 'Mme')
	{
	    $civ = 2 ;
	}
	if ($civilite = 'M')
	{
	    $civ = 1 ;
	}

	if ($civ > 0)
	{
	    my $sql = "INSERT INTO llx_socpeople (datec, fk_soc, name, firstname,phone,fax,email, fk_user) ";
	    $sql .= "VALUES (now(),".$hsr->{"co"}.",'$nom', '$prenom','$tel','$fax','$email',1)";
	    
	    $stha = $dbh->prepare($sql);
	    $stha->execute;	    
	}


    }
    print $i . " ";
    $i++;
}
close (FH);

print "DBI connection : close\n" if $verbose>3;

if ($dbh)
{
    $dbh->disconnect;
}

print "End\n" if $verbose>0;
#
# 
#
    
    
$sl = Sys::Syslog::syslog('info', 'End');

Sys::Syslog::closelog();

#
#
#
#
#
sub print_help {
    print "Usage send-newsletter.pl [-v]\n";
    exit 0;
}


__END__
# Below is the documentation for the script.

=head1 NAME

import-csv.pl - 

=head1 SYNOPSIS

import-csv.pl [-v]

=head1 DESCRIPTION

import-csv.pl import companies from a file into DB

=head1 OPTIONS

=over

=back

=head1 AUTHOR

Rodolphe Quiedeville (rodolphe@quiedeville.org)

=cut

