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
$sl = Sys::Syslog::openlog('send-newsletter.pl', 'pid', $SYSLOG_LEVEL);
$sl = Sys::Syslog::syslog('info', 'Start');

print "Start\n"  if $verbose>0;

print "DBI connection : open\n" if $verbose>3;
$dbh = DBI->connect() || die $DBI::errstr;

my $file = "/tmp/importf.csv";
my @line;
my $i = 0;
my ($civ, $rubrique,$civilite,$nom,$prenom,$num,$type,$voie,$complement,$cp,$ville,$tel,$fax,$mobile,$email,$web,$commentaire);
open (FH, "<$file") || die "can't open $file: $!";
while (<FH>) 
{
    $civ = 0;
    $civilite = '';

    s|\'|\\\'|g;
    @line = split /\t/, $_;
    $rubrique    = $line[0];
    $civilite    = $line[1];
    $nom         = $line[2];
    $prenom      = $line[3];
    $num         = $line[4];
    $type        = $line[5];
    $voie        = $line[6];
    $complement  = $line[7];
    $cp          = $line[8];
    $ville       = $line[9];
    $tel         = $line[10];
    $fax         = $line[11];
    $mobile      = $line[12];
    $email       = $line[13];
    $web         = $line[14];
    $commentaire = $line[15];

    if ($i > 0 )
    {	
	my $sql = "INSERT INTO llx_societe (datec, client, nom, address,cp,ville,tel,fax,url,note,rubrique,fk_user_creat) ";
	$sql .= "VALUES (now(),2,'$nom $prenom','$num $type $voie\n$complement','$cp','$ville','$tel','$fax','$web','$commentaire','$rubrique',1)";

	$stha = $dbh->prepare($sql);
	$stha->execute;
	
	$sql = "SELECT MAX(idp) as co FROM llx_societe";
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

send-newsletter.pl - 

=head1 SYNOPSIS

send-newsletter.pl [-v]

=head1 DESCRIPTION

send-newsletter.pl send newsletter from DB

=head1 OPTIONS

=over

=back

=head1 AUTHOR

Rodolphe Quiedeville (rodolphe@quiedeville.org)

=cut

