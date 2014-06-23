class apache::mod::setenvif {
  ::apache::mod { 'setenvif': }
  # Template uses no variables
  file { 'setenvif.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/setenvif.conf",
    content => template('apache/mod/setenvif.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
