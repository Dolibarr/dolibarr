#!/usr/bin/perl
#----------------------------------------------------------------------------
# Copyright (C) 2003 Jean-Louis BERGAMO  <jlb@j1b.org>
# Copyright (C) 2009 Laurent Destailleur <eldy@users.sourceforge.net>
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
#---------------------------------------------------------------------------
# This script build a file with format
# login:password
#---------------------------------------------------------------------------

use DBI;
use strict;
# get the command line option
use Getopt::Long;

# command line option hash table
my %optctl=();
# get command line options
GetOptions(\%optctl,"help!","host=s","db=s","user=s","pass=s","type=s","cotis!","crypt!");
if (defined $optctl{'help'}){
  &usage();
}
my $host=$optctl{'host'}||'localhost';
my $dbname=$optctl{'db'}||&usage();
my $user=$optctl{'user'}||&usage();
my $pass=$optctl{'pass'}||'';
my $type=$optctl{'type'}||'mysql';
#my $ml=$optctl{'ml'}||&usage();
my @adh=();
my @ml_adh=();

my $dbh = DBI->connect("dbi:$type:dbname=$dbname;host=$host",$user,$pass) || die $DBI::errstr ;

my $sql = 'SELECT login,pass FROM llx_adherent WHERE statut=1';

if (defined $optctl{'cotis'}){
  $sql.=" AND datefin > now()";
}

my $sth = $dbh->prepare("$sql") || die $dbh->errstr ;
$sth->execute;

# get login,pass of each adherents
while (my @row = $sth->fetchrow_array ){
  if (defined $optctl{'crypt'}){
    print "$row[0]:",crypt($row[1],join '', ('.', '/', 0..9,'A'..'Z', 'a'..'z')[rand 64, rand 64]),"\n";
  }else{
    print "$row[0]:$row[1]\n";
  }
}

$dbh->disconnect();

sub usage{
  print "Usage: $0 --db=database --user=user --pass=password [--help] [--host=host] [--cotis] [--crypt]\n";
  print " --cotis : select only adherents with cotisations up-to-date\n";
  print " --crypt : password is encrypted\n";
  print "\n";
  exit (1);
}
