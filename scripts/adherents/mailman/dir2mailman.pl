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

# Ce script va lire les adresse contenu dans le repertoire donne en
# argument, et va rajouter ou supprimer ces adresses des listes qu'on
# lui a donne en argument (ou qu'il recupere dans la table des
# constantes)

use DBI;
use strict;
# get the command line option
use Getopt::Long;

# command line option hash table
my %optctl=();
# get command line options
GetOptions(\%optctl,"help!","dir=s");
if (defined $optctl{'help'}){
  &usage();
}
my $dir=$optctl{'dir'}||&usage();
my @lists=();
my @emails=();

opendir(DIR, $dir) || die "can't opendir $dir: $!";
@lists=grep { /^[^.]/ && -d "$dir/$_" } readdir(DIR);
closedir DIR;
print join(',',@lists),"\n";

foreach my $list (@lists){
  my $subdir='subscribe';
  if(opendir(DIR, "$dir/$list/$subdir")){
    @emails=grep { /^[^.].+\@/ && -f "$dir/$list/$subdir/$_" } readdir(DIR);
    closedir DIR;
  }
  if (@emails){
    foreach my $mail(@emails){
      print "register $mail: echo $mail | /usr/sbin/add_members -n - $list\n";
      if (system("echo $mail | /usr/sbin/add_members -n - $list")){
    	warn "can't execute echo $mail | /usr/sbin/add_members -n - $list : $!\n";
      }else{
	unlink("$dir/$list/$subdir/$mail");
      }
    }
  }
  @emails=();
  $subdir='unsubscribe';
  if(opendir(DIR, "$dir/$list/$subdir")){
    @emails=grep { /^[^.].+\@/ && -f "$dir/$list/$subdir/$_" } readdir(DIR);
    closedir DIR;
  }
  if (@emails){
    foreach my $mail(@emails){
      print "unsubscribe $mail : /usr/sbin/remove_members $list $mail\n";
      if (system("/usr/sbin/remove_members $list $mail")){
    	warn "can't execute /usr/sbin/remove_members $list $mail : $!\n";
      }else{
	unlink("$dir/$list/$subdir/$mail");
      }
    }
  }
}
exit;

sub usage{
  print "$0 [--help] --dir=directory \n";
  print " directory is the directory where email are stored.";
  print "";
  exit (1);
}

