#!/usr/bin/perl

# Copyright (C) 2003 Jean-Louis BERGAMO <jlb@j1b.org>
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

# ce script prend l'intergralite des adherents valide et les mets dans
# une mailing-liste.  ce script est utilie si l'on souhaite avoir une
# mailing-liste avec uniquement les adherents valides.

use DBI;
use strict;
# get the command line option
use Getopt::Long;

# command line option hash table
my %optctl=();
# get command line options
GetOptions(\%optctl,"help!","host=s","db=s","user=s","pass=s","ml=s","type=s","cotis!");
if (defined $optctl{'help'}){
  &usage();
}
my $host=$optctl{'host'}||'localhost';
my $dbname=$optctl{'db'}||'dolibarr';
my $user=$optctl{'user'}||'dolibarr';
my $pass=$optctl{'pass'}||'';
my $type=$optctl{'type'}||'mysql';
my $ml=$optctl{'ml'}||&usage();
my @adh=();
my @ml_adh=();

my $dbh = DBI->connect("dbi:$type:dbname=$dbname;host=$host",$user,$pass) || die $DBI::errstr ;

my $sql = 'SELECT email FROM llx_adherent WHERE statut=1';

if (defined $optctl{'cotis'}){
  $sql.=" AND datefin > now()";
}

my $sth = $dbh->prepare("$sql") || die $dbh->errstr ;
$sth->execute;

# get emails of adherents
while (my @row = $sth->fetchrow_array ){
#    print "$row[0]\n";
    push (@adh,$row[0]);
}

# get emails of mailing-list suscribers
@ml_adh=`/usr/sbin/list_members $ml`;
chomp(@ml_adh);
#foreach (@ml_adh){
#	print $_;
#}
# do the diff
foreach my $adh (@adh){
  if (!grep(/^$adh$/i,@ml_adh)){
    # user not subscribed
    print "register $adh : echo $adh | /usr/sbin/add_members -n - $ml\n";
    if (system("echo $adh | /usr/sbin/add_members -n - $ml")){
    	die "can't execute echo $adh | /usr/sbin/add_members -n - $ml : $!";
    }
  }
}

# unsubcribe user not adherent
foreach my $subs (@ml_adh){
  if (!grep(/^$subs$/i,@adh)){
    # unsubscrib user
    print "unsubscribe $subs : /usr/sbin/remove_members $ml $subs\n";
    if (system("/usr/sbin/remove_members $ml $subs")){
    	die "can't execute /usr/sbin/remove_members $ml $subs : $!";
    }
  }
}

$dbh->disconnect();

sub usage{
  print "$0 [--help] [--host] [--db] [--user] [--pass] [--cotis] --ml=mailinglist\n";
  print " ml is for mailing-list. others options are for database\n";
  print " cotis : select only adherents with cotisations up-to-date\n";
  print "\n";
  exit (1);
}
