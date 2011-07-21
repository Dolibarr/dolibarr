#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-dolibarr.pl
# \brief        Dolibarr package builder (tgz, zip, rpm, deb, exe, aps)
# \version      $Id: makepack-dolibarr.pl,v 1.111 2011/07/21 01:20:31 eldy Exp $
# \author       (c)2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
#----------------------------------------------------------------------------

use Cwd;

$PROJECT="dolibarr";
$MAJOR="3";
$MINOR="1";
$BUILD="0-dev";		# Mettre x pour release, x-dev pour dev, x-beta pour beta, x-rc pour release candidate
$RPMSUBVERSION="auto";	# auto use value found into BUILD

@LISTETARGET=("TGZ","ZIP","RPM","DEB","APS","EXEDOLIWAMP","SNAPSHOT");   # Possible packages
%REQUIREMENTTARGET=(                            # Tool requirement for each package
"SNAPSHOT"=>"tar",
"TGZ"=>"tar",
"ZIP"=>"7z",
"RPM"=>"rpmbuild",
"DEB"=>"dpkg",
"APS"=>"zip",
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
$FILENAMEAPS="$PROJECT-$MAJOR.$MINOR.$BUILD.app";
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
$REVISION='$Revision: 1.111 $'; $REVISION =~ /\s(.*)\s/; $REVISION=$1;
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


my $copyalreadydone=0;      # Use - before number of choice to avoid copy
my $batch=0;

for (0..@ARGV-1) {
	if ($ARGV[$_] =~ /^-*target=(\w+)/i)   { $target=$1; $batch=1; }
	if ($ARGV[$_] =~ /^-*desti=(.+)/i)     { $DESTI=$1; }
    if ($ARGV[$_] =~ /^-*prefix=(.+)/i)    {
    	$PREFIX=$1; 
    	$FILENAMESNAPSHOT.="-".$PREFIX; 
    }
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
        $newreq=$req; $newparam='';
        if ($newreq eq 'zip') { $newparam.='-h'; }
        $cmd="\"$newreq\" $newparam 2>&1";
        print "Test command ".$cmd."... ";
        $ret=`$cmd`;
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
	    	mkdir "$BUILDROOT/$PROJECT";
	    	print "Copy $SOURCE into $BUILDROOT/$PROJECT\n";
	    	$ret=`cp -pr "$SOURCE" "$BUILDROOT/$PROJECT"`;
	    }
	    print "Clean $BUILDROOT\n";
        $ret=`rm -f  $BUILDROOT/$PROJECT/index.php`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/.cache`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/.project`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/.settings`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/build/html`;

        $ret=`rm -f  $BUILDROOT/$PROJECT/build/DoliW*-*`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/build/DoliM*-*`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.deb`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.rpm`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.tar`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.tgz`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.zip`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/build/doxygen/doxygen_warnings.log`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.mysql`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.old`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.postgres`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf*sav*`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/install/install.lock`;
	    
	    $ret=`rm -fr $BUILDROOT/$PROJECT/documents`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/document`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf.php`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf.php.mysql`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf.php.old`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf.php.postgres`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/conf/conf*sav*`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/custom`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/custom2`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/theme/bureau2crea`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/test`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/Thumbs.db $BUILDROOT/$PROJECT/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/*/Thumbs.db`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/CVS* $BUILDROOT/$PROJECT/*/CVS* $BUILDROOT/$PROJECT/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/*/*/*/CVS* $BUILDROOT/$PROJECT/*/*/*/*/*/*/*/*/*/CVS*`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/.cvsignore $BUILDROOT/$PROJECT/*/.cvsignore $BUILDROOT/$PROJECT/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/*/.cvsignore`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/php_writeexcel/php.bmp`;
	    $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/phpexcel/PHPExcel/Shared/PDF/fonts/utils/freetype6.dll`;
	    $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/phpexcel/PHPExcel/Shared/PDF/fonts/utils/zlib1.dll`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/phpexcel/PHPExcel/Shared/PDF/fonts/utils/pfm2afm`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/phpexcel/PHPExcel/Shared/PDF/fonts/utils/ttf2ufm`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/tcpdf/fonts/utils/pfm2afm`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/tcpdf/fonts/utils/ttf2ufm`;
	}
    
    # Build package for each target
    #------------------------------
    foreach my $target (keys %CHOOSEDTARGET) 
    {
        if ($CHOOSEDTARGET{$target} < 0) { next; }
    
        print "\nBuild package for target $target\n";

    	if ($target eq 'SNAPSHOT') {
    		print "Remove target $FILENAMESNAPSHOT.tgz...\n";
    		unlink("$DESTI/$FILENAMESNAPSHOT.tgz");

            #rmdir "$BUILDROOT/$FILENAMESNAPSHOT";
    		$ret=`rm -fr $BUILDROOT/$FILENAMESNAPSHOT`;
            print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMESNAPSHOT\n";
    		$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMESNAPSHOT\"";
            $ret=`$cmd`;

    		print "Compress $BUILDROOT into $FILENAMESNAPSHOT.tgz...\n";
   		    #$cmd="tar --exclude \"$BUILDROOT/tgz/tar_exclude.txt\" --exclude .cache --exclude .settings --exclude conf.php --directory \"$BUILDROOT\" -czvf \"$FILENAMESNAPSHOT.tgz\" $FILENAMESNAPSHOT";
   		    $cmd="tar --exclude doli*.tgz --exclude doli*.deb --exclude doli*.exe --exclude doli*.zip --exclude doli*.rpm --exclude .cache --exclude .settings --exclude conf.php --exclude conf.php.mysql --exclude conf.php.old --exclude conf.php.postgres --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$FILENAMESNAPSHOT.tgz\" $FILENAMESNAPSHOT";
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
    		print "Remove target $FILENAMETGZ.tgz...\n";
    		unlink("$DESTI/$FILENAMETGZ.tgz");

            #rmdir "$BUILDROOT/$FILENAMETGZ";
    		$ret=`rm -fr $BUILDROOT/$FILENAMETGZ`;
            print "Copy $BUILDROOT/$PROJECT/ to $BUILDROOT/$FILENAMETGZ\n";
    		$cmd="cp -pr \"$BUILDROOT/$PROJECT/\" \"$BUILDROOT/$FILENAMETGZ\"";
            $ret=`$cmd`;

    		print "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
   		    $cmd="tar --exclude-vcs --exclude-from \"$DESTI/tgz/tar_exclude.txt\" --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$FILENAMETGZ.tgz\" $FILENAMETGZ";
   		    $ret=`$cmd`;
            if ($OS =~ /windows/i)
            {
        		print "Move $FILENAMETGZ.tgz to $DESTI/$FILENAMETGZ.tgz\n";
        		$ret=`mv "$FILENAMETGZ.tgz" "$DESTI/$FILENAMETGZ.tgz"`;
            }
            else
            {
                print "Move $FILENAMETGZ.tgz to $DESTI/$FILENAMETGZ.tgz\n";
        		$ret=`mv "$FILENAMETGZ.tgz" "$DESTI/$FILENAMETGZ.tgz"`;
            }
    		next;
    	}

    	if ($target eq 'ZIP') {
    		print "Remove target $FILENAMEZIP.zip...\n";
    		unlink("$DESTI/$FILENAMEZIP.zip");

            #rmdir "$BUILDROOT/$FILENAMEZIP";
    		$ret=`rm -fr $BUILDROOT/$FILENAMEZIP`;
            print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMEZIP\n";
    		$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMEZIP\"";
            $ret=`$cmd`;

    		print "Compress $FILENAMEZIP into $FILENAMEZIP.zip...\n";
 
            print "Go to directory $BUILDROOT\n";
     		$olddir=getcwd();
     		chdir("$BUILDROOT");
    		$cmd= "7z a -r -tzip -xr\@\"$BUILDROOT\/$FILENAMEZIP\/build\/zip\/zip_exclude.txt\" -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMEZIP\\*";
			print $cmd."\n";
			$ret= `$cmd`;
            chdir("$olddir");
            			
            if ($OS =~ /windows/i)
            {
                print "Move $FILENAMEZIP.zip to $DESTI/$FILENAMEZIP.zip\n";
                $ret=`mv "$BUILDROOT/$FILENAMEZIP.zip" "$DESTI/$FILENAMEZIP.zip"`;
            }
            else
            {
                print "Move $FILENAMEZIP.zip to $DESTI/$FILENAMEZIP.zip\n";
                $ret=`mv "$BUILDROOT/$FILENAMEZIP.zip" "$DESTI/$FILENAMEZIP.zip"`;
            }
    		next;
    	}
    
    	if ($target eq 'RPM') {                 # Linux only
    		$ARCH='i386';
			if ($RPMDIR eq "") { $RPMDIR=$ENV{'HOME'}."/rpmbuild"; }
           	$newbuild = $BUILD;
            $newbuild =~ s/(dev|alpha)/0/gi;				# dev
            $newbuild =~ s/beta/1/gi;						# beta
            $newbuild =~ s/rc./2/gi;						# rc
            if ($newbuild !~ /-/) { $newbuild.='-3'; }		# finale
            # now newbuild is 0-0 or 0-3 for example
            $REL1 = $newbuild; $REL1 =~ s/-.*$//gi;
            if ($RPMSUBVERSION eq 'auto') { $RPMSUBVERSION = $newbuild; $RPMSUBVERSION =~ s/^.*-//gi; }
            print "Version is $MAJOR.$MINOR.$REL1-$RPMSUBVERSION\n";

            $FILENAMETGZ2="$PROJECT-$MAJOR.$MINOR.$REL1";
			
    		print "Remove target ".$FILENAMETGZ2."-".$RPMSUBVERSION.".".$ARCH.".rpm...\n";
    		unlink("$DESTI/".$FILENAMETGZ2."-".$RPMSUBVERSION.".".$ARCH.".rpm");

            #rmdir "$BUILDROOT/$FILENAMETGZ2";
    		$ret=`rm -fr $BUILDROOT/$FILENAMETGZ2`;
            print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMETGZ2\n";
    		$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMETGZ2\"";
            $ret=`$cmd`;

 			print "Remove other files\n";
            $ret=`rm -f $BUILDROOT/$FILENAMETGZ2/htdocs/includes/barcode/php-barcode/genbarcode/genbarcode`;

    		print "Compress $FILENAMETGZ2 into $FILENAMETGZ2.tgz...\n";
    		$ret=`tar --exclude-from "$SOURCE/build/tgz/tar_exclude.txt" --directory "$BUILDROOT" -czvf "$BUILDROOT/$FILENAMETGZ2.tgz" $FILENAMETGZ2`;

    		print "Move $FILENAMETGZ2.tgz to $RPMDIR/SOURCES/$FILENAMETGZ2.tgz\n";
    		rename("$BUILDROOT/$FILENAMETGZ2.tgz","$RPMDIR/SOURCES/$FILENAMETGZ2.tgz");
            $ret=`$cmd`;

    		$BUILDFIC="$FILENAME.spec";
 			print "Generate file $BUILDROOT/$BUILDFIC\n";
            open (SPECFROM,"<$SOURCE/build/rpm/${BUILDFIC}") || die "Error";
            open (SPECTO,">$BUILDROOT/$BUILDFIC") || die "Error";
            while (<SPECFROM>) {
                $_ =~ s/__FILENAMETGZ__/$FILENAMETGZ/;
                $_ =~ s/__VERSION__/$MAJOR.$MINOR.$REL1/;
                $_ =~ s/__RELEASE__/$RPMSUBVERSION/;
                print SPECTO $_;
            }
            close SPECFROM;
            close SPECTO;
    
    		print "Launch RPM build (rpmbuild --clean -ba $BUILDROOT/${BUILDFIC})\n";
    		#$ret=`rpmbuild -vvvv --clean -ba $BUILDROOT/${BUILDFIC}`;
    		$ret=`rpmbuild --clean -ba $BUILDROOT/${BUILDFIC}`;
    	
   		    print "Move $RPMDIR/RPMS/".$ARCH."/".$FILENAMETGZ2."-".$RPMSUBVERSION.".".$ARCH.".rpm into $DESTI/".$FILENAMETGZ2."-".$RPMSUBVERSION.".".$ARCH.".rpm\n";
   		    $cmd="mv \"$RPMDIR/RPMS/".$ARCH."/".$FILENAMETGZ2."-".$RPMSUBVERSION.".".$ARCH.".rpm\" \"$DESTI/".$FILENAMETGZ2."-".$RPMSUBVERSION.".".$ARCH.".rpm\"";
    		$ret=`$cmd`;
    		next;
    	}
    	
    	if ($target eq 'DEB') {
            $newbuild = $BUILD;
            $newbuild =~ s/(dev|alpha)/1/gi;                # dev
            $newbuild =~ s/beta/2/gi;                       # beta
            $newbuild =~ s/rc./3/gi;                        # rc
            if ($newbuild !~ /-/) { $newbuild.='-4'; }      # finale
            # now newbuild is 0-1 or 0-4 for example
            print "Version is $MAJOR.$MINOR.$newbuild\n";
    		
    		print "Remove target $FILENAMEDEB.deb...\n";
    		unlink("$DESTI/$FILENAMEDEB.deb");
			
            #rmdir "$BUILDROOT/$PROJECT.tmp";
    		$ret=`rm -fr $BUILDROOT/$PROJECT.tmp`;
    		print "Create directory $BUILDROOT/$PROJECT.tmp/usr/share\n";
    		$ret=`mkdir -p "$BUILDROOT/$PROJECT.tmp/usr/share"`;
    		print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT\n";
    		$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT\"";
            $ret=`$cmd`;
    		print "Create directory $BUILDROOT/$PROJECT.tmp/DEBIAN\n";
    		$ret=`mkdir "$BUILDROOT/$PROJECT.tmp/DEBIAN"`;
    		print "Copy $SOURCE/build/deb/* to $BUILDROOT/$PROJECT.tmp/DEBIAN\n";
            $ret=`cp -r "$SOURCE/build/deb/config"    "$BUILDROOT/$PROJECT.tmp/DEBIAN"`;
            $ret=`cp -r "$SOURCE/build/deb/control"   "$BUILDROOT/$PROJECT.tmp/DEBIAN"`;
            $ret=`cp -r "$SOURCE/build/deb/postinst"  "$BUILDROOT/$PROJECT.tmp/DEBIAN"`;
            $ret=`cp -r "$SOURCE/build/deb/postrm"    "$BUILDROOT/$PROJECT.tmp/DEBIAN"`;
            $ret=`cp -r "$SOURCE/build/deb/templates" "$BUILDROOT/$PROJECT.tmp/DEBIAN"`;
 
 			print "Remove other files\n";
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/dbmodel`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/fpdf`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/initdata`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/iso-normes`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/phpcheckstyle`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/phpunit`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/spec`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/uml`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev/xdebug`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/doc/flyer`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/doc/font`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/doc/tshirt`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/doc/rollup`;
		    $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/test`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/cashdesk/include/jscalendar/doc/html/CVS`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/cashdesk/include/jscalendar/skins/aqua/CVS`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/includes/ckeditor/plugins/*/dialogs/CVS`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/includes/ckeditor/plugins/*/images/CVS`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/includes/odtphp/zip/.svn`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/includes/odtphp/zip/pclzip/.svn`;
		    
            $ret=`rm -f  $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/COPYING`;
            $ret=`rm -f  $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/includes/barcode/php-barcode/genbarcode/genbarcode`;
            # To remove once stable
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/htdocs/htdocs/theme/bureau2crea`;

 			print "Edit version in file $BUILDROOT/$PROJECT.tmp/DEBIAN/control\n";
            open (SPECFROM,"<$SOURCE/build/deb/control") || die "Error";
            open (SPECTO,">$BUILDROOT/$PROJECT.tmp/DEBIAN/control") || die "Error";
            while (<SPECFROM>) {
                $_ =~ s/__VERSION__/$MAJOR.$MINOR.$newbuild/;
                print SPECTO $_;
            }
            close SPECFROM;
            close SPECTO;
			print "Version set to $MAJOR.$MINOR.$newbuild\n";
			
	   		print "Create directory $BUILDROOT/$PROJECT.tmp/usr/share/applications\n";
    		$ret=`mkdir -p "$BUILDROOT/$PROJECT.tmp/usr/share/applications"`;
    		print "Copy desktop file into $BUILDROOT/$PROJECT.tmp/usr/share/applications/dolibarr.desktop\n";
    		$ret=`cp "$SOURCE/build/deb/dolibarr.desktop" "$BUILDROOT/$PROJECT.tmp/usr/share/applications/dolibarr.desktop"`;
            
	   		print "Create directory $BUILDROOT/$PROJECT.tmp/usr/share/pixmaps\n";
    		$ret=`mkdir -p "$BUILDROOT/$PROJECT.tmp/usr/share/pixmaps"`;
    		print "Copy pixmap file into $BUILDROOT/$PROJECT.tmp/usr/share/pixmaps/dolibarr.xpm\n";
    		$ret=`cp "$SOURCE/doc/images/dolibarr.xpm" "$BUILDROOT/$PROJECT.tmp/usr/share/pixmaps/dolibarr.xpm"`;

            print "Create directory $BUILDROOT/$PROJECT.tmp/usr/share/doc/$PROJECT\n";
            $ret=`mkdir -p "$BUILDROOT/$PROJECT.tmp/usr/share/doc/$PROJECT"`;
            
    		print "Copy README file into $BUILDROOT/$PROJECT.tmp/DEBIAN\n";
            $ret=`cp "$SOURCE/README" "$BUILDROOT/$PROJECT.tmp/usr/share/doc/$PROJECT/README"`;

    		print "Copy copyright file into $BUILDROOT/$PROJECT.tmp/usr/share/doc/$PROJECT/copyright\n";
            $ret=`cp "$SOURCE/build/deb/copyright" "$BUILDROOT/$PROJECT.tmp/usr/share/doc/$PROJECT/copyright"`;

            #$ret=`gzip -9 -c $SOURCE/build/deb/changelog > $BUILDROOT/$PROJECT.tmp/usr/share/doc/$PROJECT/changelog.Debian.gz`;
            $ret=`gzip -9 -c "$SOURCE/build/deb/changelog" > $BUILDROOT/$PROJECT.tmp/usr/share/doc/$PROJECT/changelog.Debian.gz`;

            print "Set owners on files/dir\n";
		    $ret=`chown -R root.root $BUILDROOT/$PROJECT.tmp`;

            print "Set permissions on files/dir\n";
		    $ret=`chmod -R 755 $BUILDROOT/$PROJECT.tmp`;
		    $cmd="find $BUILDROOT/$PROJECT.tmp -type f -exec chmod 644 {} \\; ";
            $ret=`$cmd`;
            $cmd="find $BUILDROOT/$PROJECT.tmp/DEBIAN -type f -exec chmod 755 {} \\; ";
            $ret=`$cmd`;
            $ret=`chmod 644 $BUILDROOT/$PROJECT.tmp/DEBIAN/control`;
            $ret=`chmod 644 $BUILDROOT/$PROJECT.tmp/DEBIAN/templates`;
            $cmd="find $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/build -name *.php -type f -exec chmod 755 {} \\; ";
            $ret=`$cmd`;
            #$cmd="find $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/build -name *.pl -type f -exec chmod 755 {} \\; ";
            #$ret=`$cmd`;
            $cmd="find $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/dev -name *.php -type f -exec chmod 755 {} \\; ";
            $ret=`$cmd`;
            $cmd="find $BUILDROOT/$PROJECT.tmp/usr/share/$PROJECT/scripts -name *.php -type f -exec chmod 755 {} \\; ";
            $ret=`$cmd`;
            
     		print "Go to directory $BUILDROOT\n";
            $olddir=getcwd();
     		chdir("$BUILDROOT");
 
    		$cmd="dpkg -b $BUILDROOT/$PROJECT.tmp $BUILDROOT/${FILENAMEDEB}.deb";
    		print "Launch DEB build ($cmd)\n";
    		$ret=`$cmd`;
    		print $ret."\n";
            chdir("$olddir");
    		
            if ($OS =~ /windows/i)
            {
                print "Move ${FILENAMEDEB}.deb to $DESTI/${FILENAMEDEB}.deb\n";
                $ret=`mv "$BUILDROOT/${FILENAMEDEB}.deb" "$DESTI/${FILENAMEDEB}.deb"`;
            }
            else
            {
                print "Move ${FILENAMEDEB}.deb to $DESTI/${FILENAMEDEB}.deb\n";
                $ret=`mv "$BUILDROOT/${FILENAMEDEB}.deb" "$DESTI/${FILENAMEDEB}.deb"`;
            }
        	next;
        }
        
    	if ($target eq 'APS') {
            $newbuild = $BUILD;
            $newbuild =~ s/(dev|alpha)/0/gi;                # dev
            $newbuild =~ s/beta/1/gi;                       # beta
            $newbuild =~ s/rc./2/gi;                        # rc
            if ($newbuild !~ /-/) { $newbuild.='-3'; }      # finale
            # now newbuild is 0-0 or 0-3 for example
            $REL1 = $newbuild; $REL1 =~ s/-.*$//gi;
            if ($RPMSUBVERSION eq 'auto') { $RPMSUBVERSION = $newbuild; $RPMSUBVERSION =~ s/^.*-//gi; }
            print "Version is $MAJOR.$MINOR.$REL1-$RPMSUBVERSION\n";
    		
     		print "Remove target $FILENAMEAPS.zip...\n";
    		unlink "$DESTI/$FILENAMEAPS.zip";
 
            #rmdir "$BUILDROOT/$PROJECT.tmp";
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp`;
            print "Create directory $BUILDROOT/$PROJECT.tmp\n";
            $ret=`mkdir -p "$BUILDROOT/$PROJECT.tmp"`;
            print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$PROJECT.tmp\n";
            $cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$PROJECT.tmp\"";
            $ret=`$cmd`;

            print "Remove other files\n";
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/dbmodel`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/fpdf`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/initdata`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/iso-normes`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/phpcheckstyle`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/phpunit`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/spec`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/uml`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev/xdebug`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/doc/flyer`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/doc/font`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/doc/tshirt`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/doc/rollup`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/test`;

            $APSVERSION="1.1";
            print "Create APS files $BUILDROOT/$PROJECT.tmp/$PROJECT/APP-META.xml\n";
            open (SPECFROM,"<$BUILDROOT/$PROJECT/build/aps/APP-META-$APSVERSION.xml") || die "Error";
            open (SPECTO,">$BUILDROOT/$PROJECT.tmp/$PROJECT/APP-META.xml") || die "Error";
            while (<SPECFROM>) {
                $newbuild = $BUILD;
                $newbuild =~ s/(dev|alpha)/0/gi;                # dev
                $newbuild =~ s/beta/1/gi;                       # beta
                $newbuild =~ s/rc./2/gi;                        # rc
                if ($newbuild !~ /-/) { $newbuild.='-3'; }      # finale
                # now newbuild is 0-0 or 0-3 for example
                $_ =~ s/__VERSION__/$MAJOR.$MINOR.$REL1/;
                $_ =~ s/__RELEASE__/$RPMSUBVERSION/;
                print SPECTO $_;
            }
            close SPECFROM;
            close SPECTO;
            print "Version set to $MAJOR.$MINOR.$newbuild\n";
            #$cmd="cp -pr \"$BUILDROOT/$PROJECT/build/aps/configure\" \"$BUILDROOT/$PROJECT.tmp/$PROJECT/scripts/configure\"";
            #$ret=`$cmd`;
            $cmd="cp -pr \"$BUILDROOT/$PROJECT/build/aps/configure.php\" \"$BUILDROOT/$PROJECT.tmp/$PROJECT/scripts/configure.php\"";
            $ret=`$cmd`;
            $cmd="cp -pr \"$BUILDROOT/$PROJECT/doc/images\" \"$BUILDROOT/$PROJECT.tmp/$PROJECT/images\"";
            $ret=`$cmd`;
 
            print "Remove other files\n";
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/dev`;
            $ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/doc`;
            
            print "Build APP-LIST.xml files\n";
            
            print "Compress $BUILDROOT/$PROJECT.tmp/$PROJECT into $FILENAMEAPS.zip...\n";
 
            print "Go to directory $BUILDROOT/$PROJECT.tmp\/$PROJECT\n";
            $olddir=getcwd();
            chdir("$BUILDROOT\/$PROJECT.tmp\/$PROJECT");
            $cmd= "zip -9 -r $BUILDROOT/$FILENAMEAPS.zip \*";
            print $cmd."\n";
            $ret= `$cmd`;
            chdir("$olddir");
                        
            if ($OS =~ /windows/i)
            {
                print "Move $BUILDROOT/$FILENAMEAPS.zip to $DESTI/$FILENAMEAPS.zip\n";
                $ret=`mv "$BUILDROOT/$FILENAMEAPS.zip" "$DESTI/$FILENAMEAPS.zip"`;
            }
            else
            {
                print "Move $BUILDROOT/$FILENAMEAPS.zip to $DESTI/$FILENAMEAPS.zip\n";
                $ret=`mv "$BUILDROOT/$FILENAMEAPS.zip" "$DESTI/$FILENAMEAPS.zip"`;
            }
            next;
    	}

    	if ($target eq 'EXEDOLIWAMP')
    	{
     		print "Remove target $FILENAMEEXEDOLIWAMP.exe...\n";
    		unlink "$DESTI/$FILENAMEEXEDOLIWAMP.exe";
 
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
