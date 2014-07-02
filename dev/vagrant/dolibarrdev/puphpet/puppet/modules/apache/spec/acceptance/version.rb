_osfamily               = fact('osfamily')
_operatingsystem        = fact('operatingsystem')
_operatingsystemrelease = fact('operatingsystemrelease').to_f

case _osfamily
when 'RedHat'
  $confd_dir        = '/etc/httpd/conf.d'
  $conf_file        = '/etc/httpd/conf/httpd.conf'
  $ports_file       = '/etc/httpd/conf/ports.conf'
  $vhost_dir        = '/etc/httpd/conf.d'
  $vhost            = '/etc/httpd/conf.d/15-default.conf'
  $run_dir          = '/var/run/httpd'
  $service_name     = 'httpd'
  $package_name     = 'httpd'
  $error_log        = 'error_log'
  $suphp_handler    = 'php5-script'
  $suphp_configpath = 'undef'

  if (_operatingsystem == 'Fedora' and _operatingsystemrelease >= 18) or (_operatingsystem != 'Fedora' and _operatingsystemrelease >= 7)
    $apache_version = '2.4'
  else
    $apache_version = '2.2'
  end
when 'Debian'
  $confd_dir        = '/etc/apache2/mods-available'
  $conf_file        = '/etc/apache2/apache2.conf'
  $ports_file       = '/etc/apache2/ports.conf'
  $vhost            = '/etc/apache2/sites-available/15-default.conf'
  $vhost_dir        = '/etc/apache2/sites-enabled'
  $run_dir          = '/var/run/apache2'
  $service_name     = 'apache2'
  $package_name     = 'apache2'
  $error_log        = 'error.log'
  $suphp_handler    = 'x-httpd-php'
  $suphp_configpath = '/etc/php5/apache2'

  if _operatingsystem == 'Ubuntu' and _operatingsystemrelease >= 13.10
    $apache_version = '2.4'
  else
    $apache_version = '2.2'
  end
when 'FreeBSD'
  $confd_dir        = '/usr/local/etc/apache22/Includes'
  $conf_file        = '/usr/local/etc/apache22/httpd.conf'
  $ports_file       = '/usr/local/etc/apache22/Includes/ports.conf'
  $vhost            = '/usr/local/etc/apache22/Vhosts/15-default.conf'
  $vhost_dir        = '/usr/local/etc/apache22/Vhosts'
  $run_dir          = '/var/run/apache22'
  $service_name     = 'apache22'
  $package_name     = 'apache22'
  $error_log        = 'http-error.log'

  $apache_version = '2.2'
else
  $apache_version = '0'
end

