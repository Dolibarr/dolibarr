# Manage a database grant. See README.md for more details.
define postgresql::server::database_grant(
  $privilege,
  $db,
  $role,
  $psql_db   = undef,
  $psql_user = undef
) {
  postgresql::server::grant { "database:${name}":
    role        => $role,
    db          => $db,
    privilege   => $privilege,
    object_type => 'DATABASE',
    object_name => $db,
    psql_db     => $psql_db,
    psql_user   => $psql_user,
  }
}
