class apache::mod::mime (
  $mime_support_package = $::apache::params::mime_support_package,
  $mime_types_config    = $::apache::params::mime_types_config,
) {
  apache::mod { 'mime': }
  # Template uses $mime_types_config
  file { 'mime.conf':
    ensure  => file,
    path    => "${::apache::mod_dir}/mime.conf",
    content => template('apache/mod/mime.conf.erb'),
    require => Exec["mkdir ${::apache::mod_dir}"],
    before  => File[$::apache::mod_dir],
    notify  => Service['httpd'],
  }
  if $mime_support_package {
    package { $mime_support_package:
      ensure => 'installed',
      before => File['mime.conf'],
    }
  }
}
