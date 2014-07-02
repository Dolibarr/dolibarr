# Define for creating a database. See README.md for more details.
define postgresql::server::database(
  $dbname     = $title,
  $owner      = $postgresql::server::user,
  $tablespace = undef,
  $template   = 'template0',
  $encoding   = $postgresql::server::encoding,
  $locale     = $postgresql::server::locale,
  $istemplate = false
) {
  $createdb_path = $postgresql::server::createdb_path
  $user          = $postgresql::server::user
  $group         = $postgresql::server::group
  $psql_path     = $postgresql::server::psql_path
  $version       = $postgresql::server::version

  # Set the defaults for the postgresql_psql resource
  Postgresql_psql {
    psql_user  => $user,
    psql_group => $group,
    psql_path  => $psql_path,
  }

  # Optionally set the locale switch. Older versions of createdb may not accept
  # --locale, so if the parameter is undefined its safer not to pass it.
  if ($version != '8.1') {
    $locale_option = $locale ? {
      undef   => '',
      default => "--locale=${locale} ",
    }
    $public_revoke_privilege = 'CONNECT'
  } else {
    $locale_option = ''
    $public_revoke_privilege = 'ALL'
  }

  $encoding_option = $encoding ? {
    undef   => '',
    default => "--encoding '${encoding}' ",
  }

  $tablespace_option = $tablespace ? {
    undef   => '',
    default => "--tablespace='${tablespace}' ",
  }

  $createdb_command = "${createdb_path} --owner='${owner}' --template=${template} ${encoding_option}${locale_option}${tablespace_option} '${dbname}'"

  postgresql_psql { "Check for existence of db '${dbname}'":
    command => 'SELECT 1',
    unless  => "SELECT datname FROM pg_database WHERE datname='${dbname}'",
    require => Class['postgresql::server::service']
  }~>
  exec { $createdb_command :
    refreshonly => true,
    user        => $user,
    logoutput   => on_failure,
  }~>

  # This will prevent users from connecting to the database unless they've been
  #  granted privileges.
  postgresql_psql {"REVOKE ${public_revoke_privilege} ON DATABASE \"${dbname}\" FROM public":
    db          => $user,
    refreshonly => true,
  }

  Exec [ $createdb_command ]->
  postgresql_psql {"UPDATE pg_database SET datistemplate = ${istemplate} WHERE datname = '${dbname}'":
    unless => "SELECT datname FROM pg_database WHERE datname = '${dbname}' AND datistemplate = ${istemplate}",
  }

  # Build up dependencies on tablespace
  if($tablespace != undef and defined(Postgresql::Server::Tablespace[$tablespace])) {
    Postgresql::Server::Tablespace[$tablespace]->Exec[$createdb_command]
  }
}
