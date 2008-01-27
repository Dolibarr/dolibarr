#!/usr/bin/perl
#--------------------------------------------------------------------
# Script recup version d'un source
#
# $Id$
#--------------------------------------------------------------------


$file=$ARGV[0];

$commande='cvs status "'.$file.'" | sed -n \'s/^[ \]*Working revision:[ \t]*\([0-9][0-9\.]*\).*/\1/p\'';
#print $commande;
$result=`$commande 2>&1`;

print $result;
