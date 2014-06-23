# See README.md for details.
define mysql::db (
  $user,
  $password,
  $charset     = 'utf8',
  $collate     = 'utf8_general_ci',
  $host        = 'localhost',
  $grant       = 'ALL',
  $sql         = '',
  $enforce_sql = false,
  $ensure      = 'present'
) {
  #input validation
  validate_re($ensure, '^(present|absent)$',
  "${ensure} is not supported for ensure. Allowed values are 'present' and 'absent'.")
  $table = "${name}.*"

  include '::mysql::client'

  mysql_database { $name:
    ensure   => $ensure,
    charset  => $charset,
    collate  => $collate,
    provider => 'mysql',
    require  => [ Class['mysql::server'], Class['mysql::client'] ],
    before   => Mysql_user["${user}@${host}"],
  }

  $user_resource = {
    ensure        => $ensure,
    password_hash => mysql_password($password),
    provider      => 'mysql',
    require       => Class['mysql::server'],
  }
  ensure_resource('mysql_user', "${user}@${host}", $user_resource)

  if $ensure == 'present' {
    mysql_grant { "${user}@${host}/${table}":
      privileges => $grant,
      provider   => 'mysql',
      user       => "${user}@${host}",
      table      => $table,
      require    => [ Mysql_user["${user}@${host}"], Class['mysql::server'] ],
    }

    $refresh = ! $enforce_sql

    if $sql {
      exec{ "${name}-import":
        command     => "/usr/bin/mysql ${name} < ${sql}",
        logoutput   => true,
        environment => "HOME=${::root_home}",
        refreshonly => $refresh,
        require     => Mysql_grant["${user}@${host}/${table}"],
        subscribe   => Mysql_database[$name],
      }
    }
  }
}
