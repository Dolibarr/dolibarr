#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-dolibarr.pl
# \brief        Dolibarr package builder (tgz, zip, rpm, deb, exe)
# \version      $Revision$
# \author       (c)2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
#----------------------------------------------------------------------------

use Cwd;

$PROJECT="dolibarr";
$MAJOR="2";
$MINOR="6";
$BUILD="0-beta";				# Mettre x pour release, ou x-beta pour beta
$RPMSUBVERSION="1";		# A incrementer au moment de la release

@LISTETARGET=("TGZ","ZIP","RPM","DEB","EXE");   # Possible packages
%REQUIREMENTTARGET=(                            # Tool requirement for each package
"TGZ"=>"tar",
"ZIP"=>"7z",
"RPM"=>"rpmbuild",
"DEB"=>"dpkg-buildpackage",
"EXE"=>"makensis.exe"
);
%ALTERNATEPATH=(
"7z"=>"7-ZIP",
"makensis.exe"=>"NSIS"
);

$FILENAME="$PROJECT";
$FILENAMETGZ="$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEZIP="$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMERPM="$PROJECT-$MAJOR.$MINOR.$BUILD-$RPMSUBVERSION";
$FILENAMEDEB="$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEEXE="$PROJECT-$MAJOR.$MINOR.$BUILD";
if (-d "/usr/src/redhat") {
    # redhat
    $RPMDIR="/usr/src/redhat";
}
if (-d "/usr/src/RPM") {
    # mandrake
    $RPMDIR="/usr/src/RPM";
}


use vars qw/ $REVISION $VERSION /;
$REVISION='$Revision$'; $REVISION =~ /\s(.*)\s/; $REVISION=$1;
$VERSION="1.0 (build $REVISION)";



#------------------------------------------------------------------------------
# MAIN
#------------------------------------------------------------------------------
($DIR=$0) =~ s/([^\/\\]+)$//; ($PROG=$1) =~ s/\.([^\.]*)$//; $Extension=$1;
$DIR||='.'; $DIR =~ s/([^\/\\])[\\\/]+$/$1/;

$SOURCE="$DIR/..";
$DESTI="$SOURCE/build";

# Detect OS type
# --------------
if ("$^O" =~ /linux/i || (-d "/etc" && -d "/var" && "$^O" !~ /cygwin/i)) { $OS='linux'; $CR=''; }
elsif (-d "/etc" && -d "/Users") { $OS='macosx'; $CR=''; }
elsif ("$^O" =~ /cygwin/i || "$^O" =~ /win32/i) { $OS='windows'; $CR="\r"; }
if (! $OS) {
    print "makepack-dolbarr.pl was not able to detect your OS.\n";
	print "Can't continue.\n";
	print "makepack-dolibarr.pl aborted.\n";
    sleep 2;
	exit 1;
}

# Define buildroot
# ----------------
if ($OS =~ /linux/) {
    $TEMP=$ENV{"TEMP"}||$ENV{"TMP"}||"/tmp";
}
if ($OS =~ /macos/) {
    $TEMP=$ENV{"TEMP"}||$ENV{"TMP"}||"/tmp";
}
if ($OS =~ /windows/) {
    $TEMP=$ENV{"TEMP"}||$ENV{"TMP"}||"c:/temp";
    $PROGPATH=$ENV{"ProgramFiles"};
}
if (! $TEMP || ! -d $TEMP) {
    print "Error: A temporary directory can not be find.\n";
    print "Check that TEMP or TMP environment variable is set correctly.\n";
	print "makepack-dolibarr.pl aborted.\n";
    sleep 2;
    exit 2;
} 
$BUILDROOT="$TEMP/buildroot";


my $copyalreadydone=0;
my $batch=0;

print "Makepack version $VERSION\n";
print "Building package name: $PROJECT\n";
print "Building package version: $MAJOR.$MINOR.$BUILD\n";

for (0..@ARGV-1) {
	if ($ARGV[$_] =~ /^-*target=(\w+)/i)    { $target=$1; $batch=1; }
}

# Choose package targets
#-----------------------
if ($target) {
    $CHOOSEDTARGET{uc($target)}=1;
}
else {
    my $found=0;
    my $NUM_SCRIPT;
    while (! $found) {
    	my $cpt=0;
    	printf(" %d - %3s    (%s)\n",$cpt,"All","Need ".join(",",values %REQUIREMENTTARGET));
    	foreach my $target (@LISTETARGET) {
    		$cpt++;
    		printf(" %d - %3s    (%s)\n",$cpt,$target,"Need ".$REQUIREMENTTARGET{$target});
    	}
    
    	# On demande de choisir le fichier à passer
    	print "Choose one package number or several separated with space: ";
    	$NUM_SCRIPT=<STDIN>; 
    	chomp($NUM_SCRIPT);
    	if ($NUM_SCRIPT =~ s/-//g) {
    		# Do not do copy	
    		$copyalreadydone=1;
    	}
    	if ($NUM_SCRIPT !~ /^[0-$cpt\s]+$/)
    	{
    		print "This is not a valid package number list.\n";
    		$found = 0;
    	}
    	else
    	{
    		$found = 1;
    	}
    }
    print "\n";
    if ($NUM_SCRIPT) {
    	foreach my $num (split(/\s+/,$NUM_SCRIPT)) {
    		$CHOOSEDTARGET{$LISTETARGET[$num-1]}=1;
    	}
    }
    else {
    	foreach my $key (@LISTETARGET) {
    	    $CHOOSEDTARGET{$key}=1;
        }
    }
}

# Test if requirement is ok
#--------------------------
foreach my $target (keys %CHOOSEDTARGET) {
    foreach my $req (split(/[,\s]/,$REQUIREMENTTARGET{$target})) {
        # Test    
        print "Test requirement for target $target: Search '$req'... ";
        $ret=`"$req" 2>&1`;
        $coderetour=$?; $coderetour2=$coderetour>>8;
        if ($coderetour != 0 && (($coderetour2 == 1 && $OS =~ /windows/ && $ret !~ /Usage/i) || ($coderetour2 == 127 && $OS !~ /windows/)) && $PROGPATH) { 
            # Not found error, we try in PROGPATH
            $ret=`"$PROGPATH/$ALTERNATEPATH{$req}/$req\" 2>&1`;
            $coderetour=$?; $coderetour2=$coderetour>>8;
            $REQUIREMENTTARGET{$target}="$PROGPATH/$ALTERNATEPATH{$req}/$req";
        }    

        if ($coderetour != 0 && (($coderetour2 == 1 && $OS =~ /windows/ && $ret !~ /Usage/i) || ($coderetour2 == 127 && $OS !~ /windows/))) {
            # Not found error
            print "Not found\nCan't build target $target. Requirement '$req' not found in PATH\n";
            $CHOOSEDTARGET{$target}=-1;
            last;
        } else {
            # Pas erreur ou erreur autre que programme absent
            print " Found ".$REQUIREMENTTARGET{$target}."\n";
        }
    }
}

print "\n";

# Check if there is at least on target to build
#----------------------------------------------
$nboftargetok=0;
foreach my $target (keys %CHOOSEDTARGET) {
    if ($CHOOSEDTARGET{$target} < 0) { next; }
    $nboftargetok++;
}

if ($nboftargetok) {

    # Update buildroot
    #-----------------
    if (! $copyalreadydone) {
    	print "Delete directory $BUILDROOT\n";
    	$ret=`rm -fr "$BUILDROOT"`;
    
    	mkdir "$BUILDROOT";
    	mkdir "$BUILDROOT/dolibarr";
    	print "Copy $SOURCE into $BUILDROOT/dolibarr\n";
    	$ret=`cp -pr "$SOURCE" "$BUILDROOT/dolibarr"`;
    }
    print "Clean $BUILDROOT\n";
    $ret=`rm -fr $BUILDROOT/$PROJECT/index.php`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/documents`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/document`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/build`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/Thumbs.db $BUILDROOT/$PROJECT/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/*/Thumbs.db`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/CVS* $BUILDROOT/$PROJECT/*/CVS* $BUILDROOT/$PROJECT/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/CVS*`;
    rename("$BUILDROOT/$PROJECT","$BUILDROOT/$FILENAMETGZ");
    
    
    # Build package for each target
    #------------------------------
    foreach my $target (keys %CHOOSEDTARGET) {
        if ($CHOOSEDTARGET{$target} < 0) { next; }
    
        print "\nBuild package for target $target\n";
        
    	if ($target eq 'TGZ') {
    		unlink $FILENAMETGZ.tgz;
    		print "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
   		    $cmd="tar --exclude-vcs --exclude-from \"$DESTI/tgz/tar_exclude.txt\" --directory \"$BUILDROOT\" -czvf \"$FILENAMETGZ.tgz\" $FILENAMETGZ";
   		    $ret=`$cmd`;
            if ($OS =~ /windows/i)
            {
        		print "Move $FILENAMETGZ.tgz to $DESTI/$FILENAMETGZ.tgz\n";
        		$ret=`mv "$FILENAMETGZ.tgz" "$DESTI/$FILENAMETGZ.tgz"`;
            }
            else
            {
        		$ret=`mv "$FILENAMETGZ.tgz" "$DESTI/$FILENAMETGZ.tgz"`;
            }
    		next;
    	}

    	if ($target eq 'ZIP') {
    		unlink $FILENAMEZIP.zip;
    		print "Compress $FILENAMETGZ into $FILENAMEZIP.zip...\n";
     		chdir("$BUILDROOT");
    		$cmd= "7z a -r -tzip -xr\@\"$DESTI\/zip\/zip_exclude.txt\" -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*";
			$ret= `$cmd`;
			#print $ret;
			print "Move $FILENAMEZIP.zip to $DESTI\n";
    		rename("$BUILDROOT/$FILENAMEZIP.zip","$DESTI/$FILENAMEZIP.zip");
    		next;
    	}
    
    	if ($target eq 'RPM') {                 # Linux only
    		$BUILDFIC="$FILENAME.spec";
    		unlink $FILENAMETGZ.tgz;
    		print "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
    		$ret=`tar --exclude-from "$SOURCE/build/tgz/tar_exclude.txt" --directory "$BUILDROOT" -czvf "$BUILDROOT/$FILENAMETGZ.tgz" $FILENAMETGZ`;

    		print "Move $FILENAMETGZ.tgz to $RPMDIR/SOURCES/$FILENAMETGZ.tgz\n";
    		$cmd="mv \"$BUILDROOT/$FILENAMETGZ.tgz\" \"$RPMDIR/SOURCES/$FILENAMETGZ.tgz\"";
            $ret=`$cmd`;

    		print "Copy $SOURCE/make/rpm/${BUILDFIC} to $BUILDROOT\n";
#    		$ret=`cp -p "$SOURCE/make/rpm/${BUILDFIC}" "$BUILDROOT"`;
            open (SPECFROM,"<$SOURCE/make/rpm/${BUILDFIC}") || die "Error";
            open (SPECTO,">$BUILDROOT/$BUILDFIC") || die "Error";
            while (<SPECFROM>) {
                $_ =~ s/__VERSION__/$MAJOR.$MINOR.$BUILD/;
                print SPECTO $_;
            }
            close SPECFROM;
            close SPECTO;
    
    		print "Launch RPM build (rpm --clean -ba $BUILDROOT/${BUILDFIC})\n";
    		$ret=`rpm --clean -ba $BUILDROOT/${BUILDFIC}`;
    	
   		    print "Move $RPMDIR/RPMS/noarch/${FILENAMERPM}.noarch.rpm into $DESTI/${FILENAMERPM}.noarch.rpm\n";
   		    $cmd="mv \"$RPMDIR/RPMS/noarch/${FILENAMERPM}.noarch.rpm\" \"$DESTI/${FILENAMERPM}.noarch.rpm\"";
    		$ret=`$cmd`;
    		next;
    	}
    	
    	if ($target eq 'DEB') {
            print "Automatic build for DEB is not yet supported.\n";
        }
        
    	if ($target eq 'EXE') {
    		unlink "$FILENAMEEXE.exe";
    		print "Compress into $FILENAMEEXE.exe by $FILENAMEEXE.nsi...\n";
    		$command="\"$REQUIREMENTTARGET{$target}\" /DMUI_VERSION_DOT=$MAJOR.$MINOR.$BUILD /X\"SetCompressor bzip2\" \"$SOURCE\\build\\exe\\$FILENAME.nsi\"";
            print "$command\n";
    		$ret=`$command`;
    		print "Move $FILENAMEEXE.exe to $DESTI\n";
    		rename("$SOURCE\\build\\exe\\$FILENAMEEXE.exe","$DESTI/$FILENAMEEXE.exe");
    		next;
    	}
    
    }

}

print "\n----- Summary -----\n";
foreach my $target (keys %CHOOSEDTARGET) {
    if ($CHOOSEDTARGET{$target} < 0) {
        print "Package $target not built (bad requirement).\n";
    } else {
        print "Package $target built succeessfully in $DESTI\n";
    }
}

if (! $btach) {
    print "\nPress key to finish...";
    my $WAITKEY=<STDIN>;
}

0;
