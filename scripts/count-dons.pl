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

#
# Remember to : export DBI_DSN="dbi:mysql:dbname=dolibarr"
#
use DBI;

my $dbh = DBI->connect() || die $DBI::errstr ;

my $sql = 'SELECT sum(amount) FROM llx_don WHERE fk_statut = ' .$ARGV[0];

my $sth = $dbh->prepare("$sql") || die $dbh->errstr ;
$sth->execute;

while (my @row = $sth->fetchrow_array ) 
{    
    print int($row[0]);
}


$dbh->disconnect();

