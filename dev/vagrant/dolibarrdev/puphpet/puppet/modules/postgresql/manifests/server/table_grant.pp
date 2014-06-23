# This resource wraps the grant resource to manage table grants specifically.
# See README.md for more details.
define postgresql::server::table_grant(
  $privilege,
  $table,
  $db,
  $role,
  $psql_db   = undef,
  $psql_user = undef
) {
  postgresql::server::grant { "table:${name}":
    role        => $role,
    db          => $db,
    privilege   => $privilege,
    object_type => 'TABLE',
    object_name => $table,
    psql_db     => $psql_db,
    psql_user   => $psql_user,
  }
}
