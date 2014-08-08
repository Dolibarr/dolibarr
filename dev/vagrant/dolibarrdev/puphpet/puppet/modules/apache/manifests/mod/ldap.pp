class apache::mod::ldap (
  $apache_version = $::apache::apache_version,
){
  ::apache::mod { 'ldap': }
  # Template uses $apache_version
  file { 'ldap.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/ldap.conf",
    content => template('apache/mod/ldap.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
}
