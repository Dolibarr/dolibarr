#!/usr/bin/perl
use strict;
use HTML::Entities ();

#-----------------------------------------------------------
#  jQuery File Tree Perl Connector
#
#  Version 1.0
#
#  Oleg Burlaca
#  http://www.burlaca.com/2009/02/jquery-file-tree-connector/
#  12 February 2009
#-----------------------------------------------------------

# for security reasons,  specify a root folder 
# to prevent the whole filesystem to be shown
# for ex: the root folder of your webbrowser
 
my $root = "/var/www/html/";

#----------------------------------------------------------

my $params = &getCGIParams();
print "Content-type: text/html\n\n";

my $dir = $params->{dir};
my $fullDir = $root . $dir;

exit if ! -e $fullDir;

opendir(BIN, $fullDir) or die "Can't open $dir: $!";
my (@folders, @files);
my $total = 0;
while( defined (my $file = readdir BIN) ) {
    next if $file eq '.' or $file eq '..';
    $total++;
    if (-d "$fullDir/$file") {
	push (@folders, $file);
    } else {
	push (@files, $file);
    }
}
closedir(BIN);

return if $total == 0;
print "<ul class=\"jqueryFileTree\" style=\"display: none;\">";

# print Folders
foreach my $file (sort @folders) {
    next if ! -e  $fullDir . $file;
    
    print '<li class="directory collapsed"><a href="#" rel="' . 
          &HTML::Entities::encode($dir . $file) . '/">' . 
          &HTML::Entities::encode($file) . '</a></li>';
}

# print Files
foreach my $file (sort @files) {
    next if ! -e  $fullDir . $file;

    $file =~ /\.(.+)$/;
    my $ext = $1;
    print '<li class="file ext_' . $ext . '"><a href="#" rel="' . 
    &HTML::Entities::encode($dir . $file) . '/">' .
    &HTML::Entities::encode($file) . '</a></li>';
}

print "</ul>\n";




#--------------------------------------------------------------------------------------------------
sub getCGIParams {
    my $line;
    
    if ($ENV{'REQUEST_METHOD'} eq "POST") {
        read(STDIN, $line, $ENV{'CONTENT_LENGTH'});
    } else {
        $line = $ENV{'QUERY_STRING'};
    }

    my (@pairs) = split(/&/, $line);
    my ($name, $value, %F);
        
    foreach (@pairs) {
        ($name, $value) = split(/=/);
        $value =~ tr/+/ /;
        $value =~ s/%([a-fA-F0-9][a-fA-F0-9])/pack("C", hex($1))/eg;
        
        if (! exists $F{$name}) {
            $F{$name} = $value;
        } elsif (exists $F{$name} and ref($F{$name}) ne 'ARRAY') {
            my $prev_value = $F{$name};
            delete $F{$name};
            $F{$name} = [ $prev_value, $value ];
	} else { push @{ $F{$name} }, $value }
    }
    return \%F;
}
#--------------------------------------------------------------------------------------------------                                                                                        
                                                                    