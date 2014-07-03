class apache::mod::authnz_ldap (
  $verifyServerCert = true,
) {
  include '::apache::mod::ldap'
  ::apache::mod { 'authnz_ldap': }

  validate_bool($verifyServerCert)

  # Template uses:
  # - $verifyServerCert
  file { 'authnz_ldap.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/authnz_ldap.conf",
    content => template('apache/mod/authnz_ldap.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
