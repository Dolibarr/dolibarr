class apache::mod::proxy (
  $proxy_requests = 'Off',
  $allow_from = undef,
  $apache_version = $::apache::apache_version,
) {
  ::apache::mod { 'proxy': }
  # Template uses $proxy_requests, $apache_version
  file { 'proxy.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/proxy.conf",
    content => template('apache/mod/proxy.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
