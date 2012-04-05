#----------------------------------------------------------------------------
# \file         dolibarr.pl
# \brief        Dolibarr script install for Virtualmin Pro
# \author       (c)2009-2012 Regis Houssin  <regis@dolibarr.fr>
#----------------------------------------------------------------------------


# script_dolibarr_desc()
sub script_dolibarr_desc
{
return "Dolibarr";
}

sub script_dolibarr_uses
{
return ( "php" );
}

# script_dolibarr_longdesc()
sub script_dolibarr_longdesc
{
return "Dolibarr ERP/CRM is a powerful Open Source software to manage a professional or foundation activity (small and medium enterprises, freelancers).";
}

sub script_dolibarr_author
{
return "Regis Houssin";
}

# script_dolibarr_versions()
sub script_dolibarr_versions
{
return ( "3.2.0", "3.1.0", "3.0.1", "2.9.0" );
}

sub script_dolibarr_category
{
return "Commerce";
}

sub script_dolibarr_php_vers
{
return ( 5 );
}

sub script_dolibarr_php_vars
{
return ( [ 'memory_limit', '64M', '+' ],
	[ 'upload_max_filesize', '10M', '+' ],
	[ 'max_execution_time', '60', '+' ] );
}

sub script_dolibarr_php_modules
{
local ($d, $ver, $phpver, $opts) = @_;
local ($dbtype, $dbname) = split(/_/, $opts->{'db'}, 2);
return $dbtype eq "mysql" ? ("mysql") : ("pgsql");
}

sub script_dolibarr_dbs
{
local ($d, $ver) = @_;
return ("mysql", "postgres");
}

# script_dolibarr_params(&domain, version, &upgrade-info)
# Returns HTML for table rows for options for installing dolibarr
sub script_dolibarr_params
{
local ($d, $ver, $upgrade) = @_;
local $rv;
local $hdir = &public_html_dir($d, 1);
if ($upgrade) {
	# Options are fixed when upgrading
	local ($dbtype, $dbname) = split(/_/, $upgrade->{'opts'}->{'db'}, 2);
	$rv .= &ui_table_row("Database for Dolibarr tables", $dbname);
	local $dir = $upgrade->{'opts'}->{'dir'};
	$dir =~ s/^$d->{'home'}\///;
	$rv .= &ui_table_row("Install directory", $dir);
	}
else {
	# Show editable install options
	local @dbs = &domain_databases($d, [ "mysql"]);
	$rv .= &ui_table_row("Database for Dolibarr tables",
		     &ui_database_select("db", undef, \@dbs, $d, "dolibarr"));
	$rv .= &ui_table_row("Install sub-directory under <tt>$hdir</tt>",
			     &ui_opt_textbox("dir", "dolibarr", 30,
					     "At top level"));
	if ($d->{'ssl'} && $ver >= 3.0) {
		$rv .= &ui_table_row("Force https connection?",
				     &ui_yesno_radio("forcehttps", 0));
		}
	}
return $rv;
}

# script_dolibarr_parse(&domain, version, &in, &upgrade-info)
# Returns either a hash ref of parsed options, or an error string
sub script_dolibarr_parse
{
local ($d, $ver, $in, $upgrade) = @_;
if ($upgrade) {
	# Options are always the same
	return $upgrade->{'opts'};
	}
else {
	local $hdir = &public_html_dir($d, 0);
	$in{'dir_def'} || $in{'dir'} =~ /\S/ && $in{'dir'} !~ /\.\./ ||
		return "Missing or invalid installation directory";
	local $dir = $in{'dir_def'} ? $hdir : "$hdir/$in{'dir'}";
	local ($newdb) = ($in->{'db'} =~ s/^\*//);
	return { 'db' => $in->{'db'},
		 'newdb' => $newdb,
		 'dir' => $dir,
		 'path' => $in->{'dir_def'} ? "/" : "/$in->{'dir'}",
		 'forcehttps' => $in->{'forcehttps'}, };
	}
}

# script_dolibarr_check(&domain, version, &opts, &upgrade-info)
# Returns an error message if a required option is missing or invalid
sub script_dolibarr_check
{
local ($d, $ver, $opts, $upgrade) = @_;
$opts->{'dir'} =~ /^\// || return "Missing or invalid install directory";
$opts->{'db'} || return "Missing database";
if (-r "$opts->{'dir'}/conf/conf.php") {
	return "Dolibarr appears to be already installed in the selected directory";
	}
local ($dbtype, $dbname) = split(/_/, $opts->{'db'}, 2);
local $clash = &find_database_table($dbtype, $dbname, "llx_.*");
$clash && return "Dolibarr appears to be already using the selected database (table $clash)";
return undef;
}

# script_dolibarr_files(&domain, version, &opts, &upgrade-info)
# Returns a list of files needed by dolibarr, each of which is a hash ref
# containing a name, filename and URL
sub script_dolibarr_files
{
local ($d, $ver, $opts, $upgrade) = @_;
local @files = ( { 'name' => "source",
	   'file' => "Dolibarr_$ver.tar.gz",
	   'url' => "http://prdownloads.sourceforge.net/dolibarr/dolibarr-$ver.tgz" } );
return @files;
}

sub script_dolibarr_commands
{
return ("tar", "gunzip");
}

# script_dolibarr_install(&domain, version, &opts, &files, &upgrade-info)
# Actually installs joomla, and returns either 1 and an informational
# message, or 0 and an error
sub script_dolibarr_install
{
local ($d, $version, $opts, $files, $upgrade, $domuser, $dompass) = @_;

local ($out, $ex);
if ($opts->{'newdb'} && !$upgrade) {
        local $err = &create_script_database($d, $opts->{'db'});
        return (0, "Database creation failed : $err") if ($err);
        }
local ($dbtype, $dbname) = split(/_/, $opts->{'db'}, 2);
local $dbuser = $dbtype eq "mysql" ? &mysql_user($d) : &postgres_user($d);
local $dbpass = $dbtype eq "mysql" ? &mysql_pass($d) : &postgres_pass($d, 1);
local $dbphptype = $dbtype eq "mysql" ? "mysqli" : "pgsql";
local $dbhost = &get_database_host($dbtype);
local $dberr = &check_script_db_connection($dbtype, $dbname, $dbuser, $dbpass);
return (0, "Database connection failed : $dberr") if ($dberr);

# Extract tar file to temp dir and copy to target
local $temp = &transname();
local $err = &extract_script_archive($files->{'source'}, $temp, $d,
			     $opts->{'dir'}, "dolibarr-$ver/htdocs");
$err && return (0, "Failed to extract source : $err");

# Add config file
local $cfiledir = "$opts->{'dir'}/conf/";
local $docdir = "$opts->{'dir'}/documents";
local $altdir = "$opts->{'dir'}/custom";
local $cfile = $cfiledir."conf.php";
local $oldcfile = &transname();
local $olddocdir = &transname();
local $oldaltdir = &transname();
local $url;

$tmpl = &get_template($d->{'template'});
$mycharset = $tmpl->{'mysql_charset'};
$mycollate = $tmpl->{'mysql_collate'};
$pgcharset = $tmpl->{'postgres_encoding'};
$charset = $dbtype eq "mysql" ? $mycharset : $pgcharset;
$collate = $dbtype eq "mysql" ? $mycollate : "C";

$path = &script_path_url($d, $opts);
if ($path =~ /^https:/ || $d->{'ssl'}) {
        $url = "https://$d->{'dom'}";
}
else {
        $url = "http://$d->{'dom'}";
}
if ($opts->{'path'} =~ /\w/) {
	$url .= $opts->{'path'};
}

if (!$upgrade) {
	local $cdef = "$opts->{'dir'}/conf/conf.php.example";
    &run_as_domain_user($d, "cp ".quotemeta($cdef)." ".quotemeta($cfile));
	&set_ownership_permissions(undef, undef, 0777, $cfiledir);
	&set_ownership_permissions(undef, undef, 0666, $cfile);
	&run_as_domain_user($d, "mkdir ".quotemeta($docdir));
	&set_ownership_permissions(undef, undef, 0777, $docdir);
	&run_as_domain_user($d, "mkdir ".quotemeta($altdir));
	&set_ownership_permissions(undef, undef, 0777, $altdir);
}
else {
	# Preserve old config file, documents and custom directory
	&copy_source_dest($cfile, $oldcfile);
	&copy_source_dest($docdir, $olddocdir);
	&copy_source_dest($altdir, $oldaltdir);
}

if ($upgrade) {
	# Put back original config file, documents and custom directory
	&copy_source_dest_as_domain_user($d, $oldcfile, $cfile);
	&copy_source_dest_as_domain_user($d, $olddocdir, $docdir);
	&copy_source_dest_as_domain_user($d, $oldaltdir, $altdir);
	
	# First page (Update database schema)
	local @params = ( [ "action", "upgrade" ],
			  [ "versionfrom", $upgrade->{'version'} ],
			  [ "versionto", $ver ],
	 		 );
	local $err = &call_dolibarr_wizard_page(\@params, "upgrade", $d, $opts);
	return (-1, "Dolibarr wizard failed : $err") if ($err);
	
	# Second page (Migrate some data)
	local @params = ( [ "action", "upgrade" ],
			  [ "versionfrom", $upgrade->{'version'} ],
			  [ "versionto", $ver ],
			 );
	local $err = &call_dolibarr_wizard_page(\@params, "upgrade2", $d, $opts);
	return (-1, "Dolibarr wizard failed : $err") if ($err);
	
	# Third page (Update version number)
	local @params = ( [ "action", "upgrade" ],
			  [ "versionfrom", $upgrade->{'version'} ],
			  [ "versionto", $ver ],
			 );
	local $err = &call_dolibarr_wizard_page(\@params, "etape5", $d, $opts);
	return (-1, "Dolibarr wizard failed : $err") if ($err);
	
	# Remove the installation directory.
	local $dinstall = "$opts->{'dir'}/install";
	$dinstall  =~ s/\/$//;
	$out = &run_as_domain_user($d, "rm -rf ".quotemeta($dinstall));
	
	}
else {
	# First page (Db connection and config file creation)
	local @params = ( [ "main_dir", $opts->{'dir'} ],
			  [ "main_data_dir", $opts->{'dir'}."/documents" ],
			  [ "main_url", $url ],
			  [ "db_type", $dbphptype ],
			  [ "db_host", $dbhost ],
			  [ "db_name", $dbname ],
			  [ "db_user", $dbuser ],
			  [ "db_pass", $dbpass ],
			  [ "action", "set" ],
			  [ "main_force_https", $opts->{'forcehttps'} ],
			  [ "dolibarr_main_db_character_set", $charset ],
			  [ "dolibarr_main_db_collation", $collate ],
			  [ "usealternaterootdir", "1" ],
			  [ "main_alt_dir_name", "custom" ],
			 );
	local $err = &call_dolibarr_wizard_page(\@params, "etape1", $d, $opts);
	return (-1, "Dolibarr wizard failed : $err") if ($err);
	
	# Second page (Populate database)
	local @params = ( [ "action", "set" ] );
	local $err = &call_dolibarr_wizard_page(\@params, "etape2", $d, $opts);
	return (-1, "Dolibarr wizard failed : $err") if ($err);
	
	# Third page (Add administrator account)
	local @params = ( [ "action", "set" ],
			  [ "login", "admin" ],
			  [ "pass", $dompass ],
			  [ "pass_verif", $dompass ],
	 		 );
	local $err = &call_dolibarr_wizard_page(\@params, "etape5", $d, $opts);
	return (-1, "Dolibarr wizard failed : $err") if ($err);
	
	# Remove the installation directory and protect config file.
	local $dinstall = "$opts->{'dir'}/install";
	$dinstall  =~ s/\/$//;
	$out = &run_as_domain_user($d, "rm -rf ".quotemeta($dinstall));
	&set_ownership_permissions(undef, undef, 0644, $cfile);
	&set_ownership_permissions(undef, undef, 0755, $cfiledir);
	}
 
# Return a URL for the user
local $rp = $opts->{'dir'};
$rp =~ s/^$d->{'home'}\///;
local $adminurl = $url;
return (1, "Dolibarr installation complete. Go to <a target=_new href='$url'>$url</a> to use it.", "Under $rp using $dbtype database $dbname", $url, 'admin', $dompass);
}

# call_dolibarr_wizard_page(&parameters, step-no, &domain, &opts)
sub call_dolibarr_wizard_page
{
local ($params, $page, $d, $opts) = @_;
local $params = join("&", map { $_->[0]."=".&urlize($_->[1]) } @$params );
local $ipage = $opts->{'path'}."/install/".$page.".php";
local ($iout, $ierror);

&post_http_connection($d, $ipage, $params, \$iout, \$ierror);

if ($ierror) {
	return $ierror;
	}

return undef;
}

# script_dolibarr_uninstall(&domain, version, &opts)
# Un-installs a dolibarr installation, by deleting the directory.
# Returns 1 on success and a message, or 0 on failure and an error
sub script_dolibarr_uninstall
{
local ($d, $version, $opts) = @_;

# Remove the contents of the target directory
local $derr = &delete_script_install_directory($d, $opts);
return (0, $derr) if ($derr);

# Remove all llx_ tables from the database
# 3 times because of constraints
&cleanup_script_database($d, $opts->{'db'}, "llx_");
&cleanup_script_database($d, $opts->{'db'}, "llx_");
&cleanup_script_database($d, $opts->{'db'}, "llx_");

# Take out the DB
if ($opts->{'newdb'}) {
        &delete_script_database($d, $opts->{'db'});
        }

return (1, "Dolibarr directory and tables deleted.");
}

# script_dolibarr_latest(version)
# Returns a URL and regular expression or callback func to get the version
sub script_dolibarr_latest
{
local ($ver) = @_;
if ($ver >= 3.0) {
	return ( "http://sourceforge.net/projects/dolibarr/files/".
		  "Dolibarr%20ERP-CRM",
		  "(3\\.[0-9\\.]+)" );
	}
elsif ($ver >= 2.9) {
	return ( "http://www.dolibarr.fr/files/stable/",
		 "dolibarr\\-(2\\.[0-9\\.]+)" );
	}
return ( );
}

sub script_dolibarr_site
{
return 'http://www.dolibarr.org/';
}

sub script_dolibarr_passmode
{
return 2;
}

1;

