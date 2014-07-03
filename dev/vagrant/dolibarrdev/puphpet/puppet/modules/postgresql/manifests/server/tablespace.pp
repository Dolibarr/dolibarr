# This module creates tablespace. See README.md for more details.
define postgresql::server::tablespace(
  $location,
  $owner   = undef,
  $spcname = $title
) {
  $user      = $postgresql::server::user
  $group     = $postgresql::server::group
  $psql_path = $postgresql::server::psql_path

  Postgresql_psql {
    psql_user  => $user,
    psql_group => $group,
    psql_path  => $psql_path,
  }

  if ($owner == undef) {
    $owner_section = ''
  } else {
    $owner_section = "OWNER \"${owner}\""
  }

  $create_tablespace_command = "CREATE TABLESPACE \"${spcname}\" ${owner_section} LOCATION '${location}'"

  file { $location:
    ensure => directory,
    owner  => $user,
    group  => $group,
    mode   => '0700',
  }

  $create_ts = "Create tablespace '${spcname}'"
  postgresql_psql { "Create tablespace '${spcname}'":
    command => $create_tablespace_command,
    unless  => "SELECT spcname FROM pg_tablespace WHERE spcname='${spcname}'",
    require => [Class['postgresql::server'], File[$location]],
  }

  if($owner != undef and defined(Postgresql::Server::Role[$owner])) {
    Postgresql::Server::Role[$owner]->Postgresql_psql[$create_ts]
  }
}
