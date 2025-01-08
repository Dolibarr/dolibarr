#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-dolibarrmodule.pl
# \brief        Package builder (tgz, zip, rpm, deb, exe)
# \author       (c)2005-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
# \contributor  (c)2017 Nicolas ZABOURI <info@inovea-conseil.com>
#----------------------------------------------------------------------------

use Cwd;
use Term::ANSIColor;

$OWNER="ldestailleur";
$GROUP="ldestailleur";


@LISTETARGET=("ZIP");   # Possible packages
%REQUIREMENTTARGET=(    # Tool requirement for each package
"TGZ"=>"tar",
"ZIP"=>"7z"
);
%ALTERNATEPATH=(
);


use vars qw/ $REVISION $VERSION /;
$REVISION='1.0';
$VERSION="3.5 (build $REVISION)";



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
    print "$PROG.$Extension was not able to detect your OS.\n";
	print "Can't continue.\n";
	print "$PROG.$Extension aborted.\n";
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
	print "$PROG.$Extension aborted.\n";
    sleep 2;
    exit 2;
}
$BUILDROOT="$TEMP/dolibarr-buildroot";


my $copyalreadydone=0;
my $batch=0;

for (0..@ARGV-1) {
	if ($ARGV[$_] =~ /^-*target=(\w+)/i)   { $target=$1; $batch=1; }
	if ($ARGV[$_] =~ /^-*desti=(.+)/i)     { $DESTI=$1; }
    if ($ARGV[$_] =~ /^-*prefix=(.+)/i)    {
    	$PREFIX=$1;
    	$FILENAMESNAPSHOT.="-".$PREFIX;
    }
}
$SOURCE="$DIR/../..";
$DESTI="$SOURCE/build";
if ($ENV{"DESTIMODULES"}) { $DESTI = $ENV{"DESTIMODULES"}; }		# Force output dir if env DESTIMODULES is defined
$NEWDESTI=$DESTI;


print "Makepack for modules version $VERSION\n";
print "Source directory: $SOURCE\n";
print "Target directory: $NEWDESTI\n";


# Ask module
print "Enter name for your module (mymodule, mywonderfulmondule, ... or 'all') : ";
$PROJECTINPUT=<STDIN>;
chomp($PROJECTINPUT);
print "Move to ".$DIR." directory.\n";
chdir($DIR);


my @PROJECTLIST=();
if ($PROJECTINPUT eq "all")
{
    opendir(DIR, $DIR) || return;
    local @rv = grep { /^makepack\-(.*)\.conf$/ } sort readdir(DIR);
    closedir(DIR);
    foreach my $xxx (0..@rv-1) {
    	if ($rv[$xxx] =~ /^makepack\-(.*)\.conf$/)
    	{
    	   @PROJECTLIST[$xxx]=$1;
    	}
    }
}
else
{
	@PROJECTLIST=($PROJECTINPUT);
}


# Loop on each projects
foreach my $PROJECT (@PROJECTLIST) {

	$PROJECTLC=lc($PROJECT);

	if (! -f "makepack-".$PROJECT.".conf")
	{
	    print "Error: can't open conf file makepack-".$PROJECT.".conf\n";
		print "\n";
		print "For help on building a module package, see web page\n";
		print "http://wiki.dolibarr.org/index.php/Module_development#Create_a_package_to_distribute_and_install_your_module\n";
		print "makepack-dolibarrmodule.pl aborted.\n";
	    sleep 2;
	    exit 2;
	}

	# Get version $MAJOR, $MINOR and $BUILD
	print "Version detected for module ".$PROJECT.": ";
	$result=open(IN,"<".$SOURCE."/htdocs/".$PROJECTLC."/core/modules/mod".$PROJECT.".class.php");
	$custom=false;
	if (! $result) {
                $result=open(IN,"<".$SOURCE."/htdocs/custom/".$PROJECTLC."/core/modules/mod".$PROJECT.".class.php");
                if (! $result) {
                    die "Error: Can't open descriptor file ".$SOURCE."/htdocs/(or /htdocs/custom/)".$PROJECTLC."/core/modules/mod".$PROJECT.".class.php for reading.\n";
                }else{
                    $custom = true;
                }
        }
    while(<IN>)
    {
    	if ($_ =~ /this->version\s*=\s*'([\d\.]+)'/) { $PROJVERSION=$1; break; }
    }
    close IN;
	print $PROJVERSION."\n";

	($MAJOR,$MINOR,$BUILD)=split(/\./,$PROJVERSION,3);
	if ($MINOR eq '')
	{
	    print "Enter value for minor version for module ".$PROJECT.": ";
	    $MINOR=<STDIN>;
	    chomp($MINOR);
	}

	$FILENAME="$PROJECTLC";
	$FILENAMETGZ="module_$PROJECTLC-$MAJOR.$MINOR".($BUILD ne ''?".$BUILD":"");
	$FILENAMEZIP="module_$PROJECTLC-$MAJOR.$MINOR".($BUILD ne ''?".$BUILD":"");
	if (-d "/usr/src/redhat") {
	    # redhat
	    $RPMDIR="/usr/src/redhat";
	}
	if (-d "/usr/src/RPM") {
	    # mandrake
	    $RPMDIR="/usr/src/RPM";
	}


	# Choose package targets
	#-----------------------
	$target="ZIP";    # Dolibarr modules are this format
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
	$nboftargetneedbuildroot=0;
	$nboftargetneedcvs=0;
	foreach my $target (keys %CHOOSEDTARGET) {
	    if ($CHOOSEDTARGET{$target} < 0) { next; }
		if ($target ne 'EXE' && $target ne 'EXEDOLIWAMP')
		{
			$nboftargetneedbuildroot++;
		}
		if ($target eq 'SNAPSHOT')
		{
			$nboftargetneedcvs++;
		}
		$nboftargetok++;
	}

	if ($nboftargetok) {

	    # Update CVS if required
	    #-----------------------
	    if ($nboftargetneedcvs)
		{
	    	print "Go to directory $SOURCE\n";
	   		$olddir=getcwd();
	   		chdir("$SOURCE");
	    	print "Run cvs update -P -d\n";
	    	$ret=`cvs update -P -d 2>&1`;
	    	chdir("$olddir");
		}

	    # Update buildroot if required
	    #-----------------------------
	    if ($nboftargetneedbuildroot)
		{
		    if (! $copyalreadydone) {
		    	print "Delete directory $BUILDROOT\n";
		    	$ret=`rm -fr "$BUILDROOT"`;

		    	mkdir "$BUILDROOT";
		    	mkdir "$BUILDROOT/$PROJECTLC";

				$result=open(IN,"<makepack-".$PROJECT.".conf");
				if (! $result) { die "Error: Can't open conf file makepack-".$PROJECT.".conf for reading.\n"; }
			    while(<IN>)
			    {
			    	$entry=$_;

			    	if ($entry =~ /^#/) { next; }	# Do not process comments

					$entry =~ s/\n//;

			    	if ($entry =~ /^!(.*)$/)		# Exclude so remove file/dir
			    	{
			    		print "Remove $BUILDROOT/$PROJECTLC/$1\n";
			    		$ret=`rm -fr "$BUILDROOT/$PROJECTLC/"$1`;
		    		    if ($? != 0) { die "Failed to delete a file to exclude declared into makepack-".$PROJECT.".conf file (Fails on line ".$entry.")\n"; }
		    		    next;
			    	}

					$entry =~ /^(.*)\/[^\/]+/;
			    	print "Create directory $BUILDROOT/$PROJECTLC/$1\n";
			    	$ret=`mkdir -p "$BUILDROOT/$PROJECTLC/$1"`;
			    	if ($entry !~ /version\-/)
			    	{
			    	    print "Copy $SOURCE/$entry into $BUILDROOT/$PROJECTLC/$entry\n";
		    		    $ret=`cp -pr "$SOURCE/$entry" "$BUILDROOT/$PROJECTLC/$entry"`;
		    		    if ($? != 0) { die "Failed to make copy of a file declared into makepack-".$PROJECT.".conf file (Fails on line ".$entry.")\n"; }
			    	}

				}
				close IN;

				@timearray=localtime(time());
				$fulldate=($timearray[5]+1900).'-'.($timearray[4]+1).'-'.$timearray[3].' '.$timearray[2].':'.$timearray[1];
				open(VF,">$BUILDROOT/$PROJECTLC/dev/build/version-".$PROJECTLC.".txt");

				print "Create version file $BUILDROOT/$PROJECTLC/dev/build/version-".$PROJECTLC.".txt with date ".$fulldate."\n";
				$ret=`mkdir -p "$BUILDROOT/$PROJECTLC/dev/build"`;
				print VF "Version: ".$MAJOR.".".$MINOR.($BUILD ne ''?".$BUILD":"")."\n";
				print VF "Build  : ".$fulldate."\n";
				close VF;
		    }
		    print "Clean $BUILDROOT\n";
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/.cache`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/.git`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/.project`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/.settings`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/index.php`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/dev/build/html`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/documents`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/document`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/htdocs/conf/conf.php.mysql`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/htdocs/conf/conf.php.old`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/htdocs/conf/conf.php.postgres`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/htdocs/conf/conf*sav*`;
		    if($custom){
                        $ret=`cp -r $BUILDROOT/$PROJECTLC/htdocs/custom/* $BUILDROOT/$PROJECTLC/htdocs/.`;
		    }
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/htdocs/custom`;
	            $ret=`rm -fr $BUILDROOT/$PROJECTLC/htdocs/custom2`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/test`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/Thumbs.db $BUILDROOT/$PROJECTLC/*/Thumbs.db $BUILDROOT/$PROJECTLC/*/*/Thumbs.db $BUILDROOT/$PROJECTLC/*/*/*/Thumbs.db $BUILDROOT/$PROJECTLC/*/*/*/*/Thumbs.db`;
		    $ret=`rm -fr $BUILDROOT/$PROJECTLC/CVS* $BUILDROOT/$PROJECTLC/*/CVS* $BUILDROOT/$PROJECTLC/*/*/CVS* $BUILDROOT/$PROJECTLC/*/*/*/CVS* $BUILDROOT/$PROJECTLC/*/*/*/*/CVS* $BUILDROOT/$PROJECTLC/*/*/*/*/*/CVS*`;
		}

	    # Build package for each target
	    #------------------------------
	    foreach my $target (keys %CHOOSEDTARGET) {
	        if ($CHOOSEDTARGET{$target} < 0) { next; }

	        print "\nBuild package for target $target\n";

	    	if ($target eq 'TGZ') {
	    		$NEWDESTI=$DESTI;
				if (-d $DESTI.'/../modules') { $NEWDESTI=$DESTI.'/../modules'; }

	    		print "Remove target $FILENAMETGZ.tgz...\n";
	    		unlink("$NEWDESTI/$FILENAMETGZ.tgz");
	    		print "Compress $BUILDROOT/* into $FILENAMETGZ.tgz...\n";
	   		    $cmd="tar --exclude-vcs --exclude *.tgz --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$FILENAMETGZ.tgz\" .";
	   		    $ret=`$cmd`;
	            if ($OS =~ /windows/i) {
	        		print "Move $FILENAMETGZ.tgz to $NEWDESTI/$FILENAMETGZ.tgz\n";
	        		$ret=`mv "$FILENAMETGZ.tgz" "$NEWDESTI/$FILENAMETGZ.tgz"`;
	            }
	            else
	            {
	        		$ret=`mv "$FILENAMETGZ.tgz" "$NEWDESTI/$FILENAMETGZ.tgz"`;
	            }
	            next;
	    	}

	    	if ($target eq 'ZIP') {
	    		$NEWDESTI=$DESTI;
				if (-d $DESTI.'/../modules') { $NEWDESTI=$DESTI.'/../modules'; }

	    		print "Remove target $FILENAMEZIP.zip...\n";
	    		unlink "$NEWDESTI/$FILENAMEZIP.zip";
	    		print "Compress $FILENAMEZIP into $FILENAMEZIP.zip...\n";

	            print "Go to directory $BUILDROOT/$PROJECTLC\n";
	     		$olddir=getcwd();
	     		chdir("$BUILDROOT/$PROJECTLC");
	    		$cmd= "7z a -r -tzip -mx $BUILDROOT/$FILENAMEZIP.zip *";
				print $cmd."\n";
				$ret= `$cmd`;
	            chdir("$olddir");

	            print "Move $FILENAMEZIP.zip to $NEWDESTI/$FILENAMEZIP.zip\n";
	            $ret=`mv "$BUILDROOT/$FILENAMEZIP.zip" "$NEWDESTI/$FILENAMEZIP.zip"`;
	            $ret=`chown $OWNER.$GROUP "$NEWDESTI/$FILENAMEZIP.zip"`;
	    		next;
	    	}

	    	if ($target eq 'EXE') {
	    		$NEWDESTI=$DESTI;
				if (-d $DESTI.'/../modules') { $NEWDESTI=$DESTI.'/../modules'; }

	    		print "Remove target $FILENAMEEXE.exe...\n";
	    		unlink "$NEWDESTI/$FILENAMEEXE.exe";
	    		print "Compress into $FILENAMEEXE.exe by $FILENAMEEXE.nsi...\n";
	    		$command="\"$REQUIREMENTTARGET{$target}\" /DMUI_VERSION_DOT=$MAJOR.$MINOR.$BUILD /X\"SetCompressor bzip2\" \"$SOURCE\\build\\exe\\$FILENAME.nsi\"";
	            print "$command\n";
	    		$ret=`$command`;
	    		print "Move $FILENAMEEXE.exe to $NEWDESTI\n";
	    		rename("$SOURCE\\build\\exe\\$FILENAMEEXE.exe","$NEWDESTI/$FILENAMEEXE.exe");
	    		next;
	    	}

	    }

	}

	print "\n----- Summary -----\n";
	foreach my $target (keys %CHOOSEDTARGET) {
	    if ($CHOOSEDTARGET{$target} < 0) {
	        print "Package $target not built (bad requirement).\n";
	    } else {
	        print "Package $target built successfully in $NEWDESTI\n";
	    }
	}


}


if (! $batch) {
    print "\nPress key to finish...";
    my $WAITKEY=<STDIN>;
}

0;
