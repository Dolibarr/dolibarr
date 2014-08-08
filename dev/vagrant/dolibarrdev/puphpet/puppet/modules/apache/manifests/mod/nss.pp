class apache::mod::nss (
  $transfer_log = "${::apache::params::logroot}/access.log",
  $error_log    = "${::apache::params::logroot}/error.log",
  $passwd_file  = undef
) {
  include ::apache::mod::mime

  apache::mod { 'nss': }

  $httpd_dir = $::apache::httpd_dir

  # Template uses:
  # $transfer_log
  # $error_log
  # $http_dir
  # passwd_file
  file { 'nss.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/nss.conf",
    content => template('apache/mod/nss.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
