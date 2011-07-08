#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-dolibarrlang.pl
# \brief        Package builder (tgz, zip, rpm, deb, exe)
# \version      $Revision: 1.9 $
# \author       (c)2005 Laurent Destailleur  <eldy@users.sourceforge.net>
#----------------------------------------------------------------------------

use Cwd;

$PROJECT = "dolibarr";

@LISTETARGET       = ("TGZ");    # Possible packages
%REQUIREMENTTARGET = (           # Tool requirement for each package
	"TGZ" => "tar",
	"ZIP" => "7z",
	"EXE" => "makensis.exe"
);
%ALTERNATEPATH = (
	"7z"           => "7-ZIP",
	"makensis.exe" => "NSIS"
);

use vars qw/ $REVISION $VERSION /;
$REVISION = '$Revision: 1.9 $';
$REVISION =~ /\s(.*)\s/;
$REVISION = $1;
$VERSION  = "1.0 (build $REVISION)";

#------------------------------------------------------------------------------
# MAIN
#------------------------------------------------------------------------------
( $DIR  = $0 ) =~ s/([^\/\\]+)$//;
( $PROG = $1 ) =~ s/\.([^\.]*)$//;
$Extension = $1;
$DIR ||= '.';
$DIR =~ s/([^\/\\])[\\\/]+$/$1/;

# Detect OS type
# --------------
if ( "$^O" =~ /linux/i || ( -d "/etc" && -d "/var" && "$^O" !~ /cygwin/i ) ) {
	$OS = 'linux';
	$CR = '';
}
elsif ( -d "/etc" && -d "/Users" ) { $OS = 'macosx'; $CR = ''; }
elsif ( "$^O" =~ /cygwin/i || "$^O" =~ /win32/i ) {
	$OS = 'windows';
	$CR = "\r";
}
if ( !$OS ) {
	print "makepack-dolbarrlang.pl was not able to detect your OS.\n";
	print "Can't continue.\n";
	print "makepack-dolibarrlang.pl aborted.\n";
	sleep 2;
	exit 1;
}

# Define buildroot
# ----------------
if ( $OS =~ /linux/ ) {
	$TEMP = $ENV{"TEMP"} || $ENV{"TMP"} || "/tmp";
}
if ( $OS =~ /macos/ ) {
	$TEMP = $ENV{"TEMP"} || $ENV{"TMP"} || "/tmp";
}
if ( $OS =~ /windows/ ) {
	$TEMP = $ENV{"TEMP"} || $ENV{"TMP"} || "c:/temp";
	$PROGPATH = $ENV{"ProgramFiles"};
}
if ( !$TEMP || !-d $TEMP ) {
	print "Error: A temporary directory can not be find.\n";
	print "Check that TEMP or TMP environment variable is set correctly.\n";
	print "makepack-dolibarrlang.pl aborted.\n";
	sleep 2;
	exit 2;
}
$BUILDROOT = "$TEMP/dolibarr-buildroot";

my $copyalreadydone = 0;
my $batch           = 0;

print "Makepack langs version $VERSION\n";
print "Enter language code to package (en_US, fr_FR, ...) : ";
$PROJECT = <STDIN>;
chomp($PROJECT);

# Ask and set version $MAJOR and $MINOR
print "Enter value for version: ";
$PROJVERSION = <STDIN>;
chomp($PROJVERSION);
( $MAJOR, $MINOR ) = split( /\./, $PROJVERSION, 2 );
if ( $MINOR eq '' ) {
	print "Enter value for minor version: ";
	$MINOR = <STDIN>;
	chomp($MINOR);
}

$FILENAME    = "$PROJECT";
$FILENAMETGZ = "lang_$PROJECT-$MAJOR.$MINOR";
if ( -d "/usr/src/redhat" ) {

	# redhat
	$RPMDIR = "/usr/src/redhat";
}
if ( -d "/usr/src/RPM" ) {

	# mandrake
	$RPMDIR = "/usr/src/RPM";
}

$SOURCE = "$DIR/../../dolibarr";
$DESTI  = "$SOURCE/build";

# Choose package targets
#-----------------------
$target = "ZIP";    # Les langs sont au format zip
if ($target) {
	$CHOOSEDTARGET{ uc($target) } = 1;
}
else {
	my $found = 0;
	my $NUM_SCRIPT;
	while ( !$found ) {
		my $cpt = 0;
		printf( " %d - %3s    (%s)\n",
			$cpt, "All", "Need " . join( ",", values %REQUIREMENTTARGET ) );
		foreach my $target (@LISTETARGET) {
			$cpt++;
			printf( " %d - %3s    (%s)\n",
				$cpt, $target, "Need " . $REQUIREMENTTARGET{$target} );
		}

		# Are asked to select the file to move
		print "Choose one package number or several separated with space: ";
		$NUM_SCRIPT = <STDIN>;
		chomp($NUM_SCRIPT);
		if ( $NUM_SCRIPT =~ s/-//g ) {

			# Do not do copy
			$copyalreadydone = 1;
		}
		if ( $NUM_SCRIPT !~ /^[0-$cpt\s]+$/ ) {
			print "This is not a valid package number list.\n";
			$found = 0;
		}
		else {
			$found = 1;
		}
	}
	print "\n";
	if ($NUM_SCRIPT) {
		foreach my $num ( split( /\s+/, $NUM_SCRIPT ) ) {
			$CHOOSEDTARGET{ $LISTETARGET[ $num - 1 ] } = 1;
		}
	}
	else {
		foreach my $key (@LISTETARGET) {
			$CHOOSEDTARGET{$key} = 1;
		}
	}
}

# Test if requirement is ok
#--------------------------
foreach my $target ( keys %CHOOSEDTARGET ) {
	foreach my $req ( split( /[,\s]/, $REQUIREMENTTARGET{$target} ) ) {

		# Test
		print "Test requirement for target $target: Search '$req'... ";
		$ret         = `"$req" 2>&1`;
		$coderetour  = $?;
		$coderetour2 = $coderetour >> 8;
		if (
			$coderetour != 0
			&& (   ( $coderetour2 == 1 && $OS =~ /windows/ && $ret !~ /Usage/i )
				|| ( $coderetour2 == 127 && $OS !~ /windows/ ) )
			&& $PROGPATH
		  )
		{

			# Not found error, we try in PROGPATH
			$ret         = `"$PROGPATH/$ALTERNATEPATH{$req}/$req\" 2>&1`;
			$coderetour  = $?;
			$coderetour2 = $coderetour >> 8;
			$REQUIREMENTTARGET{$target} = "$PROGPATH/$ALTERNATEPATH{$req}/$req";
		}

		if (
			$coderetour != 0
			&& (   ( $coderetour2 == 1 && $OS =~ /windows/ && $ret !~ /Usage/i )
				|| ( $coderetour2 == 127 && $OS !~ /windows/ ) )
		  )
		{

			# Not found error
			print
"Not found\nCan't build target $target. Requirement '$req' not found in PATH\n";
			$CHOOSEDTARGET{$target} = -1;
			last;
		}
		else {

			# Pas erreur ou erreur autre que programme absent
			print " Found " . $REQUIREMENTTARGET{$target} . "\n";
		}
	}
}

print "\n";

# Check if there is at least on target to build
#----------------------------------------------
$nboftargetok = 0;
foreach my $target ( keys %CHOOSEDTARGET ) {
	if ( $CHOOSEDTARGET{$target} < 0 ) { next; }
	$nboftargetok++;
}

if ($nboftargetok) {

	# Update buildroot
	#-----------------
	if ( !$copyalreadydone ) {
		print "Delete directory $BUILDROOT\n";
		$ret = `rm -fr "$BUILDROOT"`;
		mkdir "$BUILDROOT";
		mkdir "$BUILDROOT/htdocs";
		mkdir "$BUILDROOT/htdocs/langs";
		mkdir "$BUILDROOT/htdocs/langs/$PROJECT";

		print "Copy $SOURCE into $BUILDROOT\n";
		mkdir "$BUILDROOT";
		$ret =
		  `cp -pr "$SOURCE/htdocs/langs/$PROJECT" "$BUILDROOT/htdocs/langs"`;
	}
	print "Clean $BUILDROOT\n";
	$ret =
`rm -fr $BUILDROOT/htdocs/langs/$PROJECT/Thumbs.db $BUILDROOT/htdocs/langs/$PROJECT/*/Thumbs.db $BUILDROOT/htdocs/langs/$PROJECT/*/*/Thumbs.db $BUILDROOT/htdocs/langs/$PROJECT/*/*/*/Thumbs.db`;
	$ret =
`rm -fr $BUILDROOT/htdocs/langs/$PROJECT/CVS* $BUILDROOT/htdocs/langs/$PROJECT/*/CVS* $BUILDROOT/htdocs/langs/$PROJECT/*/*/CVS* $BUILDROOT/htdocs/langs/$PROJECT/*/*/*/CVS* $BUILDROOT/htdocs/langs/$PROJECT/*/*/*/*/CVS* $BUILDROOT/htdocs/langs/$PROJECT/*/*/*/*/*/CVS*`;

	# Build package for each target
	#------------------------------
	foreach my $target ( keys %CHOOSEDTARGET ) 
	{
		if ( $CHOOSEDTARGET{$target} < 0 ) { next; }

		print "\nBuild package for target $target\n";

		if ( $target eq 'TGZ' ) 
		{
			unlink $FILENAMETGZ . tgz;

			#    		unlink $BUILDROOT/$FILENAMETGZ.tgz;
			print "Compress $BUILDROOT/htdocs into $FILENAMETGZ.tgz...\n";
			$cmd =
"tar --exclude-vcs --exclude-from \"$DESTI/tgz/tar.exclude\" --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$FILENAMETGZ.tgz\" htdocs";
			$ret = `$cmd`;

#        	$cmd="tar --exclude-vcs --exclude-from \"$DESTI/tgz/tar.exclude\" --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$BUILDROOT/$FILENAMETGZ.tgz\" htdocs\n";
#        	$ret=`$cmd`;
			if ( $OS =~ /windows/i ) {
				print "Move $FILENAMETGZ.tgz to $DESTI/$FILENAMETGZ.tgz\n";
				$ret = `mv "$FILENAMETGZ.tgz" "$DESTI/$FILENAMETGZ.tgz"`;

   #        		$ret=`mv "$BUILDROOT/$FILENAMETGZ.tgz" "$DESTI/$FILENAMETGZ.tgz"`;
			}
			next;
		}

		if ( $target eq 'ZIP' ) 
		{
			unlink $FILENAMEZIP . zip;
			print "Compress $FILENAMETGZ into $FILENAMEZIP.zip...\n";
			chdir("$BUILDROOT");

#print "cd $BUILDROOTNT & 7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*\n";
#$ret=`cd $BUILDROOTNT & 7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*`;
			$ret =
			  `7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*`;
			print "Move $FILENAMEZIP.zip to $DESTI\n";
			rename( "$BUILDROOT/$FILENAMEZIP.zip", "$DESTI/$FILENAMEZIP.zip" );
			next;
		}
	}
}

print "\n----- Summary -----\n";
foreach my $target ( keys %CHOOSEDTARGET ) {
	if ( $CHOOSEDTARGET{$target} < 0 ) {
		print "Package $target not built (bad requirement).\n";
	}
	else {
		print "Package $target built succeessfully in $DESTI\n";
	}
}

if ( !$btach ) {
	print "\nPress key to finish...";
	my $WAITKEY = <STDIN>;
}

0;
