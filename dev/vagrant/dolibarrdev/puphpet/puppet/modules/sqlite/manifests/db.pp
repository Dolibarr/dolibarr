# Define: sqlite::db
#
# This define allows for managing the existance of a sqlite database
#
# Parameters:
#  $location:
#    The location on disk to store the sqlite database
#  $owner:
#    The owner of the sqlite database file on disk
#  $group:
#    The group owning the sqlite database file on disk
#  $mode:
#    The mode of the sqlite datbase file on disk
#  $ensure:
#    Whether the database should be `present` or `absent`. Defaults to `present`
#  $sqlite_cmd:
#    The sqlite command for the node's platform.  Defaults to `sqlite3`
define sqlite::db(
    $location   = '',
    $owner      = 'root',
    $group      = 0,
    $mode       = '755',
    $ensure     = present,
    $sqlite_cmd = 'sqlite3'
  ) {

  $safe_location = $location ? {
    ''      => "/var/lib/sqlite/${name}.db",
    default => $location,
  }

  file { $safe_location:
    ensure => $ensure,
    owner  => $owner,
    group  => $group,
    notify => Exec["create_${name}_db"],
  }

  exec { "create_${name}_db":
    command     => "${sqlite_cmd} $safe_location",
    path        => '/usr/bin:/usr/local/bin',
    refreshonly => true,
  }
}
