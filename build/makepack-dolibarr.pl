#!/usr/bin/perl
#----------------------------------------------------------------------------
# \file         build/makepack-dolibarr.pl
# \brief        Dolibarr package builder (tgz, zip, rpm, deb, exe, aps)
# \author       (c)2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
#
# This is list of constant you can set to have generated packages moved into a specific dir: 
#DESTIBETARC='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/lastbuild'
#DESTISTABLE='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/stable'
#DESTIMODULES='/media/HDDATA1_LD/Mes Sites/Web/Admin1/wwwroot/files/modules'
#DESTIDOLIMEDBETARC='/media/HDDATA1_LD/Mes Sites/Web/DoliCloud/dolimed.com/htdocs/files/lastbuild'
#DESTIDOLIMEDMODULES='/media/HDDATA1_LD/Mes Sites/Web/DoliCloud/dolimed.com/htdocs/files/modules'
#DESTIDOLIMEDSTABLE='/media/HDDATA1_LD/Mes Sites/Web/DoliCloud/dolimed.com/htdocs/files/stable'
#----------------------------------------------------------------------------

use Cwd;
use Term::ANSIColor;

# Change this to defined target for option 98 and 99
$PROJECT="dolibarr";
$PUBLISHSTABLE="eldy,dolibarr\@frs.sourceforge.net:/home/frs/project/dolibarr";
$PUBLISHBETARC="dolibarr\@vmprod1.dolibarr.org:/home/dolibarr/dolibarr.org/httpdocs/files";


#@LISTETARGET=("TGZ","ZIP","RPM_GENERIC","RPM_FEDORA","RPM_MANDRIVA","RPM_OPENSUSE","DEB","EXEDOLIWAMP","SNAPSHOT");   # Possible packages
@LISTETARGET=("TGZ","ZIP","RPM_GENERIC","RPM_FEDORA","RPM_MANDRIVA","RPM_OPENSUSE","DEB","EXEDOLIWAMP","SNAPSHOT");   # Possible packages
%REQUIREMENTPUBLISH=(
"SF"=>"git ssh rsync",
"ASSO"=>"git ssh rsync"
);
%REQUIREMENTTARGET=(                            # Tool requirement for each package
"TGZ"=>"tar",
"ZIP"=>"7z",
"XZ"=>"xz",
"RPM_GENERIC"=>"rpmbuild",
"RPM_FEDORA"=>"rpmbuild",
"RPM_MANDRIVA"=>"rpmbuild",
"RPM_OPENSUSE"=>"rpmbuild",
"DEB"=>"dpkg dpatch",
"FLATPACK"=>"flatpack",
"EXEDOLIWAMP"=>"ISCC.exe",
"SNAPSHOT"=>"tar"
);
%ALTERNATEPATH=(
"7z"=>"7-ZIP",
"makensis.exe"=>"NSIS"
);

$RPMSUBVERSION="auto";	# auto use value found into BUILD
if (-d "/usr/src/redhat")   { $RPMDIR="/usr/src/redhat"; } # redhat
if (-d "/usr/src/packages") { $RPMDIR="/usr/src/packages"; } # opensuse
if (-d "/usr/src/RPM")      { $RPMDIR="/usr/src/RPM"; } # mandrake


use vars qw/ $REVISION $VERSION /;
$VERSION="4.0";



#------------------------------------------------------------------------------
# MAIN
#------------------------------------------------------------------------------
($DIR=$0) =~ s/([^\/\\]+)$//; ($PROG=$1) =~ s/\.([^\.]*)$//; $Extension=$1;
$DIR||='.'; $DIR =~ s/([^\/\\])[\\\/]+$/$1/;

$SOURCE="$DIR/..";
$DESTI="$SOURCE/build";
if ($SOURCE !~ /^\//)
{
	print "Error: Launch the script $PROG.$Extension with its full path from /.\n";
	print "$PROG.$Extension aborted.\n";
	sleep 2;
	exit 1;
}
if (! $ENV{"DESTIBETARC"} || ! $ENV{"DESTISTABLE"})
{
	print "Error: Missing environment variables.\n";
	print "You must define the environment variable DESTIBETARC and DESTISTABLE to point to the\ndirectories where you want to save the generated packages.\n";
	print "Example: DESTIBETARC='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/lastbuild'\n";
	print "Example: DESTISTABLE='/media/HDDATA1_LD/Mes Sites/Web/Dolibarr/dolibarr.org/files/stable'\n";
	print "$PROG.$Extension aborted.\n";
	sleep 2;
	exit 1;
}
if (! -d $ENV{"DESTIBETARC"} || ! -d $ENV{"DESTISTABLE"})
{
	print "Error: Directory of environment variable DESTIBETARC or DESTISTABLE does not exist.\n";
	print "$PROG.$Extension aborted.\n";
	sleep 2;
	exit 1;
}

# Detect OS type
# --------------
if ("$^O" =~ /linux/i || (-d "/etc" && -d "/var" && "$^O" !~ /cygwin/i)) { $OS='linux'; $CR=''; }
elsif (-d "/etc" && -d "/Users") { $OS='macosx'; $CR=''; }
elsif ("$^O" =~ /cygwin/i || "$^O" =~ /win32/i) { $OS='windows'; $CR="\r"; }
if (! $OS) {
	print "Error: Can't detect your OS.\n";
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


# Get version $MAJOR, $MINOR and $BUILD
$result = open( IN, "<" . $SOURCE . "/htdocs/filefunc.inc.php" );
if ( !$result ) { die "Error: Can't open descriptor file " . $SOURCE . "/htdocs/filefunc.inc.php\n"; }
while (<IN>) {
	if ( $_ =~ /define\('DOL_VERSION','([\d\.a-z\-]+)'\)/ ) { $PROJVERSION = $1; break; }
}
close IN;
($MAJOR,$MINOR,$BUILD)=split(/\./,$PROJVERSION,3);
if ($MINOR eq '') { die "Error can't detect version into ".$SOURCE . "/htdocs/filefunc.inc.php"; }

# Set vars for packaging
$FILENAME            = "$PROJECT";
$FILENAMESNAPSHOT    = "$PROJECT-snapshot";
$FILENAMETGZ         = "$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEZIP         = "$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEXZ          = "$PROJECT-$MAJOR.$MINOR.$BUILD";
$FILENAMEDEB         = "see later";
$FILENAMEEXEDOLIWAMP = "DoliWamp-$MAJOR.$MINOR.$BUILD";
# For RPM
$ARCH='noarch';
$newbuild = $BUILD;
$newbuild =~ s/(dev|alpha)/0.1.a/gi;			# dev (fedora)
$newbuild =~ s/beta(.?)/0.2.beta/gi;			# beta (fedora)    (we want beta1, beta2, betax to be same package name)
$newbuild =~ s/rc(.?)/0.3.rc/gi;				# rc (fedora)      (we want rc1, rc2, rcx to be same package name)
if ($newbuild !~ /-/) { $newbuild.='-0.4'; }	# finale (fedora)
#$newbuild =~ s/(dev|alpha)/0/gi;				# dev
#$newbuild =~ s/beta/1/gi;						# beta
#$newbuild =~ s/rc./2/gi;						# rc
#if ($newbuild !~ /-/) { $newbuild.='-3'; }		# finale
$REL1 = $newbuild; $REL1 =~ s/-.*$//gi;
if ($RPMSUBVERSION eq 'auto') { $RPMSUBVERSION = $newbuild; $RPMSUBVERSION =~ s/^.*-//gi; }
$FILENAMETGZ2="$PROJECT-$MAJOR.$MINOR.$REL1";
$FILENAMERPM=$FILENAMETGZ2."-".$RPMSUBVERSION.".".$ARCH.".rpm";
$FILENAMERPMSRC=$FILENAMETGZ2."-".$RPMSUBVERSION.".src.rpm";
# For Deb
$newbuild = $BUILD;
$newbuild =~ s/(dev|alpha)/1/gi;                # dev
$newbuild =~ s/beta(.?)/2/gi;                   # beta    			(we want beta1, beta2, betax to be same package name)
$newbuild =~ s/rc(.?)/3/gi;                     # rc				(we want rc1, rc2, rcx to be same package name)
if ($newbuild !~ /-/) { $newbuild.='-4'; }      # finale is same than rc. 
# now newbuild is 0-1 or 0-4 for example. Note that for native package (see debian/source/format), we should not use a dash part but to get a better version management
$build = $newbuild;
$build =~ s/-.*$//g;
# now build is 0 for example
# $build .= '+nmu1';
# now build is 0+nmu1 for example
$FILENAMEDEBNATIVE="${PROJECT}_${MAJOR}.${MINOR}.${build}";
$FILENAMEDEB="${PROJECT}_${MAJOR}.${MINOR}.${newbuild}";
$FILENAMEDEBSHORT="${PROJECT}_${MAJOR}.${MINOR}.${build}";


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
if ($ENV{"DESTIBETARC"} && $BUILD =~ /[a-z]/i)   { $DESTI = $ENV{"DESTIBETARC"}; }	# Force output dir if env DESTIBETARC is defined
if ($ENV{"DESTISTABLE"} && $BUILD =~ /^[0-9]+$/) { $DESTI = $ENV{"DESTISTABLE"}; }	# Force output dir if env DESTISTABLE is defined
if ($ENV{"PUBLISHBETARC"} && $BUILD =~ /[a-z]/i)   { $PUBLISHBETARC = $ENV{"PUBLISHBETARC"}; }	# Force target site for publishing if env PUBLISHBETARC is defined
if ($ENV{"PUBLISHSTABLE"} && $BUILD =~ /^[0-9]+$/) { $PUBLISHSTABLE = $ENV{"PUBLISHSTABLE"}; }	# Force target site for publishing if env PUBLISHSTABLE is defined

print "Makepack version $VERSION\n";
print "Building/publishing package name: $PROJECT\n";
print "Building/publishing package version: $MAJOR.$MINOR.$BUILD\n";
print "Source directory (SOURCE): $SOURCE\n";
print "Target directory (DESTI) : $DESTI\n";
#print "Publishing target (PUBLISH): $PUBLISH\n";


# Choose package targets
#-----------------------
if ($target) {
	if ($target eq "ALL") { 
		foreach my $key (@LISTETARGET) {
			if ($key ne 'SNAPSHOT' && $key ne 'SF' && $key ne 'ASSO') { $CHOOSEDTARGET{$key}=1; }
		}
	}
	if ($target ne "ALL" && $target ne "SF" && $target ne "ASSO") { $CHOOSEDTARGET{uc($target)}=1; }
	if ($target eq "SF") { $CHOOSEDPUBLISH{"SF"}=1; }
	if ($target eq "ASSO") { $CHOOSEDPUBLISH{"ASSO"}=1; }
}
else {
	my $found=0;
	my $NUM_SCRIPT;
	my $cpt=0;
	while (! $found) {
		$cpt=0;
		printf(" %2d - %-14s  (%s)\n",$cpt,"ALL (1..10)","Need ".join(",",values %REQUIREMENTTARGET));
		$cpt++;
		printf(" %2d - %-14s\n",$cpt,"Generate check file");
		foreach my $target (@LISTETARGET) {
			$cpt++;
			printf(" %2d - %-14s  (%s)\n",$cpt,$target,"Need ".$REQUIREMENTTARGET{$target});
		}
		$cpt=98;
		printf(" %2d - %-14s  (%s)\n",$cpt,"ASSO (publish)","Need ".$REQUIREMENTPUBLISH{"ASSO"});
		$cpt=99;
		printf(" %2d - %-14s  (%s)\n",$cpt,"SF (publish)","Need ".$REQUIREMENTPUBLISH{"SF"});
	
		# Ask which target to build
		print "Choose one target number or several separated with space (0 - ".$cpt."): ";
		$NUM_SCRIPT=<STDIN>; 
		chomp($NUM_SCRIPT);
		if ($NUM_SCRIPT !~ /^[0-9\s]+$/)
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
	if ($NUM_SCRIPT eq "98") {
		$CHOOSEDPUBLISH{"ASSO"}=1;
	}
	elsif ($NUM_SCRIPT eq "99") {
		$CHOOSEDPUBLISH{"SF"}=1;
	}
	elsif ($NUM_SCRIPT eq "0") {
		$CHOOSEDTARGET{"-CHKSUM"}=1;
		foreach my $key (@LISTETARGET) {
			if ($key ne 'SNAPSHOT' && $key ne 'ASSO' && $key ne 'SF') { $CHOOSEDTARGET{$key}=1; }
		}
	}
	elsif ($NUM_SCRIPT eq "1") {
		$CHOOSEDTARGET{"-CHKSUM"}=1
	}
	else {
		foreach my $num (split(/\s+/,$NUM_SCRIPT)) {
			$CHOOSEDTARGET{$LISTETARGET[$num-2]}=1;
		}
	}
}


# Test if requirement is ok
#--------------------------
$atleastonerpm=0;
foreach my $target (sort keys %CHOOSEDTARGET) {
	if ($target =~ /RPM/i)
	{
		if ($atleastonerpm && ($DESTI eq "$SOURCE/build"))
		{
			print "Error: You asked creation of several rpms. Because all rpm have same name, you must defined an environment variable DESTI to tell packager where it can create subdirs for each generated package.\n";
			exit;
		}
		$atleastonerpm=1;			
	} 
	foreach my $req (split(/[,\s]/,$REQUIREMENTTARGET{$target})) 
	{
		# Test    
		print "Test requirement for target $target: Search '$req'... ";
		$newreq=$req; $newparam='';
		if ($newreq eq 'zip') { $newparam.='-h'; }
		if ($newreq eq 'xz') { $newparam.='-h'; }
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
			print " Found ".$req."\n";
		}
	}
}

print "\n";


#print join(',',sort keys %CHOOSEDTARGET)."\n";

# Check if there is at least one target to build
#----------------------------------------------
$nboftargetok=0;
$nboftargetneedbuildroot=0;
$nbofpublishneedtag=0;
$nbofpublishneedchangelog=0;

foreach my $target (sort keys %CHOOSEDTARGET) {
	if ($target eq '-CHKSUM') { $nbofpublishneedchangelog++; }
	if ($CHOOSEDTARGET{$target} < 0) { next; }
	if ($target ne 'EXE' && $target ne 'EXEDOLIWAMP' && $target ne '-CHKSUM') 
	{
		$nboftargetneedbuildroot++;
	}
	$nboftargetok++;
}
foreach my $target (sort keys %CHOOSEDPUBLISH) {
	if ($CHOOSEDPUBLISH{$target} < 0) { next; }
	if ($target eq 'ASSO') { $nbofpublishneedchangelog++; }
	if ($target eq 'SF') { $nbofpublishneedchangelog++; $nbofpublishneedtag++; }
	$nboftargetok++;
}


if ($nboftargetok) {

	# Check Changelog
	#----------------
	if ($nbofpublishneedchangelog)
	{
		# Test that the ChangeLog is ok
		$TMPBUILDTOCHECKCHANGELOG=$BUILD;
		$TMPBUILDTOCHECKCHANGELOG =~ s/\-rc\d*//;
		$TMPBUILDTOCHECKCHANGELOG =~ s/\-beta\d*//;
		print "\nCheck if ChangeLog is ok for version $MAJOR.$MINOR\.$TMPBUILDTOCHECKCHANGELOG\n";
		$ret=`grep "ChangeLog for $MAJOR.$MINOR\.$TMPBUILDTOCHECKCHANGELOG" "$SOURCE/ChangeLog" 2>&1`;
		if (! $ret)
		{
			print color("yellow"), "Error: The ChangeLogFile was not updated. Run the following command before building package for $MAJOR.$MINOR.$BUILD:\n", color('reset');
		}
		else
		{
			print "ChangeLog for $MAJOR.$MINOR\.$BUILD was found into '$SOURCE/ChangeLog. But you can regenerate it with command:\n";
		}
		if (! $BUILD || $BUILD eq '0-rc')	# For a major version
		{
			print 'cd ~/git/dolibarr_'.$MAJOR.'.'.$MINOR.'; git log `git rev-list --boundary '.$MAJOR.'.'.$MINOR.'..origin/develop | grep ^- | cut -c2- | head -n 1`.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e \'^FIX\|NEW\|CLOSE\' | sort -u | sed \'s/FIXED:/FIX:/g\' | sed \'s/FIXED :/FIX:/g\' | sed \'s/FIX :/FIX:/g\' | sed \'s/FIX /FIX: /g\' | sed \'s/NEW :/NEW:/g\' | sed \'s/NEW /NEW: /g\' > /tmp/aaa';
		}
		else			# For a maintenance release
		{
			#print 'cd ~/git/dolibarr_'.$MAJOR.'.'.$MINOR.'; git log '.$MAJOR.'.'.$MINOR.'.'.($BUILD-1).'.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e \'^FIX\|NEW\' | sort -u | sed \'s/FIXED:/FIX:/g\' | sed \'s/FIXED :/FIX:/g\' | sed \'s/FIX :/FIX:/g\' | sed \'s/FIX /FIX: /g\' | sed \'s/NEW :/NEW:/g\' | sed \'s/NEW /NEW: /g\' > /tmp/aaa';
			print 'cd ~/git/dolibarr_'.$MAJOR.'.'.$MINOR.'; git log '.$MAJOR.'.'.$MINOR.'.'.($BUILD-1).'.. | grep -v "Merge branch" | grep -v "Merge pull" | grep "^ " | sed -e "s/^[0-9a-z]* *//" | grep -e \'^FIX\|NEW\|CLOSE\' | sort -u | sed \'s/FIXED:/FIX:/g\' | sed \'s/FIXED :/FIX:/g\' | sed \'s/FIX :/FIX:/g\' | sed \'s/FIX /FIX: /g\' | sed \'s/NEW :/NEW:/g\' | sed \'s/NEW /NEW: /g\' > /tmp/aaa';
			
		}
		print "\n";
		if (! $ret)
		{
			print "\nPress F to force and continue anyway (or other key to stop)... ";
			my $WAITKEY=<STDIN>;
			chomp($WAITKEY);
			if ($WAITKEY ne 'F')
			{
				print "Canceled.\n";
				exit;
			}
		}
	}
		
	# Build xml check file
	#-----------------------
	if ($CHOOSEDTARGET{'-CHKSUM'})
	{
		chdir("$SOURCE");
	   	print 'Create xml check file with md5 checksum with command php '.$SOURCE.'/build/generate_filelist_xml.php release='.$MAJOR.'.'.$MINOR.'.'.$BUILD."\n";
	  	$ret=`php $SOURCE/build/generate_filelist_xml.php release=$MAJOR.$MINOR.$BUILD`;
	  	print $ret."\n";
	  	# Copy to final dir
	  	$NEWDESTI=$DESTI;
		print "Copy \"$SOURCE/htdocs/install/filelist-$MAJOR.$MINOR.$BUILD.xml\" to $NEWDESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml\n";
	    use File::Copy qw(copy);
	    copy "$SOURCE/htdocs/install/filelist-$MAJOR.$MINOR.$BUILD.xml", "$NEWDESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml";
	}

	# Update GIT tag if required
	#---------------------------
	if ($nbofpublishneedtag)
	{
		print "Go to directory $SOURCE\n";
		$olddir=getcwd();
		chdir("$SOURCE");
		
		print 'Run git tag -a -m "'.$MAJOR.'.'.$MINOR.'.'.$BUILD.'" "'.$MAJOR.'.'.$MINOR.'.'.$BUILD.'"'."\n";
		$ret=`git tag -a -m "$MAJOR.$MINOR.$BUILD" "$MAJOR.$MINOR.$BUILD" 2>&1`;
		if ($ret =~ /(already exists|existe déjà)/)
		{
			print "WARNING: Tag ".$MAJOR.'.'.$MINOR.'.'.$BUILD." already exists. Overwrite (y/N) ? ";
			$QUESTIONOVERWRITETAG=<STDIN>; 
			chomp($QUESTIONOVERWRITETAG);
			if ($QUESTIONOVERWRITETAG =~ /(o|y)/)
			{
				print 'Run git tag -a -f -m "'.$MAJOR.'.'.$MINOR.'.'.$BUILD.'" "'.$MAJOR.'.'.$MINOR.'.'.$BUILD.'"'."\n";
				$ret=`git tag -a -f -m "$MAJOR.$MINOR.$BUILD" "$MAJOR.$MINOR.$BUILD"`;
				print 'Run git push -f --tags'."\n";
				$ret=`git push -f --tags`;
			}
		}
		else
		{
			print 'Run git push --tags'."\n";
			$ret=`git push --tags`;
		}
		chdir("$olddir");
	}
	
	# Update buildroot if required
	#-----------------------------
	if ($nboftargetneedbuildroot)
	{
		if (! $copyalreadydone) {
			print "Creation of a buildroot used for all packages\n";

			print "Delete directory $BUILDROOT\n";
			$ret=`rm -fr "$BUILDROOT"`;
		
			mkdir "$BUILDROOT";
			mkdir "$BUILDROOT/$PROJECT";
			print "Copy $SOURCE into $BUILDROOT/$PROJECT\n";
			$ret=`cp -pr "$SOURCE" "$BUILDROOT/$PROJECT"`;

			#print "Copy $SOURCE/build/debian/apache/.htaccess into $BUILDROOT/$PROJECT/build/debian/apache/.htaccess\n";
			#$ret=`cp -pr "$SOURCE/build/debian/apache/.htaccess" "$BUILDROOT/$PROJECT/build/debian/apache/.htaccess"`;
		}
		print "Clean $BUILDROOT\n";
		$ret=`rm -f  $BUILDROOT/$PROJECT/.buildpath`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.cache`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.editorconfig`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.externalToolBuilders`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.git*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.project`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.settings`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.scrutinizer.yml`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.travis.yml`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/.tx`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build.xml`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/pom.xml`;
		
		$ret=`rm -fr $BUILDROOT/$PROJECT/build/html`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/Doli*-*`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr_*.deb`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr_*.dsc`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr_*.tar.gz`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr_*.tar.xz`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.deb`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.rpm`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.tar`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.tar.gz`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.tar.xz`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.tgz`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.xz`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/dolibarr-*.zip`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/build/doxygen/doxygen_warnings.log`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/cache.manifest`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.mysql`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.old`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf.php.postgres`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/conf/conf*sav*`;

		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/install/mssql/README`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/install/mysql/README`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/install/pgsql/README`;

		$ret=`rm -fr  $BUILDROOT/$PROJECT/htdocs/install/mssql`;

		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/ansible`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/codesniffer`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/codetemplates`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/dbmodel`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/initdata`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/initdemo`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/iso-normes`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/ldap`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/licence`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/mail`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/multitail`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/phpcheckstyle`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/phpunit`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/security`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/spec`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/test`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/uml`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/vagrant`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/dev/xdebug`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/dev/dolibarr_changes.txt`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/dev/README`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot2.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot3.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot4.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot5.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot6.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot7.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot8.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot9.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot10.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot11.png`;
		$ret=`rm -f  $BUILDROOT/$PROJECT/doc/images/dolibarr_screenshot12.png`;

		# Security to avoid to package data files 
        print "Remove documents dir\n";
		$ret=`rm -fr $BUILDROOT/$PROJECT/document`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/documents`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/document`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/documents`;
        
        print "Remove subdir of custom dir\n";
   	    print "find $BUILDROOT/$PROJECT/htdocs/custom/* -type d -exec rm -fr {} \\;\n";
   	    $ret=`find $BUILDROOT/$PROJECT/htdocs/custom/* -type d -exec rm -fr {} \\; >/dev/null 2>&1`;	# For custom we want to remove all subdirs but not files
   	    print "find $BUILDROOT/$PROJECT/htdocs/custom/* -type l -exec rm -fr {} \\;\n";
   	    $ret=`find $BUILDROOT/$PROJECT/htdocs/custom/* -type l -exec rm -fr {} \\; >/dev/null 2>&1`;	# For custom we want to remove all subdirs, even symbolic links, but not files

		# Removed known external modules to avoid any error when packaging from env where external modules are tested 
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/abricot*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/accountingexport*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/allscreens*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/ancotec*`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/cabinetmed*`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/calling*`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/bootstrap*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/dolimed*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/dolimod*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/factory*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/forceproject*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/lead*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/management*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/multicompany*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/ndf*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/nltechno*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/nomenclature*`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/of/`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/oscim*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/pos*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/teclib*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/timesheet*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/webmail*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/workstation*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/accountingexport*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/themes/oblyon*`;
		$ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/themes/allscreen*`;
		# Removed other test files
	    $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/public/test`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/test`;
	    $ret=`rm -fr $BUILDROOT/$PROJECT/Thumbs.db $BUILDROOT/$PROJECT/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/Thumbs.db $BUILDROOT/$PROJECT/*/*/*/*/Thumbs.db`;
	    $ret=`rm -f  $BUILDROOT/$PROJECT/.cvsignore $BUILDROOT/$PROJECT/*/.cvsignore $BUILDROOT/$PROJECT/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/.cvsignore $BUILDROOT/$PROJECT/*/*/*/*/*/*/.cvsignore`;
	    $ret=`rm -f  $BUILDROOT/$PROJECT/.gitignore $BUILDROOT/$PROJECT/*/.gitignore $BUILDROOT/$PROJECT/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/*/*/.gitignore $BUILDROOT/$PROJECT/*/*/*/*/*/*/.gitignore`;
   	    $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/geoip/sample*.*`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/ckeditor/ckeditor/adapters`;		# Keep this removal in case we embed libraries
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/ckeditor/ckeditor/samples`;		# Keep this removal in case we embed libraries
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/ckeditor/_source`;					# _source must be kept into tarball for official debian, not for the rest
   	    
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/jquery/plugins/multiselect/MIT-LICENSE.txt`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/jquery/plugins/select2/release.sh`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/mike42/escpos-php/doc`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/mike42/escpos-php/example`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/mike42/escpos-php/test`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/mobiledetect/mobiledetectlib/.gitmodules`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nusoap/lib/Mail`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/nusoap/samples`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/parsedown/LICENSE.txt`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/php-iban/docs`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/phpoffice/phpexcel/.gitattributes`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/phpoffice/phpexcel/Classes/license.md`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/phpoffice/phpexcel/Classes/PHPExcel/Shared/PDF`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/phpoffice/phpexcel/Classes/PHPExcel/Shared/PCLZip`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/phpoffice/phpexcel/Examples`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/phpoffice/phpexcel/unitTests`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/phpoffice/phpexcel/license.md`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/sabre/sabre/*/tests`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/stripe/tests`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/stripe/LICENSE`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tcpdf/fonts/dejavu-fonts-ttf-*`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tcpdf/fonts/freefont-*`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tcpdf/fonts/ae_fonts_*`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tcpdf/fonts/utils`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/tcpdf/LICENSE.TXT`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/dejavu-fonts-ttf-*`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/freefont-*`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/ae_fonts_*`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/fonts/utils`;
        $ret=`rm -fr $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/tools`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/includes/tecnickcom/tcpdf/LICENSE.TXT`;
        $ret=`rm -f  $BUILDROOT/$PROJECT/htdocs/theme/common/octicons/LICENSE`;
	}

	# Build package for each target
	#------------------------------
	foreach my $target (sort keys %CHOOSEDTARGET) 
	{
		if ($CHOOSEDTARGET{$target} < 0) { next; }
		if ($target eq '-CHKSUM') { next; }
		
		print "\nBuild package for target $target\n";

		if ($target eq 'SNAPSHOT') 
		{
			$NEWDESTI=$DESTI;

			print "Remove target $FILENAMESNAPSHOT.tgz...\n";
			unlink("$NEWDESTI/$FILENAMESNAPSHOT.tgz");

			#rmdir "$BUILDROOT/$FILENAMESNAPSHOT";
			$ret=`rm -fr $BUILDROOT/$FILENAMESNAPSHOT`;
			print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMESNAPSHOT\n";
			$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMESNAPSHOT\"";
			$ret=`$cmd`;

			print "Compress $BUILDROOT into $FILENAMESNAPSHOT.tgz...\n";
			$cmd="tar --exclude doli*.tgz --exclude doli*.deb --exclude doli*.exe --exclude doli*.xz --exclude doli*.zip --exclude doli*.rpm --exclude .cache --exclude .settings --exclude conf.php --exclude conf.php.mysql --exclude conf.php.old --exclude conf.php.postgres --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$FILENAMESNAPSHOT.tgz\" $FILENAMESNAPSHOT";
			print $cmd."\n";
			$ret=`$cmd`;

			# Move to final dir
			print "Move $FILENAMESNAPSHOT.tgz to $NEWDESTI/$FILENAMESNAPSHOT.tgz\n";
			$ret=`mv "$FILENAMESNAPSHOT.tgz" "$NEWDESTI/$FILENAMESNAPSHOT.tgz"`;
			next;
		}

		if ($target eq 'TGZ') 
		{
			$NEWDESTI=$DESTI;
			if ($NEWDESTI =~ /stable/)
			{
				mkdir($DESTI.'/standard');
				if (-d $DESTI.'/standard') { $NEWDESTI=$DESTI.'/standard'; } 
			}
			
			print "Remove target $FILENAMETGZ.tgz...\n";
			unlink("$NEWDESTI/$FILENAMETGZ.tgz");

			#rmdir "$BUILDROOT/$FILENAMETGZ";
			$ret=`rm -fr $BUILDROOT/$FILENAMETGZ`;
			print "Copy $BUILDROOT/$PROJECT/ to $BUILDROOT/$FILENAMETGZ\n";
			$cmd="cp -pr \"$BUILDROOT/$PROJECT/\" \"$BUILDROOT/$FILENAMETGZ\"";
			$ret=`$cmd`;

			$ret=`rm -fr $BUILDROOT/$FILENAMETGZ/build/exe`;
			$ret=`rm -fr $BUILDROOT/$FILENAMETGZ/htdocs/includes/ckeditor/_source`;	# We can't remove it with exclude file, we need it for some tarball packages
			
			print "Compress $FILENAMETGZ into $FILENAMETGZ.tgz...\n";
			$cmd="tar --exclude-vcs --exclude-from \"$BUILDROOT/$PROJECT/build/tgz/tar_exclude.txt\" --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$BUILDROOT/$FILENAMETGZ.tgz\" $FILENAMETGZ";
			print "$cmd\n";
			$ret=`$cmd`;

			# Move to final dir
			print "Move $BUILDROOT/$FILENAMETGZ.tgz to $NEWDESTI/$FILENAMETGZ.tgz\n";
			$ret=`mv "$BUILDROOT/$FILENAMETGZ.tgz" "$NEWDESTI/$FILENAMETGZ.tgz"`;
			next;
		}

		if ($target eq 'XZ') 
		{
			$NEWDESTI=$DESTI;
			if ($NEWDESTI =~ /stable/)
			{
				mkdir($DESTI.'/standard');
				if (-d $DESTI.'/standard') { $NEWDESTI=$DESTI.'/standard'; }
			} 

			print "Remove target $FILENAMEXZ.xz...\n";
			unlink("$NEWDESTI/$FILENAMEXZ.xz");

			#rmdir "$BUILDROOT/$FILENAMEXZ";
			$ret=`rm -fr $BUILDROOT/$FILENAMEXZ`;
			print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMEXZ\n";
			$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMEXZ\"";
			$ret=`$cmd`;

			$ret=`rm -fr $BUILDROOT/$FILENAMEXZ/build/exe`;
			$ret=`rm -fr $BUILDROOT/$FILENAMEXZ/htdocs/includes/ckeditor/_source`;	# We can't remove it with exclude file, we need it for some tarball packages
			
			print "Compress $FILENAMEXZ into $FILENAMEXZ.xz...\n";

			print "Go to directory $BUILDROOT\n";
			$olddir=getcwd();
			chdir("$BUILDROOT");
			$cmd= "xz -9 -r $BUILDROOT/$FILENAMEXZ.xz \*";
			print $cmd."\n";
			$ret= `$cmd`;
			chdir("$olddir");

			# Move to final dir
			print "Move $FILENAMEXZ.xz to $NEWDESTI/$FILENAMEXZ.xz\n";
			$ret=`mv "$BUILDROOT/$FILENAMEXZ.xz" "$NEWDESTI/$FILENAMEXZ.xz"`;
			next;
		}
		
		if ($target eq 'ZIP') 
		{
			$NEWDESTI=$DESTI;
			if ($NEWDESTI =~ /stable/)
			{
				mkdir($DESTI.'/standard');
				if (-d $DESTI.'/standard') { $NEWDESTI=$DESTI.'/standard'; }
			} 

			print "Remove target $FILENAMEZIP.zip...\n";
			unlink("$NEWDESTI/$FILENAMEZIP.zip");

			#rmdir "$BUILDROOT/$FILENAMEZIP";
			$ret=`rm -fr $BUILDROOT/$FILENAMEZIP`;
			print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMEZIP\n";
			$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$FILENAMEZIP\"";
			$ret=`$cmd`;

			$ret=`rm -fr $BUILDROOT/$FILENAMEZIP/build/exe`;
			$ret=`rm -fr $BUILDROOT/$FILENAMEZIP/htdocs/includes/ckeditor/_source`;	# We can't remove it with exclude file, we need it for some tarball packages

			print "Compress $FILENAMEZIP into $FILENAMEZIP.zip...\n";

			print "Go to directory $BUILDROOT\n";
			$olddir=getcwd();
			chdir("$BUILDROOT");
			$cmd= "7z a -r -tzip -xr\@\"$BUILDROOT\/$FILENAMEZIP\/build\/zip\/zip_exclude.txt\" -mx $BUILDROOT/$FILENAMEZIP.zip $FILENAMEZIP\/*";
			print $cmd."\n";
			$ret= `$cmd`;
			chdir("$olddir");
						
			# Move to final dir
			print "Move $FILENAMEZIP.zip to $NEWDESTI/$FILENAMEZIP.zip\n";
			$ret=`mv "$BUILDROOT/$FILENAMEZIP.zip" "$NEWDESTI/$FILENAMEZIP.zip"`;
			next;
		}
	
		if ($target =~ /RPM/)	                 # Linux only 
		{
			$NEWDESTI=$DESTI;
			$subdir="package_rpm_generic";
			if ($target =~ /FEDO/i) { $subdir="package_rpm_redhat-fedora"; }
			if ($target =~ /MAND/i) { $subdir="package_rpm_mandriva"; }
			if ($target =~ /OPEN/i) { $subdir="package_rpm_opensuse"; }
			if ($NEWDESTI =~ /stable/)
			{
				mkdir($DESTI.'/'.$subdir);
				if (-d $DESTI.'/'.$subdir) { $NEWDESTI=$DESTI.'/'.$subdir; }
			} 

			if ($RPMDIR eq "") { $RPMDIR=$ENV{'HOME'}."/rpmbuild"; }

			print "Version is $MAJOR.$MINOR.$REL1-$RPMSUBVERSION\n";

			print "Remove target ".$FILENAMERPM."...\n";
			unlink("$NEWDESTI/".$FILENAMERPM);
			print "Remove target ".$FILENAMERPMSRC."...\n";
			unlink("$NEWDESTI/".$FILENAMERPMSRC);

			print "Create directory $BUILDROOT/$FILENAMETGZ2\n";
			$ret=`rm -fr $BUILDROOT/$FILENAMETGZ2`;
			
			print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$FILENAMETGZ2\n";
			$cmd="cp -pr '$BUILDROOT/$PROJECT' '$BUILDROOT/$FILENAMETGZ2'";
			$ret=`$cmd`;

			# Removed files we don't need (already removed before)
			#$ret=`rm -fr $BUILDROOT/$FILENAMETGZ2/htdocs/includes/ckeditor/_source`;

			print "Set permissions on files/dir\n";
			$ret=`chmod -R 755 $BUILDROOT/$FILENAMETGZ2`;
			$cmd="find $BUILDROOT/$FILENAMETGZ2 -type f -exec chmod 644 {} \\; ";
			$ret=`$cmd`;

			# Build tgz
			print "Compress $FILENAMETGZ2 into $FILENAMETGZ2.tgz...\n";
			$ret=`tar --exclude-from "$SOURCE/build/tgz/tar_exclude.txt" --directory "$BUILDROOT" -czvf "$BUILDROOT/$FILENAMETGZ2.tgz" $FILENAMETGZ2`;

			print "Move $BUILDROOT/$FILENAMETGZ2.tgz to $RPMDIR/SOURCES/$FILENAMETGZ2.tgz\n";
			$cmd="mv $BUILDROOT/$FILENAMETGZ2.tgz $RPMDIR/SOURCES/$FILENAMETGZ2.tgz";
			$ret=`$cmd`;

			$BUILDFIC="${FILENAME}.spec";
			$BUILDFICSRC="${FILENAME}_generic.spec";
			if ($target =~ /FEDO/i) { $BUILDFICSRC="${FILENAME}_fedora.spec"; }
			if ($target =~ /MAND/i) { $BUILDFICSRC="${FILENAME}_mandriva.spec"; }
			if ($target =~ /OPEN/i) { $BUILDFICSRC="${FILENAME}_opensuse.spec"; }
			
			use Date::Language;
			$lang=Date::Language->new('English');
			$datestring = $lang->time2str("%a %b %e %Y", time);
    		$changelogstring="* ".$datestring." Laurent Destailleur (eldy) $MAJOR.$MINOR.$REL1-$RPMSUBVERSION\n- Upstream release\n";

			print "Generate file $BUILDROOT/$BUILDFIC from $SOURCE/build/rpm/${BUILDFICSRC}\n";
			open (SPECFROM,"<$SOURCE/build/rpm/${BUILDFICSRC}") || die "Error";
			open (SPECTO,">$BUILDROOT/$BUILDFIC") || die "Error";
			while (<SPECFROM>) {
				$_ =~ s/__FILENAMETGZ__/$FILENAMETGZ/;
				$_ =~ s/__VERSION__/$MAJOR.$MINOR.$REL1/;
				$_ =~ s/__RELEASE__/$RPMSUBVERSION/;
                $_ =~ s/__CHANGELOGSTRING__/$changelogstring/;
				print SPECTO $_;
			}
			close SPECFROM;
			close SPECTO;
	
			print "Copy patch file to $RPMDIR/SOURCES\n";
			$ret=`cp "$SOURCE/build/rpm/dolibarr-forrpm.patch" "$RPMDIR/SOURCES"`;
			$ret=`chmod 644 $RPMDIR/SOURCES/dolibarr-forrpm.patch`;

			print "Launch RPM build (rpmbuild --clean -ba $BUILDROOT/${BUILDFIC})\n";
			#$ret=`rpmbuild -vvvv --clean -ba $BUILDROOT/${BUILDFIC}`;
			$ret=`rpmbuild --clean -ba $BUILDROOT/${BUILDFIC}`;

			# Move to final dir
			print "Move $RPMDIR/RPMS/".$ARCH."/".$FILENAMETGZ2."-".$RPMSUBVERSION."*.".$ARCH.".rpm into $NEWDESTI/".$FILENAMETGZ2."-".$RPMSUBVERSION."*.".$ARCH.".rpm\n";
			$cmd="mv $RPMDIR/RPMS/".$ARCH."/".$FILENAMETGZ2."-".$RPMSUBVERSION."*.".$ARCH.".rpm \"$NEWDESTI/\"";
			$ret=`$cmd`;
			print "Move $RPMDIR/SRPMS/".$FILENAMETGZ2."-".$RPMSUBVERSION."*.src.rpm into $NEWDESTI/".$FILENAMETGZ2."-".$RPMSUBVERSION."*.src.rpm\n";
			$cmd="mv $RPMDIR/SRPMS/".$FILENAMETGZ2."-".$RPMSUBVERSION."*.src.rpm \"$NEWDESTI/\"";
			$ret=`$cmd`;
			print "Move $RPMDIR/SOURCES/".$FILENAMETGZ2.".tgz into $NEWDESTI/".$FILENAMETGZ2.".tgz\n";
			$cmd="mv \"$RPMDIR/SOURCES/".$FILENAMETGZ2.".tgz\" \"$NEWDESTI/".$FILENAMETGZ2.".tgz\"";
			#$ret=`$cmd`;
			next;
		}

		if ($target eq 'DEB') 
		{
			$NEWDESTI=$DESTI;
			if ($NEWDESTI =~ /stable/)
			{
				mkdir($DESTI.'/package_debian-ubuntu');
				if (-d $DESTI.'/package_debian-ubuntu') { $NEWDESTI=$DESTI.'/package_debian-ubuntu'; }
			} 

			$olddir=getcwd();

			print "Remove target ${FILENAMEDEB}_all.deb...\n";
			unlink("$NEWDESTI/${FILENAMEDEB}_all.deb");
			print "Remove target ${FILENAMEDEB}.dsc...\n";
			unlink("$NEWDESTI/${FILENAMEDEB}.dsc");
			print "Remove target ${FILENAMEDEB}.tar.gz...\n";
			unlink("$NEWDESTI/${FILENAMEDEB}.tar.gz");
			print "Remove target ${FILENAMEDEB}.changes...\n";
			unlink("$NEWDESTI/${FILENAMEDEB}.changes");
			print "Remove target ${FILENAMEDEB}.debian.tar.gz...\n";
			unlink("$NEWDESTI/${FILENAMEDEB}.debian.tar.gz");
			print "Remove target ${FILENAMEDEB}.debian.tar.xz...\n";
			unlink("$NEWDESTI/${FILENAMEDEB}.debian.tar.xz");
			print "Remove target ${FILENAMEDEBNATIVE}.orig.tar.gz...\n";
			unlink("$NEWDESTI/${FILENAMEDEBNATIVE}.orig.tar.gz");

			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp`;
			$ret=`rm -fr $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build`;
			
			print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$PROJECT.tmp\n";
			$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$PROJECT.tmp\"";
			$ret=`$cmd`;
			$cmd="cp -pr \"$BUILDROOT/$PROJECT/build/debian/apache/.htaccess\" \"$BUILDROOT/$PROJECT.tmp/build/debian/apache/.htaccess\"";
			$ret=`$cmd`;

			print "Remove other files\n";
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/README-FR.md`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/README`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/README-FR`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/aps`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/dmg`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/pad/README`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/tgz/README`;
			#$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/debian`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/debian/po`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/debian/source`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/changelog`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/compat`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/control*`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/copyright`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.config`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.desktop`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.docs`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.install`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.lintian-overrides`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.postrm`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.postinst`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.templates`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/dolibarr.templates.futur`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/rules`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/README.Debian`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/README.howto`;
			$ret=`rm -f  $BUILDROOT/$PROJECT.tmp/build/debian/watch`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/doap`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/exe`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/launchpad`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/live`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/patch`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/perl`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/rpm`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/build/zip`;
			# Removed duplicate license files
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/_source/LICENSE.md`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/_source/plugins/scayt/LICENSE.md`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/_source/plugins/wsc/LICENSE.md`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/LICENSE.md`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/plugins/scayt/LICENSE.md`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/plugins/wsc/LICENSE.md`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/php-iban/LICENSE`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/flot/LICENSE.txt`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/ColReorder/License.txt`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/ColVis/License.txt`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/FixedColumns/License.txt`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/extensions/Responsive/License.txt`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/datatables/license.txt`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/jquery/plugins/select2/LICENSE`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/mike42/escpos-php/LICENSE.md`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/mobiledetect/mobiledetectlib/LICENSE.txt`;
			
			# Removed files we don't need (already removed)
			#$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/htdocs/includes/ckeditor/ckeditor/_source`;
			
			# Rename upstream changelog to match debian rules
			$ret=`mv $BUILDROOT/$PROJECT.tmp/ChangeLog $BUILDROOT/$PROJECT.tmp/changelog`;
			
			# Prepare source package (init debian dir)
			print "Create directory $BUILDROOT/$PROJECT.tmp/debian\n";
			$ret=`mkdir "$BUILDROOT/$PROJECT.tmp/debian"`;
			print "Copy $SOURCE/build/debian/xxx to $BUILDROOT/$PROJECT.tmp/debian\n";
			# Add files for dpkg-source (changelog)
			#$ret=`cp -f  "$SOURCE/build/debian/changelog"      "$BUILDROOT/$PROJECT.tmp/debian"`;
			open (SPECFROM,"<$SOURCE/build/debian/changelog") || die "Error";
			open (SPECTO,">$BUILDROOT/$PROJECT.tmp/debian/changelog") || die "Error";
			while (<SPECFROM>) {
				$_ =~ s/__VERSION__/$MAJOR.$MINOR.$newbuild/;
				print SPECTO $_;
			}
			close SPECFROM;
			close SPECTO;
			# Add files for dpkg-source
			$ret=`cp -f  "$SOURCE/build/debian/compat"         "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/control"        "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/copyright"      "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.desktop"        	"$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.docs"        		"$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.install" 	        "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.lintian-overrides"  "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.xpm"  		      	"$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/rules"          "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/watch"          "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -fr "$SOURCE/build/debian/patches"        "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -fr "$SOURCE/build/debian/po"             "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -fr "$SOURCE/build/debian/source"         "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -fr "$SOURCE/build/debian/apache"         "$BUILDROOT/$PROJECT.tmp/debian/apache"`;
			$ret=`cp -f  "$SOURCE/build/debian/apache/.htaccess" "$BUILDROOT/$PROJECT.tmp/debian/apache"`;
			$ret=`cp -fr "$SOURCE/build/debian/lighttpd"       "$BUILDROOT/$PROJECT.tmp/debian/lighttpd"`;
			# Add files also required to build binary package
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.config"         "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.postinst"       "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.postrm"         "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/dolibarr.templates"      "$BUILDROOT/$PROJECT.tmp/debian"`;
			$ret=`cp -f  "$SOURCE/build/debian/install.forced.php.install"      "$BUILDROOT/$PROJECT.tmp/debian"`;
			
			# Set owners and permissions
			#print "Set owners on files/dir\n";
			#$ret=`chown -R root.root $BUILDROOT/$PROJECT.tmp`;

			print "Set permissions on files/dir\n";
			$ret=`chmod -R 755 $BUILDROOT/$PROJECT.tmp`;
			$cmd="find $BUILDROOT/$PROJECT.tmp -type f -exec chmod 644 {} \\; ";
			$ret=`$cmd`;
			$cmd="find $BUILDROOT/$PROJECT.tmp/build -name '*.php' -type f -exec chmod 755 {} \\; ";
			$ret=`$cmd`;
			$cmd="find $BUILDROOT/$PROJECT.tmp/build -name '*.dpatch' -type f -exec chmod 755 {} \\; ";
			$ret=`$cmd`;
			$cmd="find $BUILDROOT/$PROJECT.tmp/build -name '*.pl' -type f -exec chmod 755 {} \\; ";
			$ret=`$cmd`;
			$cmd="find $BUILDROOT/$PROJECT.tmp/dev -name '*.php' -type f -exec chmod 755 {} \\; ";
			$ret=`$cmd`;
			$ret=`chmod 755 $BUILDROOT/$PROJECT.tmp/debian/rules`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/dev/translation/autotranslator.class.php`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/class/actions_mymodule.class.php`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/class/api_mymodule.class.php`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/class/myobject.class.php`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/core/modules/modMyModule.class.php`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/mymoduleindex.php`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/myobject_card.php`;
			$ret=`chmod -R 644 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/myobject_list.php`;
			$ret=`chmod -R 755 $BUILDROOT/$PROJECT.tmp/htdocs/modulebuilder/template/scripts/myobject.php`;
			$cmd="find $BUILDROOT/$PROJECT.tmp/scripts -name '*.php' -type f -exec chmod 755 {} \\; ";
			$ret=`$cmd`;
			$cmd="find $BUILDROOT/$PROJECT.tmp/scripts -name '*.sh' -type f -exec chmod 755 {} \\; ";
			$ret=`$cmd`;
			
		
			print "Rename directory $BUILDROOT/$PROJECT.tmp into $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build\n";
			$cmd="mv $BUILDROOT/$PROJECT.tmp $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build";
			$ret=`$cmd`;


			print "Go into directory $BUILDROOT\n";
			chdir("$BUILDROOT");
			
			# We need a tarball to be able to build "quilt" debian package (not required for native but we need patch so it is not a native)
			print "Compress $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build into $BUILDROOT/$FILENAMEDEBNATIVE.orig.tar.gz...\n";
			$cmd="tar --exclude-vcs --exclude-from \"$BUILDROOT/$PROJECT/build/tgz/tar_exclude.txt\" --directory \"$BUILDROOT\" --mode=go-w --group=500 --owner=500 -czvf \"$BUILDROOT/$FILENAMEDEBNATIVE.orig.tar.gz\" $PROJECT-$MAJOR.$MINOR.$build";
			print $cmd."\n";
			$ret=`$cmd`;

			# Creation of source package          
			print "Go into directory $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build\n";
			chdir("$BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build");
			#$cmd="dpkg-source -b $BUILDROOT/$PROJECT-$MAJOR.$MINOR.$build";
			$cmd="dpkg-buildpackage -us -uc";
			print "Launch DEB build ($cmd)\n";
			$ret=`$cmd 2>&1 3>&1`;
			print $ret."\n";

			chdir("$olddir");
			
			print "You can check bin package with lintian --pedantic -E -I \"$NEWDESTI/${FILENAMEDEB}_all.deb\"\n";
			print "You can check src package with lintian --pedantic -E -I \"$NEWDESTI/${FILENAMEDEB}.dsc\"\n";
			
			# Move to final dir
			print "Move *_all.deb *.dsc *.orig.tar.gz *.changes to $NEWDESTI\n";
			$ret=`mv $BUILDROOT/*_all.deb "$NEWDESTI/"`;
			$ret=`mv $BUILDROOT/*.dsc "$NEWDESTI/"`;
			$ret=`mv $BUILDROOT/*.orig.tar.gz "$NEWDESTI/"`;
			$ret=`mv $BUILDROOT/*.debian.tar.xz "$NEWDESTI/"`;
			$ret=`mv $BUILDROOT/*.changes "$NEWDESTI/"`;
			next;
		}
		
		if ($target eq 'APS') 
		{
			$NEWDESTI=$DESTI;
			if ($NEWDESTI =~ /stable/)
			{
				mkdir($DESTI.'/package_aps');
				if (-d $DESTI.'/package_aps') { $NEWDESTI=$DESTI.'/package_aps'; }
			} 
			
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
			unlink "$NEWDESTI/$FILENAMEAPS.zip";

			#rmdir "$BUILDROOT/$PROJECT.tmp";
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp`;
			print "Create directory $BUILDROOT/$PROJECT.tmp\n";
			$ret=`mkdir -p "$BUILDROOT/$PROJECT.tmp"`;
			print "Copy $BUILDROOT/$PROJECT to $BUILDROOT/$PROJECT.tmp\n";
			$cmd="cp -pr \"$BUILDROOT/$PROJECT\" \"$BUILDROOT/$PROJECT.tmp\"";
			$ret=`$cmd`;

			print "Remove other files\n";
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/deb`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/dmg`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/doap`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/exe`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/live`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/patch`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/rpm`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/zip`;
			$ret=`rm -fr $BUILDROOT/$PROJECT.tmp/$PROJECT/build/perl`;

            $APSVERSION="1.2";
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
                        
    		# Move to final dir
            print "Move $BUILDROOT/$FILENAMEAPS.zip to $NEWDESTI/$FILENAMEAPS.zip\n";
            $ret=`mv "$BUILDROOT/$FILENAMEAPS.zip" "$NEWDESTI/$FILENAMEAPS.zip"`;
            next;
    	}

		if ($target eq 'EXEDOLIWAMP')
		{
			$NEWDESTI=$DESTI;
			if ($NEWDESTI =~ /stable/)
			{
				mkdir($DESTI.'/package_windows');
				if (-d $DESTI.'/package_windows') { $NEWDESTI=$DESTI.'/package_windows'; }
			} 

     		print "Remove target $NEWDESTI/$FILENAMEEXEDOLIWAMP.exe...\n";
    		unlink "$NEWDESTI/$FILENAMEEXEDOLIWAMP.exe";
 
 			print "Check that in your Wine setup, you create a Z: drive that point to your / directory.\n";

 			$SOURCEBACK=$SOURCE;
 			$SOURCEBACK =~ s/\//\\/g;

    		print "Prepare file \"$SOURCEBACK\\build\\exe\\doliwamp\\doliwamp.tmp.iss from \"$SOURCEBACK\\build\\exe\\doliwamp\\doliwamp.iss\"\n";
    		$ret=`cat "$SOURCE/build/exe/doliwamp/doliwamp.iss" | sed -e 's/__FILENAMEEXEDOLIWAMP__/$FILENAMEEXEDOLIWAMP/g' > "$SOURCE/build/exe/doliwamp/doliwamp.tmp.iss"`;

    		print "Compil exe $FILENAMEEXEDOLIWAMP.exe file from iss file \"$SOURCEBACK\\build\\exe\\doliwamp\\doliwamp.tmp.iss\"\n";
    		$cmd= "wine ISCC.exe \"Z:$SOURCEBACK\\build\\exe\\doliwamp\\doliwamp.tmp.iss\"";
			print "$cmd\n";
			$ret= `$cmd`;
			#print "$ret\n";

			# Move to final dir
			print "Move \"$SOURCE\\build\\$FILENAMEEXEDOLIWAMP.exe\" to $NEWDESTI/$FILENAMEEXEDOLIWAMP.exe\n";
    		rename("$SOURCE/build/$FILENAMEEXEDOLIWAMP.exe","$NEWDESTI/$FILENAMEEXEDOLIWAMP.exe");
            print "Move $SOURCE/build/$FILENAMEEXEDOLIWAMP.exe to $NEWDESTI/$FILENAMEEXEDOLIWAMP.exe\n";
            $ret=`mv "$SOURCE/build/$FILENAMEEXEDOLIWAMP.exe" "$NEWDESTI/$FILENAMEEXEDOLIWAMP.exe"`;
            
            print "Remove tmp file $SOURCE/build/exe/doliwamp/doliwamp.tmp.iss\n";
            $ret=`rm "$SOURCE/build/exe/doliwamp/doliwamp.tmp.iss"`;
            
    		next;
    	}
    }

	# Publish package for each target
	#--------------------------------
	foreach my $target (sort keys %CHOOSEDPUBLISH) 
	{
		if ($CHOOSEDPUBLISH{$target} < 0) { next; }
	
		print "\nList of files to publish (BUILD=$BUILD)\n";
		%filestoscansf=(
			"$DESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml"=>'none',				# none means it won't be published on SF
			"$DESTI/package_rpm_generic/$FILENAMERPM"=>'Dolibarr installer for Fedora-Redhat-Mandriva-Opensuse (DoliRpm)',
			"$DESTI/package_rpm_generic/$FILENAMERPMSRC"=>'none',						# none means it won't be published on SF
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_all.deb"=>'Dolibarr installer for Debian-Ubuntu (DoliDeb)',
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_amd64.changes"=>'none',		# none means it won't be published on SF
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.dsc"=>'none',					# none means it won't be published on SF
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.debian.tar.xz"=>'none',		# none means it won't be published on SF
			"$DESTI/package_debian-ubuntu/${FILENAMEDEBSHORT}.orig.tar.gz"=>'none',		# none means it won't be published on SF
			"$DESTI/package_windows/$FILENAMEEXEDOLIWAMP.exe"=>'Dolibarr installer for Windows (DoliWamp)',
			"$DESTI/standard/$FILENAMETGZ.tgz"=>'Dolibarr ERP-CRM',
			"$DESTI/standard/$FILENAMETGZ.zip"=>'Dolibarr ERP-CRM'
		);
		%filestoscanstableasso=(
			"$DESTI/signatures/filelist-$MAJOR.$MINOR.$BUILD.xml"=>'signatures',
			"$DESTI/package_rpm_generic/$FILENAMERPM"=>'package_rpm_generic',
			"$DESTI/package_rpm_generic/$FILENAMERPMSRC"=>'package_rpm_generic',
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_all.deb"=>'package_debian-ubuntu',
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}_amd64.changes"=>'package_debian-ubuntu',
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.dsc"=>'package_debian-ubuntu',
			"$DESTI/package_debian-ubuntu/${FILENAMEDEB}.debian.tar.xz"=>'package_debian-ubuntu',
			"$DESTI/package_debian-ubuntu/${FILENAMEDEBSHORT}.orig.tar.gz"=>'package_debian-ubuntu',
			"$DESTI/package_windows/$FILENAMEEXEDOLIWAMP.exe"=>'package_windows',
			"$DESTI/standard/$FILENAMETGZ.tgz"=>'standard',
			"$DESTI/standard/$FILENAMETGZ.zip"=>'standard'
		);
		if ($target eq 'ASSO' && $BUILD =~ /[a-z]/i)   { 	# Not stable
			%filestoscansf=(
				"$DESTI/$FILENAMERPM"=>'Dolibarr installer for Fedora-Redhat-Mandriva-Opensuse (DoliRpm)',
				"$DESTI/${FILENAMEDEB}_all.deb"=>'Dolibarr installer for Debian-Ubuntu (DoliDeb)',
				"$DESTI/$FILENAMEEXEDOLIWAMP.exe"=>'Dolibarr installer for Windows (DoliWamp)',
				"$DESTI/$FILENAMETGZ.tgz"=>'Dolibarr ERP-CRM',
				"$DESTI/$FILENAMETGZ.zip"=>'Dolibarr ERP-CRM'
			);
			%filestoscanstableasso=(
				"$DESTI/$FILENAMERPM"=>'',
				"$DESTI/${FILENAMEDEB}_all.deb"=>'',
				"$DESTI/$FILENAMEEXEDOLIWAMP.exe"=>'',
				"$DESTI/$FILENAMETGZ.tgz"=>'',
				"$DESTI/$FILENAMETGZ.zip"=>''
			);
		}

		use POSIX qw/strftime/;
		foreach my $file (sort keys %filestoscansf)
		{
			$found=0;
			my $filesize = -s $file;
			my $filedate = (stat $file)[9];
			print $file." ".($filesize?"(found)":"(not found)");
			print ($filesize?" - ".$filesize:"");
			print ($filedate?" - ".strftime("%Y-%m-%d %H:%M:%S",localtime($filedate)):"");
			print "\n";
		}

		if ($target eq 'SF' || $target eq 'ASSO') 
		{
			print "\n";
			
			if ($target eq 'SF') { $PUBLISH = $PUBLISHSTABLE; }
			if ($target eq 'ASSO' && $BUILD =~ /[a-z]/i)   { $PUBLISH = $PUBLISHBETARC.'/lastbuild'; }
			if ($target eq 'ASSO' && $BUILD =~ /^[0-9]+$/) { $PUBLISH = $PUBLISHBETARC.'/stable'; }
			
			$NEWPUBLISH=$PUBLISH;
			print "Publish to target $NEWPUBLISH. Click enter or CTRL+C...\n";

			# Ask which target to build
			$NUM_SCRIPT=<STDIN>; 
			chomp($NUM_SCRIPT);

			print "Create empty dir /tmp/emptydir. We need it to create target dir using rsync.\n";
			$ret=`mkdir -p "/tmp/emptydir/"`;
			
			%filestoscan=%filestoscansf;
			
			foreach my $file (sort keys %filestoscan)
			{
				$found=0;
				my $filesize = -s $file;
				if (! $filesize) { next; }

	    		if ($target eq 'SF') {
	    			if ($filestoscan{$file} eq 'none') {
	    				next;
	    			} 
	    			$destFolder="$NEWPUBLISH/$filestoscan{$file}/".$MAJOR.'.'.$MINOR.'.'.$BUILD;
	    		}
	    		elsif ($target eq 'ASSO' and $NEWPUBLISH =~ /stable/) {
	    			$destFolder="$NEWPUBLISH/$filestoscanstableasso{$file}";
	    		} 
	    		elsif ($target eq 'ASSO' and $NEWPUBLISH !~ /stable/) {
	    			$destFolder="$NEWPUBLISH";
	    		} 
	    		else	# No more used
	    		{
	    			$dirnameonly=$file;
	    			$dirnameonly =~ s/.*\/([^\/]+)\/[^\/]+$/$1/;  
	    			$filenameonly=$file;
	    			$filenameonly =~ s/.*\/[^\/]+\/([^\/])+$/$1/;  
	    			$destFolder="$NEWPUBLISH/$dirnameonly";
	    		}

				print "\n";
	    		print "Publish file ".$file." to ".$destFolder."\n";

				# mkdir	   
				#my $ssh = Net::SSH::Perl->new("frs.sourceforge.net");
				#$ssh->login("$user","$pass"); 		
				#use String::ShellQuote qw( shell_quote );
				#$ssh->cmd('mkdir '.shell_quote($destFolder).' && exit');

				#use Net::SFTP::Foreign;
				#my $sftp = Net::SFTP::Foreign->new($ip, user => $user, password => $pass, autodie => 1);
				#$sftp->mkdir($destFolder)

				#$command="ssh eldy,dolibarr\@frs.sourceforge.net mkdir -p \"$destFolder\"";
				#print "$command\n";	
				#my $ret=`$command 2>&1`;

				$command="rsync -s -e 'ssh' --recursive /tmp/emptydir/ \"".$destFolder."\"";
				print "$command\n";	
				my $ret=`$command 2>&1`;

				$command="rsync -s -e 'ssh' \"$file\" \"".$destFolder."\"";
				print "$command\n";	
				my $ret2=`$command 2>&1`;
				print "$ret2\n";
			}
		}
	}    
}

print "\n----- Summary -----\n";
foreach my $target (sort keys %CHOOSEDTARGET) {
	if ($target eq '-CHKSUM') { print "Checksum was generated"; next; }
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
