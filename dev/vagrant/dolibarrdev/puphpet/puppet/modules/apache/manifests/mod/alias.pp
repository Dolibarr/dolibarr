class apache::mod::alias(
  $apache_version = $apache::apache_version
) {
  $icons_path = $::osfamily ? {
    'debian'  => '/usr/share/apache2/icons',
    'redhat'  => '/var/www/icons',
    'freebsd' => '/usr/local/www/apache22/icons',
  }
  apache::mod { 'alias': }
  # Template uses $icons_path
  file { 'alias.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/alias.conf",
    content => template('apache/mod/alias.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
