#
class mysql::server::mysqltuner($ensure='present') {
  # mysql performance tester
  file { '/usr/local/bin/mysqltuner':
    ensure  => $ensure,
    mode    => '0550',
    source  => 'puppet:///modules/mysql/mysqltuner.pl',
  }
}
