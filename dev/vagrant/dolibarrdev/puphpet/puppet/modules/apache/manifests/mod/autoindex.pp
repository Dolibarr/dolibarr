class apache::mod::autoindex {
  ::apache::mod { 'autoindex': }
  # Template uses no variables
  file { 'autoindex.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/autoindex.conf",
    content => template('apache/mod/autoindex.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
