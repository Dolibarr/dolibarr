class apache::package (
  $ensure     = 'present',
  $mpm_module = $::apache::params::mpm_module,
) inherits ::apache::params {
  case $::osfamily {
    'freebsd' : {
      $all_mpms = [
        'www/apache22',
        'www/apache22-worker-mpm',
        'www/apache22-event-mpm',
        'www/apache22-itk-mpm',
        'www/apache22-peruser-mpm',
      ]
      if $mpm_module {
        $apache_package = $mpm_module ? {
          'prefork' => 'www/apache22',
          default   => "www/apache22-${mpm_module}-mpm"
        }
      } else {
        $apache_package = 'www/apache22'
      }
      $other_mpms = delete($all_mpms, $apache_package)
      # Configure ports to have apache module packages dependent on correct
      # version of apache package (apache22, apache22-worker-mpm, ...)
      file_line { 'APACHE_PORT in /etc/make.conf':
        ensure => $ensure,
        path   => '/etc/make.conf',
        line   => "APACHE_PORT=${apache_package}",
        match  => '^\s*#?\s*APACHE_PORT\s*=\s*',
        before => Package['httpd'],
      }
      # remove other packages
      ensure_resource('package', $other_mpms, {
        ensure  => absent,
        before  => Package['httpd'],
        require => File_line['APACHE_PORT in /etc/make.conf'],
      })
    }
    default: {
      $apache_package = $::apache::params::apache_name
    }
  }
  package { 'httpd':
    ensure => $ensure,
    name   => $apache_package,
    notify => Class['Apache::Service'],
  }
}
