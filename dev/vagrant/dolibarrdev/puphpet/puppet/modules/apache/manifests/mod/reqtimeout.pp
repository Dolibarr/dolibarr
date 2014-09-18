class apache::mod::reqtimeout {
  ::apache::mod { 'reqtimeout': }
  # Template uses no variables
  file { 'reqtimeout.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/reqtimeout.conf",
    content => template('apache/mod/reqtimeout.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
