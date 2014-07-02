class apache::mod::fcgid(
  $options = {},
) {
  ::apache::mod { 'fcgid': }

  # Template uses:
  # - $options
  file { 'fcgid.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/fcgid.conf",
    content => template('apache/mod/fcgid.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
