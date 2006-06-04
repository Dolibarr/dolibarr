#!/usr/bin/perl

# Copyright (C) 2002 Rodolphe Quiedeville
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

# Write a file with the 3 values
# Remember to : export DBI_DSN="dbi:mysql:dbname=dolibarr"
#
# Usage : file-dons.pl PROJECTID FILE_TO_WRITE [DBI_DSN]
#
use DBI;

my $dbh = DBI->connect($ARGV[2]) || die $DBI::errstr ;

my $sql = 'SELECT sum(amount),fk_statut FROM llx_don';
$sql .= ' WHERE fk_statut in (1,2,3) AND fk_don_projet = '.$ARGV[0];
$sql .= ' GROUP BY fk_statut ASC ;';

my $sth = $dbh->prepare("$sql") || die $dbh->errstr ;
$sth->execute;

open (FH, ">$ARGV[1]") || die "can't open $ARGV[1]: $!";	

while (my @row = $sth->fetchrow_array ) 
{    
    print FH int($row[0]) . "\n";
}

close (FH);

$dbh->disconnect();

