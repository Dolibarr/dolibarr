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

unless (defined $ENV{"DBI_DSN"}) {
    print "Missing ENV var: DBI_DSN is not defined\n";
    exit 0;
}


my($dbh, $sth, $sthi, $i, $sqli, $sql, $stha, $digest);

print "Running in verbose mode level $verbose\n" if $verbose>0;

my $sl = Sys::Syslog::setlogsock('unix');
$sl = Sys::Syslog::openlog('send-newsletter.pl', 'pid', $SYSLOG_LEVEL);
$sl = Sys::Syslog::syslog('info', 'Start');

print "Start\n"  if $verbose>0;

print "DBI connection : open\n" if $verbose>3;
$dbh = DBI->connect() || die $DBI::errstr;

#
#
# Lecture des infos de la base
#
#
#   email_subject      varchar(32) NOT NULL,
#   email_from_name    varchar(255) NOT NULL,
#   email_from_email   varchar(255) NOT NULL,
#   email_replyto      varchar(255) NOT NULL,
#   email_body         text,
#   target             smallint,
#   sql_target         text,
#   status             smallint NOT NULL DEFAULT 0,
#   date_send_request  datetime,   -- debut de l'envoi demandé
#   date_send_begin    datetime,   -- debut de l'envoi
#   date_send_end      datetime,   -- fin de l'envoi
#   nbsent             integer,    -- nombre de mails envoyés

my $sqli = "SELECT rowid, email_subject, email_from_name, email_from_email, email_replyto, email_body, target, sql_target, status, date_send_request, date_send_begin, date_send_end, nbsent";

$sqli .= " FROM llx_newsletter WHERE status=2 AND date_send_request < now()";
$sthi = $dbh->prepare($sqli);

$sthi->execute;

my ($hsri);
while ( $hsri = $sthi->fetchrow_hashref ) {

    #
    # Update newsletter
    #
    if (!$debug) {
	$stha = $dbh->prepare("UPDATE llx_newsletter SET status=4,date_send_begin=now() WHERE rowid=" . $hsri->{"rowid"});
	$stha->execute;
	$stha->finish;
    }

    #
    #
    #
    my ($fromemail, $from, $replyto, $subject, $mesg);

    $from      = $hsri->{"email_from_name"} . " <" . $hsri->{"email_from_email"} . ">";
    $fromemail = $hsri->{"email_from_email"};
    $replyto   = $hsri->{"email_replyto"};
    $mesg      = $hsri->{"email_body"};
    $subject   = $hsri->{"email_subject"};
    $sql       = $hsri->{"sql_target"};

    print "Message de : $from\n" if $verbose;

    #
    # Read dest
    #

    if ($sql) {

	$sth = $dbh->prepare($sql);
	$sth->execute;

	my($nbdest, $nberror) = (0,0);
    
	while (my $hsr = $sth->fetchrow_hashref )
	{

	    if (length($hsr->{"email"}) > 0)
	    {
		my $firstname = $hsr->{"firstname"};
		my $lastname = $hsr->{"name"};
		my $email = "$firstname $lastname <".$hsr->{"email"}.">";
		

		if (!$debug)
		{

		    if (! mail_it($hsr->{"email"},
				  $email,
				  $fromemail, 
				  $from,
				  $subject,
				  $mesg,
				  $replyto))
		    {
			$nberror++;
			print $nberror;

		    }
		    
		}
		else
		{
		    print "$nbdest : Mail $from -> ".$email."\n" if $verbose;
		}
	    }
	    
	    $nbdest++;	    
	}

	$sth->finish;

	#
	# Update newsletter
	#
	if (!$debug)
	{
	    $stha = $dbh->prepare("UPDATE llx_newsletter SET status=3,date_send_end=now(), nbsent=$nbdest, nberror=$nberror WHERE rowid=" . $hsri->{"rowid"});
	    $stha->execute;
	    $stha->finish;
	}
    } else {
	print "No sql request";
    }

}
$sthi->finish;

print "DBI connection : close\n" if $verbose>3;

$dbh->disconnect;

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

sub mail_it {
    my ($toemail, $to, $fromemail, $from, $subject, $mesg, $replyto) = @_;
    my ($smtp);

    $mesg = wrap("","",$mesg);

    $smtp = Net::SMTP->new('localhost',
			   Hello => 'localhost',
			   Timeout => 30);
    
    if ($smtp) {

	print "Mail $from -> ".$to."\n" if $verbose;

	if ($smtp->verify($toemail)) {

	    $smtp->mail($fromemail);
	    $smtp->to($toemail);
    
	    $smtp->data();
	    $smtp->datasend("From: $from\n");
	    $smtp->datasend("Reply-To: $replyto\n") if $replyto;
	    $smtp->datasend("Content-Type: text/plain; charset=\"iso-8859-1\"\n");
	    $smtp->datasend("To: $to\n");
	    $smtp->datasend("Subject: $subject\n");
	    $smtp->datasend("X-Mailer: Dolibarr\n");
	    $smtp->datasend("\n");
	
	    $smtp->datasend($mesg);
	    
	    $smtp->dataend();

	    return 1;

	} else {
	    return 0;
	}
	
	$smtp->quit;

    } else {
	return 0;
    }
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

