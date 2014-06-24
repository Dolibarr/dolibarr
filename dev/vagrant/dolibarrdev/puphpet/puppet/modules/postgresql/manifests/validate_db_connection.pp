# This type validates that a successful postgres connection can be established
# between the node on which this resource is run and a specified postgres
# instance (host/port/user/password/database name).
#
# See README.md for more details.
define postgresql::validate_db_connection(
  $database_host     = undef,
  $database_name     = undef,
  $database_password = undef,
  $database_username = undef,
  $database_port     = undef,
  $run_as            = undef,
  $sleep             = 2,
  $tries             = 10,
  $create_db_first   = true
) {
  require postgresql::client
  include postgresql::params

  $psql_path = $postgresql::params::psql_path

  $cmd_init = "${psql_path} --tuples-only --quiet "
  $cmd_host = $database_host ? {
    default => "-h ${database_host} ",
    undef   => "",
  }
  $cmd_user = $database_username ? {
    default => "-U ${database_username} ",
    undef   => "",
  }
  $cmd_port = $database_port ? {
    default => "-p ${database_port} ",
    undef   => "",
  }
  $cmd_dbname = $database_name ? {
    default => "--dbname ${database_name} ",
    undef   => "--dbname ${postgresql::params::default_database} ",
  }
  $env = $database_password ? {
    default => "PGPASSWORD=${database_password}",
    undef   => undef,
  }
  $cmd = join([$cmd_init, $cmd_host, $cmd_user, $cmd_port, $cmd_dbname])
  $validate_cmd = "/usr/local/bin/validate_postgresql_connection.sh ${sleep} ${tries} '${cmd}'"

  # This is more of a safety valve, we add a little extra to compensate for the
  # time it takes to run each psql command.
  $timeout = (($sleep + 2) * $tries)

  $exec_name = "validate postgres connection for ${database_host}/${database_name}"
  exec { $exec_name:
    command     => "echo 'Unable to connect to defined database using: ${cmd}' && false",
    unless      => $validate_cmd,
    cwd         => '/tmp',
    environment => $env,
    logoutput   => 'on_failure',
    user        => $run_as,
    path        => '/bin',
    timeout     => $timeout,
    require     => Package['postgresql-client'],
  }

  # This is a little bit of puppet magic.  What we want to do here is make
  # sure that if the validation and the database instance creation are being
  # applied on the same machine, then the database resource is applied *before*
  # the validation resource.  Otherwise, the validation is guaranteed to fail
  # on the first run.
  #
  # We accomplish this by using Puppet's resource collection syntax to search
  # for the Database resource in our current catalog; if it exists, the
  # appropriate relationship is created here.
  if($create_db_first) {
    Postgresql::Server::Database<|title == $database_name|> -> Exec[$exec_name]
  }
}
