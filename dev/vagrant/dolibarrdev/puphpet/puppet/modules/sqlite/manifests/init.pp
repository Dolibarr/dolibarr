# Class: sqlite
#
# This class manages the installation of the sqlite
# database.
#
# Sample Usage:
# class { 'sqlite': }
class sqlite {
  package { 'sqlite':
    ensure => installed,
  }

  file { '/var/lib/sqlite/':
    ensure => directory,
  }
}
