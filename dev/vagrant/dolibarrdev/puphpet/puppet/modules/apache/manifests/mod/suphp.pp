class apache::mod::suphp (
){
  ::apache::mod { 'suphp': }

  file {'suphp.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/suphp.conf",
    content => template('apache/mod/suphp.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd']
  }
}

