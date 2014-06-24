class apache::mod::rpaf (
  $sethostname = true,
  $proxy_ips   = [ '127.0.0.1' ],
  $header      = 'X-Forwarded-For'
) {
  ::apache::mod { 'rpaf': }

  # Template uses:
  # - $sethostname
  # - $proxy_ips
  # - $header
  file { 'rpaf.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/rpaf.conf",
    content => template('apache/mod/rpaf.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
