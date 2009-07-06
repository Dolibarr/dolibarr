#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-dolibarrmodule.pl
# \brief        Package builder (tgz, zip, rpm, deb, exe)
# \version      $Revision$
# \author       (c)2005-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
#----------------------------------------------------------------------------

use Cwd;

$PROJECT="mymodule";

@LISTETARGET=("TGZ");   # Possible packages
%REQUIREMENTTARGET=(    # Tool requirement for each package
"TGZ"=>"tar",
);
%ALTERNATEPATH=(
);


use vars qw/ $REVISION $VERSION /;
$REVISION='$Revision$'; $REVISION =~ /\s(.*)\s/; $REVISION=$1;
$VERSION="1.0 (build $REVISION)";



#------------------------------------------------------------------------------
# MAIN
#------------------------------------------------------------------------------
($DIR=$0) =~ s/([^\/\\]+)$//; ($PROG=$1) =~ s/\.([^\.]*)$//; $Extension=$1;
$DIR||='.'; $DIR =~ s/([^\/\\])[\\\/]+$/$1/;

# Detect OS type
# --------------
if ("$^O" =~ /linux/i || (-d "/etc" && -d "/var" && "$^O" !~ /cygwin/i)) { $OS='linux'; $CR=''; }
elsif (-d "/etc" && -d "/Users") { $OS='macosx'; $CR=''; }
elsif ("$^O" =~ /cygwin/i || "$^O" =~ /win32/i) { $OS='windows'; $CR="\r"; }
if (! $OS) {
    print "makepack-dolbarrmodule.pl was not able to detect your OS.\n";
	print "Can't continue.\n";
	print "makepack-dolibarrmodule.pl aborted.\n";
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
	print "makepack-dolibarrmodule.pl aborted.\n";
    sleep 2;
    exit 2;
} 
$BUILDROOT="$TEMP/dolibarr-buildroot";


my $copyalreadydone=0;
my $batch=0;

print "Makepack module version $VERSION\n";
print "Enter name for your module (mymodule, mywonderfulmondule, ...) : ";
$PROJECT=<STDIN>;
chomp($PROJECT);
print "Move to ".$DIR." directory.\n";
chdir($DIR);

if (! -f "makepack-".$PROJECT.".conf")
{
    print "Error: can't open conf file makepack-".$PROJECT.".conf\n";
	print "Check that current directory is 'build' directory\n";
	print "makepack-dolibarrmodule.pl aborted.\n";
    sleep 2;
    exit 2;
}
# Ask and set version $MAJOR and $MINOR
print "Enter value for version: ";
$PROJVERSION=<STDIN>;
chomp($PROJVERSION);
($MAJOR,$MINOR)=split(/\./,$PROJVERSION,2);
if ($MINOR eq '')
{
	print "Enter value for minor version: ";
	$MINOR=<STDIN>;
	chomp($MINOR);
}

$FILENAME="$PROJECT";
$FILENAMETGZ="module_$PROJECT-$MAJOR.$MINOR";
if (-d "/usr/src/redhat") {
    # redhat
    $RPMDIR="/usr/src/redhat";
}
if (-d "/usr/src/RPM") {
    # mandrake
    $RPMDIR="/usr/src/RPM";
}

$SOURCE="$DIR/..";
$DESTI="$SOURCE/build";


# Choose package targets
#-----------------------
$target="TGZ";    # Dolibarr modules are tgz format
$CHOOSEDTARGET{uc($target)}=1;


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
    	
		$result=open(IN,"<makepack-".$PROJECT.".conf");
		if (! $result) { die "Error: Can't open conf file makepack-".$PROJECT.".conf for reading.\n"; }
	    while(<IN>)
	    {
	    	if ($_ =~ /^#/) { next; }	# Do not process comments
			
			$_ =~ s/\n//;
	    	$_ =~ /^(.*)\/[^\/]+/;
	    	print "Create directory $BUILDROOT/$1\n";
	    	$ret=`mkdir -p "$BUILDROOT/$1"`;
	    	print "Copy $SOURCE/$_ into $BUILDROOT/$_\n";
    		$ret=`cp -pr "$SOURCE/$_" "$BUILDROOT/$_"`;
		}	
		close IN;
		
		@timearray=localtime(time());
		$fulldate=($timearray[5]+1900).'-'.($timearray[4]+1).'-'.$timearray[3].' '.$timearray[2].':'.$timearray[1];
		$versionfile=open(VF,">$BUILDROOT/build/version-".$PROJECT.".txt");

		print "Create version file $BUILDROOT/build/version-".$PROJECT.".txt with date ".$fulldate."\n";
		$ret=`mkdir -p "$BUILDROOT/build"`;
		print VF "Version: ".$MAJOR.".".$MINOR."\n";
		print VF "Build  : ".$fulldate."\n";
		close VF;
    }
    
    
    # Build package for each target
    #------------------------------
    foreach my $target (keys %CHOOSEDTARGET) {
        if ($CHOOSEDTARGET{$target} < 0) { next; }
    
        print "\nBuild package for target $target\n";
        
    	if ($target eq 'TGZ') {
    		unlink $FILENAMETGZ.tgz;
    		print "Compress $BUILDROOT/* into $FILENAMETGZ.tgz...\n";
   		    $cmd="tar --exclude-vcs --directory \"$BUILDROOT\" -czvf \"$FILENAMETGZ.tgz\" .";
   		    $ret=`$cmd`;
            if ($OS =~ /windows/i) {
        		print "Move $FILENAMETGZ.tgz to $DESTI/$FILENAMETGZ.tgz\n";
        		$ret=`mv "$FILENAMETGZ.tgz" "$DESTI/$FILENAMETGZ.tgz"`;
            }
    		next;
    	}

    	if ($target eq 'ZIP') {
    		unlink $FILENAMEZIP.zip;
    		print "Compress $FILENAMETGZ into $FILENAMEZIP.zip...\n";
     		chdir("$BUILDROOT");
            #print "cd $BUILDROOTNT & 7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*\n";
            #$ret=`cd $BUILDROOTNT & 7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*`;
    		$ret=`7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*.*`;
			print "Move $FILENAMEZIP.zip to $DESTI\n";
    		rename("$BUILDROOT/$FILENAMEZIP.zip","$DESTI/$FILENAMEZIP.zip");
    		next;
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
        print "Package $target built successfully in $DESTI\n";
    }
}

if (! $btach) {
    print "\nPress key to finish...";
    my $WAITKEY=<STDIN>;
}

0;
