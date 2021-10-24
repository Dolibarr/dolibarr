#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-dolibarr.pl
# \brief        Dolibarr package builder (tgz, zip, rpm, deb, exe)
# \version      $Revision$
# \author       (c)2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
#----------------------------------------------------------------------------

use Cwd;

$PROJECT="dolibarr";
$MAJOR="2";
$MINOR="8";
$BUILD="1";				# Mettre x pour release, x-dev pour dev, x-beta pour beta, x-rc pour release candidate
$RPMSUBVERSION="1";		# A incrementer au moment de la release

@LISTETARGET=("TGZ","ZIP","RPM","DEB","EXE","EXEDOLIWAMP","SNAPSHOT");   # Possible packages
%REQUIREMENTTARGET=(                            # Tool requirement for each package
"SNAPSHOT"=>"tar",
"TGZ"=>"tar",
"ZIP"=>"7z",
"RPM"=>"rpmbuild",
"DEB"=>"dpkg",
"EXE"=>"makensis.exe",
"EXEDOLIWAMP"=>"iscc.exe"
);
%ALTERNATEPATH=(
"7z"=>"7-ZIP",
"makensis.exe"=>"NSIS"
);

$FILENAME="$PROJECT";
$FILENAMESNAPSHOT="$PROJECT-snapshot";
$FILENAMETGZ="$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEZIP="$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMERPM="$PROJECT-$MAJOR.$MINOR.$BUILD-$RPMSUBVERSION";
$FILENAMEDEB="$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEEXE="$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEEXEDOLIWAMP="$PROJECT-$MAJOR.$MINOR.$BUILD";
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
$BUILDROOT="$TEMP/buildroot";


my $copyalreadydone=0;
my $batch=0;

for (0..@ARGV-1) {
	if ($ARGV[$_] =~ /^-*target=(\w+)/i)   { $target=$1; $batch=1; }
	if ($ARGV[$_] =~ /^-*desti=(.+)/i)     { $DESTI=$1; }
}

print "Makepack version $VERSION\n";
print "Building package name: $PROJECT\n";
print "Building package version: $MAJOR.$MINOR.$BUILD\n";
print "Source directory: $SOURCE\n";
print "Target directory: $DESTI\n";



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
   		chdir("$SOURCE");
    	print "Run cvs update -P -d\n";
    	$ret=`cvs update -P -d 2>&1`;
	}
	
    # Update buildroot if required
    #-----------------------------
    if ($nboftargetneedbuildroot)
	{
	    if (! $copyalreadydone) {
	    	print "Delete directory $BUILDROOT\n";
	    	$ret=`rm -fr "$BUILDROOT"`;
	    
	    	mkdir "$BUILDROOT";
	    	mkdir "$BUILDROOT/dolibarr";
	    	print "Copy $SOURCE into $BUILDROOT/dolibarr\n";
	    	$ret=`cp -pr "$SOURCE" "$BUILDROOT/dolibarr"`;
	    }
	    print "Clean $BUILDROOT\n";
	    $ret=`rm -fr $BUILDROOT/$PROJECT/.cache`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/.project`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/.settings`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/index.php`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/documents`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/document`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf.php.mysql`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf.php.old`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf.php.postgres`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/Thumbs.db $BUILDROOT/$PROJECT/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/*/Thumbs.db`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/CVS* $BUILDROOT/$PROJECT/*/CVS* $BUILDROOT/$PROJECT/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/CVS*`;
	}
    
    # Build package for each target
    #------------------------------
    foreach my $target (keys %CHOOSEDTARGET) {
        if ($CHOOSEDTARGET{$target} < 0) { next; }
    
        print "\nBuild package for target $target\n";

    	if ($target eq 'SNAPSHOT') {
    		print "Rename $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMESNAPSHOT\n";
			rename("$BUILDROOT/$PROJECT","$BUILDROOT/$FILENAMESNAPSHOT");
    		unlink("$FILENAMESNAPSHOT.tgz");

    		print "Compress $BUILDROOT into $FILENAMESNAPSHOT.tgz...\n";
   		    #$cmd="tar --exclude \"$BUILDROOT/tgz/tar_exclude.txt\" --exclude .cache --exclude .settings --exclude conf.php --directory \"$BUILDROOT\" -czvf \"$FILENAMESNAPSHOT.tgz\" $FILENAMESNAPSHOT";
   		    $cmd="tar --exclude .cache --exclude .settings --exclude conf.php --exclude conf.php.mysql --exclude conf.php.old --exclude conf.php.postgres --directory \"$BUILDROOT\" -czvf \"$FILENAMESNAPSHOT.tgz\" $FILENAMESNAPSHOT";
			print $cmd."\n";
			$ret=`$cmd`;
            if ($OS =~ /windows/i)
            {
        		print "Move $FILENAMESNAPSHOT.tgz to $DESTI/$FILENAMESNAPSHOT.tgz\n";
        		$ret=`mv "$FILENAMESNAPSHOT.tgz" "$DESTI/$FILENAMESNAPSHOT.tgz"`;
            }
            else
            {
        		print "Move $FILENAMESNAPSHOT.tgz to $DESTI/$FILENAMESNAPSHOT.tgz\n";
        		$ret=`mv "$FILENAMESNAPSHOT.tgz" "$DESTI/$FILENAMESNAPSHOT.tgz"`;
            }
    		next;
    	}

    	if ($target eq 'TGZ') {
    		print "Rename $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMETGZ\n";
			rename("$BUILDROOT/$PROJECT","$BUILDROOT/$FILENAMETGZ");
    		unlink("$FILENAMETGZ.tgz");
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
    		print "Rename $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMETGZ\n";
			rename("$BUILDROOT/$PROJECT","$BUILDROOT/$FILENAMETGZ");
    		unlink("$FILENAMEZIP.zip");
    		print "Compress $FILENAMETGZ into $FILENAMEZIP.zip...\n";
 
     		print "Go to directory $BUILDROOT\n";
     		chdir("$BUILDROOT");
 
    		$cmd= "7z a -r -tzip -xr\@\"$BUILDROOT\/$FILENAMETGZ\/build\/zip\/zip_exclude.txt\" -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMETGZ\\*";
			print $cmd."\n";
			$ret= `$cmd`;
			#print $ret;
			#print "Go to directory $DESTI\n";
     		#chdir("$DESTI");
			print "Move $FILENAMEZIP.zip to $DESTI\n";
    		rename("$BUILDROOT/$FILENAMEZIP.zip","$DESTI/$FILENAMEZIP.zip");
    		next;
    	}
    
    	if ($target eq 'RPM') {                 # Linux only
			print "RPM build is not yet available\n";
			return;
			
    		print "Rename $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMETGZ\n";
			rename("$BUILDROOT/$PROJECT","$BUILDROOT/$FILENAMETGZ");
    		unlink("$FILENAMETGZ.tgz");
    		print "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
    		$ret=`tar --exclude-from "$SOURCE/build/tgz/tar_exclude.txt" --directory "$BUILDROOT" -czvf "$BUILDROOT/$FILENAMETGZ.tgz" $FILENAMETGZ`;

    		print "Move $FILENAMETGZ.tgz to $RPMDIR/SOURCES/$FILENAMETGZ.tgz\n";
    		$cmd="mv \"$BUILDROOT/$FILENAMETGZ.tgz\" \"$RPMDIR/SOURCES/$FILENAMETGZ.tgz\"";
            $ret=`$cmd`;

    		$BUILDFIC="$FILENAME.spec";
 			print "Generate file $BUILDROOT/$BUILDFIC\n";
            open (SPECFROM,"<$SOURCE/build/rpm/${BUILDFIC}") || die "Error";
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
			
		    $ret=`rm -fr $BUILDROOT/$PROJECT/.cvsignore $BUILDROOT/$PROJECT/*/.cvsignore $BUILDROOT/$PROJECT/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/*/.cvsignore`;

    		print "Move $BUILDROOT/$PROJECT $BUILDROOT/$PROJECT.tmp\n";
    		$cmd="mv \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$PROJECT.tmp\"";
            $ret=`$cmd`;

    		print "Create directory $BUILDROOT/$PROJECT/usr/share\n";
    		$ret=`mkdir -p "$BUILDROOT/$PROJECT/usr/share"`;

    		print "Move $BUILDROOT/$PROJECT.tmp $BUILDROOT/$PROJECT/usr/share/$PROJECT\n";
    		$cmd="mv \"$BUILDROOT/$PROJECT.tmp\" \"$BUILDROOT/$PROJECT/usr/share/$PROJECT\"";
            $ret=`$cmd`;
    		
    		print "Create directory $BUILDROOT/$PROJECT/DEBIAN\n";
    		$ret=`mkdir "$BUILDROOT/$PROJECT/DEBIAN"`;
    		print "Copy $SOURCE/build/deb/* to $BUILDROOT/$PROJECT/DEBIAN\n";
    		$ret=`cp -r "$SOURCE/build/deb/." "$BUILDROOT/$PROJECT/DEBIAN"`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/DEBIAN/CVS`;
 
 			print "Remove config file\n";
		    $ret=`rm -f $BUILDROOT/$PROJECT/usr/share/$PROJECT/htdocs/conf/conf.php`;

 			print "Remove other files\n";
		    $ret=`rm -f $BUILDROOT/$PROJECT/usr/share/$PROJECT/build/DoliWamp-*`;
		    $ret=`rm -f $BUILDROOT/$PROJECT/usr/share/$PROJECT/build/DoliMamp-*`;
		    $ret=`rm -f $BUILDROOT/$PROJECT/usr/share/$PROJECT/build/dolibarr-*.tar`;
		    $ret=`rm -f $BUILDROOT/$PROJECT/usr/share/$PROJECT/build/dolibarr-*.tgz`;
		    $ret=`rm -f $BUILDROOT/$PROJECT/usr/share/$PROJECT/build/dolibarr-*.zip`;
		    $ret=`rm -f $BUILDROOT/$PROJECT/usr/share/$PROJECT/build/dolibarr-*.deb`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/usr/share/$PROJECT/doc/flyer`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/usr/share/$PROJECT/doc/font`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/usr/share/$PROJECT/doc/tshirt`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/usr/share/$PROJECT/doc/rollup`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/usr/share/$PROJECT/htdocs/conf/conf.php.mysql`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/usr/share/$PROJECT/htdocs/conf/conf.php.old`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT/usr/share/$PROJECT/htdocs/conf/conf.php.postgres`;

 			print "Edit version in file $BUILDROOT/$PROJECT/DEBIAN/control\n";
            open (SPECFROM,"<$SOURCE/build/deb/control") || die "Error";
            open (SPECTO,">$BUILDROOT/$PROJECT/DEBIAN/control") || die "Error";
            while (<SPECFROM>) {
            	$newbuild = $BUILD;
                $newbuild =~ s/dev/0/gi;
                $newbuild =~ s/alpha/0/gi;
                $newbuild =~ s/beta/1/gi;
                if ($newbuild !~ /-/) { $newbuild.='-2'; }
                $_ =~ s/__VERSION__/$MAJOR.$MINOR.$newbuild/;
                print SPECTO $_;
            }
            close SPECFROM;
            close SPECTO;

    		print "Create directory $BUILDROOT/$PROJECT/usr/share/$PROJECT/documents\n";
    		$ret=`mkdir -p "$BUILDROOT/$PROJECT/usr/share/$PROJECT/documents"`;

    		#print "Create directory $BUILDROOT/$PROJECT/etc/$PROJECT\n";
    		#$ret=`mkdir -p "$BUILDROOT/$PROJECT/etc/$PROJECT"`;

    		#print "Copy changelog file into $BUILDROOT/$PROJECT/DEBIAN\n";
    		#$ret=`cp "$SOURCE/ChangeLog" "$BUILDROOT/$PROJECT/DEBIAN/changelog"`;

    		print "Copy README file into $BUILDROOT/$PROJECT/DEBIAN\n";
    		$ret=`cp "$SOURCE/README" "$BUILDROOT/$PROJECT/DEBIAN/README"`;

    		print "Copy copyright file into $BUILDROOT/$PROJECT/DEBIAN\n";
    		$ret=`cp "$SOURCE/COPYRIGHT" "$BUILDROOT/$PROJECT/DEBIAN/copyright"`;

    		#print "Copy apache conf file into $BUILDROOT/$PROJECT/etc/$PROJECT\n";
    		#$ret=`cp "$SOURCE/build/deb/apache.conf" "$BUILDROOT/$PROJECT/etc/$PROJECT"`;

			print "Set permissions/owners on files/dir\n";
		    $ret=`chown -R root.root $BUILDROOT/$PROJECT`;
		    $ret=`chown -R www-data.www-data $BUILDROOT/$PROJECT/usr/share/$PROJECT/documents`;
		    $ret=`chmod -R 555 $BUILDROOT/$PROJECT`;
		    $ret=`chmod -R 755 $BUILDROOT/$PROJECT/usr/share/$PROJECT/documents`;
		    $ret=`chmod -R 755 $BUILDROOT/$PROJECT/DEBIAN`;

     		print "Go to directory $BUILDROOT\n";
     		chdir("$BUILDROOT");
 
    		$cmd="dpkg -b $BUILDROOT/$PROJECT $BUILDROOT/${FILENAMEDEB}.deb";
    		print "Launch DEB build ($cmd)\n";
    		$ret=`$cmd`;
    		print $ret."\n";
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
    
    	if ($target eq 'EXEDOLIWAMP') 
    	{
    		unlink "$FILENAMEEXEDOLIWAMP.exe";
    		print "Compil exe $FILENAMEEXEDOLIWAMP.exe file from iss file \"$SOURCE\\build\\exe\\doliwamp\\doliwamp.iss\"\n";
    		$cmd= "iscc.exe \"$SOURCE\\build\\exe\\doliwamp\\doliwamp.iss\"";
			print "$cmd\n";
			$ret= `$cmd`;
			#print "$ret\n";
			print "Move \"$SOURCE\\build\\$FILENAMEEXEDOLIWAMP.exe\" to $DESTI/$FILENAMEEXEDOLIWAMP.exe\n";
    		rename("$SOURCE/build/$FILENAMEEXEDOLIWAMP.exe","$DESTI/$FILENAMEEXEDOLIWAMP.exe");
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

if (! $batch) {
    print "\nPress key to finish...";
    my $WAITKEY=<STDIN>;
}

0;
