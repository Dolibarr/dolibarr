#!/usr/bin/perl
#-------------------------------------------------------------------------
# \file         build/makepack-dolibarr.pl
# \brief        Generateur de packages (tgz, zip, rpm, deb, exe)
# \version      $Revision$
# \author       (c) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
#-------------------------------------------------------------------------

use Cwd;

$PROJECT="dolibarr";
$MAJOR="2";
$MINOR="0";
$RPMSUBVERSION="1";

@LISTETARGET=("TGZ","ZIP","RPM","DEB","EXE");   # Possible packages
%REQUIREMENTTARGET=(                            # Tool requirement for each package
"TGZ"=>"tar",
"ZIP"=>"7z",
"RPM"=>"rpmbuild",
"DEB"=>"dpkg-buildpackage",
"EXE"=>"makensis.exe");
%ALTERNATEPATH=(
"7z"=>"7-ZIP",
"makensis.exe"=>"NSIS"
);


$FILENAMETGZ="$PROJECT-$MAJOR.$MINOR";
$FILENAMEZIP="$PROJECT-$MAJOR.$MINOR";
$FILENAMERPM="$PROJECT-$MAJOR.$MINOR-$RPMSUBVERSION";
$FILENAMEDEB="$PROJECT-$MAJOR.$MINOR";
$FILENAMEEXE="$PROJECT-$MAJOR.$MINOR";
$RPMSRC="/usr/src/redhat/SOURCES";

use vars qw/ $REVISION $VERSION /;
$REVISION='$Revision$'; $REVISION =~ /\s(.*)\s/; $REVISION=$1;
$VERSION="1.0 (build $REVISION)";



#------------------------------------------------------------------------------
# MAIN
#------------------------------------------------------------------------------
($DIR=$0) =~ s/([^\/\\]+)$//; ($PROG=$1) =~ s/\.([^\.]*)$//; $Extension=$1;
$DIR||='.'; $DIR =~ s/([^\/\\])[\\\/]+$/$1/;

$SOURCE="$DIR/../../dolibarr";

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

# Choose package targets
#-----------------------
print "Makepack version $VERSION\n";
print "Building package for $PROJECT $MAJOR.$MINOR\n";
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

# Test if requirement is ok
#--------------------------
foreach my $target (keys %CHOOSEDTARGET) {
    foreach my $req (split(/[,\s]/,$REQUIREMENTTARGET{$target})) {
        # Test    
        print "Test requirement for target $target: Search '$req'... ";
        $ret=`"$req" 2>&1`;
        $coderetour=$?; $coderetour2=$coderetour>>8;
        if ($coderetour != 0 && ($coderetour2 == 1 || $coderetour2 == 127) && $PROGPATH) { 
            # If error not found, we try in PROGPATH
            $ret=`"$PROGPATH/$ALTERNATEPATH{$req}/$req\" 2>&1`;
            $coderetour=$?; $coderetour2=$coderetour>>8;
            $REQUIREMENTTARGET{$target}="$PROGPATH/$ALTERNATEPATH{$req}/$req";
        }    

        if ($coderetour == 0 || ($coderetour2 > 1 && $coderetour2 < 127) || $ret =~ /Usage/) {
            # Pas erreur ou erreur autre que programme absent
            print " Found ".$REQUIREMENTTARGET{$target}."\n";
        } else {
            print "Not found\nCan't build target $target. Requirement '$req' not found in PATH\n";
            $CHOOSEDTARGET{$target}=-1;
            last;
        }
    }
}

print "\n";

# Check if there is at least on target to build
#----------------------------------------------
$nboftargetok=0;
foreach $target (keys %CHOOSEDTARGET) {
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
    	print "Copy $SOURCE into $BUILDROOT\n";
    	mkdir "$BUILDROOT";
    	$ret=`cp -pr "$SOURCE" "$BUILDROOT"`;
    }
    print "Clean $BUILDROOT\n";
    $ret=`rm -fr $BUILDROOT/$PROJECT/document`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/build`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/Thumbs.db $BUILDROOT/$PROJECT/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/Thumbs.db`;
    $ret=`rm -fr $BUILDROOT/$PROJECT/CVS* $BUILDROOT/$PROJECT/*/CVS* $BUILDROOT/$PROJECT/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/CVS*`;
    rename("$BUILDROOT/$PROJECT","$BUILDROOT/$FILENAMETGZ");
    
    
    # Build package for each target
    #------------------------------
    foreach $target (keys %CHOOSEDTARGET) {
        if ($CHOOSEDTARGET{$target} < 0) { next; }
    
        print "\nBuild pack for target $target\n";
        
    	if ($target eq 'TGZ') {
    		unlink $FILENAMETGZ.tgz;
    		print "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
    		$ret=`tar --exclude-from "$SOURCE/build/tgz/tar.exclude" --directory "$BUILDROOT" -czvf "$BUILDROOT/$FILENAMETGZ.tgz" $FILENAMETGZ`;
    		print "Move $FILENAMETGZ.tgz to $SOURCE/build/$FILENAMETGZ.tgz\n";
    		rename("$BUILDROOT/$FILENAMETGZ.tgz","$SOURCE/build/$FILENAMETGZ.tgz");
    		next;
    	}	
    
    	if ($target eq 'ZIP') {
    		unlink $FILENAMEZIP.zip;
    		print "Compress $FILENAMETGZ into $FILENAMEZIP.zip...\n";
     		chdir("$BUILDROOT");
            #print "cd $BUILDROOTNT & 7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*\n";
            #$ret=`cd $BUILDROOTNT & 7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*`;
    		$ret=`7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*`;
    		print "Move $FILENAMEZIP.zip to $SOURCE/build/$FILENAMEZIP.zip\n";
    		rename("$BUILDROOT/$FILENAMEZIP.zip","$SOURCE/build/$FILENAMEZIP.zip");
    		next;
    	}
    
    	if ($target eq 'RPM') {
    		$BUILDFIC="$FILENAMETGZ.spec";
    		unlink $FILENAMETGZ.tgz;
    		print "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
    		$ret=`tar --exclude-from "$SOURCE/build/tgz/tar.exclude" --directory "$BUILDROOT" -czvf "$BUILDROOT/$FILENAMETGZ.tgz" $FILENAMETGZ`;

    		print "Move $FILENAMETGZ.tgz to $RPMSRC/$FILENAMETGZ.tgz\n";
    		if (rename("$BUILDROOT/$FILENAMETGZ.tgz","$RPMSRC/$FILENAMETGZ.tgz")==0) {
    		    print "Error: Failed to rename '$BUILDROOT/$FILENAMETGZ.tgz' into '$RPMSRC/$FILENAMETGZ.tgz'\n";
    		}

    		print "Copy $SOURCE/build/rpm/${BUILDFIC} to $BUILDROOT\n";
    		$ret=`cp -p "$SOURCE/build/rpm/${BUILDFIC}" "$BUILDROOT"`;
    		#rename("$SOURCE/build/rpm/${BUILDFIC}","$BUILDROOT");
    
    		print "Launch RPM build (rpm --clean -ba $BUILDROOT/${BUILDFIC})\n";
    		$ret=`rpm --clean -ba $BUILDROOT/${BUILDFIC}`;
    	
    		print "Move /usr/src/RPM/RPMS/noarch/${FILENAMERPM}.noarch.rpm into $SOURCE/build/${FILENAMERPM}.noarch.rpm\n";
    		rename("/usr/src/RPM/RPMS/noarch/${FILENAMERPM}.noarch.rpm","$SOURCE/build/${FILENAMERPM}.noarch.rpm");
    		next;
    	}
    	
    	if ($target eq 'DEB') {
            print "Automatic build for DEB is not yet supported.\n";
        }
        
    	if ($target eq 'EXE') {
    		unlink "$FILENAMEEXE.exe";
    		print "Compress into $FILENAMEEXE.exe by $FILENAMEEXE.nsi...\n";
    		$ret=`"$REQUIREMENTTARGET{$target}" /X"SetCompressor bzip2" "$SOURCE\\build\\exe\\$FILENAMEEXE.nsi"`;
    		print "Move $FILENAMEEXE.exe to $SOURCE/build/$FILENAMEEXE.exe\n";
    		rename("$SOURCE\\build\\exe\\$FILENAMEEXE.exe","$SOURCE/build/$FILENAMEEXE.exe");
    		next;
    	}
    
    }

}

print "\n----- Summary -----\n";
foreach $target (keys %CHOOSEDTARGET) {
    if ($CHOOSEDTARGET{$target} < 0) {
        print "Package $target not built (bad requirement).\n";
    } else {
        print "Package $target built succeessfully in $SOURCE/build/\n";
    }
}

print "\nPress key to finish...";
my $WAITKEY=<STDIN>;

0;
