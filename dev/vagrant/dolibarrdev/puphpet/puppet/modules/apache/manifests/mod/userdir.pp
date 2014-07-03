class apache::mod::userdir (
  $home = '/home',
  $dir = 'public_html',
  $disable_root = true,
  $apache_version = $::apache::apache_version,
) {
  ::apache::mod { 'userdir': }

  # Template uses $home, $dir, $disable_root, $apache_version
  file { 'userdir.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/userdir.conf",
    content => template('apache/mod/userdir.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
