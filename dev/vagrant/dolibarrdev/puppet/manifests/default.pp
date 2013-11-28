## Begin Server manifest

if $server_values == undef {
  $server_values = hiera('server', false)
}

# Ensure the time is accurate, reducing the possibilities of apt repositories
# failing for invalid certificates
include '::ntp'

Exec { path => [ '/bin/', '/sbin/', '/usr/bin/', '/usr/sbin/' ] }
File { owner => 0, group => 0, mode => 0644 }

group { 'puppet': ensure => present }
group { 'www-data': ensure => present }

user { $::ssh_username:
  shell  => '/bin/bash',
  home   => "/home/${::ssh_username}",
  ensure => present
}

user { ['apache', 'nginx', 'httpd', 'www-data']:
  shell  => '/bin/bash',
  ensure => present,
  groups => 'www-data',
  require => Group['www-data']
}

file { "/home/${::ssh_username}":
    ensure => directory,
    owner  => $::ssh_username,
}

# copy dot files to ssh user's home directory
exec { 'dotfiles':
  cwd     => "/home/${::ssh_username}",
  command => "cp -r /vagrant/files/dot/.[a-zA-Z0-9]* /home/${::ssh_username}/ && chown -R ${::ssh_username} /home/${::ssh_username}/.[a-zA-Z0-9]*",
  onlyif  => "test -d /vagrant/files/dot",
  require => User[$::ssh_username]
}

case $::osfamily {
  # debian, ubuntu
  'debian': {
    class { 'apt': }

    Class['::apt::update'] -> Package <|
        title != 'python-software-properties'
    and title != 'software-properties-common'
    |>

    ensure_packages( ['augeas-tools'] )
  }
  # redhat, centos
  'redhat': {
    class { 'yum': extrarepo => ['epel'] }

    Class['::yum'] -> Yum::Managed_yumrepo <| |> -> Package <| |>

    exec { 'bash_git':
      cwd     => "/home/${::ssh_username}",
      command => "curl https://raw.github.com/git/git/master/contrib/completion/git-prompt.sh > /home/${::ssh_username}/.bash_git",
      creates => "/home/${::ssh_username}/.bash_git"
    }

    file_line { 'link ~/.bash_git':
      ensure  => present,
      line    => 'if [ -f ~/.bash_git ] ; then source ~/.bash_git; fi',
      path    => "/home/${::ssh_username}/.bash_profile",
      require => [
        Exec['dotfiles'],
        Exec['bash_git'],
      ]
    }

    file_line { 'link ~/.bash_aliases':
      ensure  => present,
      line    => 'if [ -f ~/.bash_aliases ] ; then source ~/.bash_aliases; fi',
      path    => "/home/${::ssh_username}/.bash_profile",
      require => [
        File_line['link ~/.bash_git'],
      ]
    }

    ensure_packages( ['augeas'] )
  }
}

if $php_values == undef {
  $php_values = hiera('php', false)
}

case $::operatingsystem {
  'debian': {
    add_dotdeb { 'packages.dotdeb.org': release => $lsbdistcodename }

    if is_hash($php_values) {
      # Debian Squeeze 6.0 can do PHP 5.3 (default) and 5.4
      if $lsbdistcodename == 'squeeze' and $php_values['version'] == '54' {
        add_dotdeb { 'packages.dotdeb.org-php54': release => 'squeeze-php54' }
      }
      # Debian Wheezy 7.0 can do PHP 5.4 (default) and 5.5
      elsif $lsbdistcodename == 'wheezy' and $php_values['version'] == '55' {
        add_dotdeb { 'packages.dotdeb.org-php55': release => 'wheezy-php55' }
      }
    }
  }
  'ubuntu': {
    apt::key { '4F4EA0AAE5267A6C': }

    if is_hash($php_values) {
      # Ubuntu Lucid 10.04, Precise 12.04, Quantal 12.10 and Raring 13.04 can do PHP 5.3 (default <= 12.10) and 5.4 (default <= 13.04)
      if $lsbdistcodename in ['lucid', 'precise', 'quantal', 'raring'] and $php_values['version'] == '54' {
        if $lsbdistcodename == 'lucid' {
          apt::ppa { 'ppa:ondrej/php5-oldstable': require => Apt::Key['4F4EA0AAE5267A6C'], options => '' }
        } else {
          apt::ppa { 'ppa:ondrej/php5-oldstable': require => Apt::Key['4F4EA0AAE5267A6C'] }
        }
      }
      # Ubuntu Precise 12.04, Quantal 12.10 and Raring 13.04 can do PHP 5.5
      elsif $lsbdistcodename in ['precise', 'quantal', 'raring'] and $php_values['version'] == '55' {
        apt::ppa { 'ppa:ondrej/php5': require => Apt::Key['4F4EA0AAE5267A6C'] }
      }
      elsif $lsbdistcodename in ['lucid'] and $php_values['version'] == '55' {
        err('You have chosen to install PHP 5.5 on Ubuntu 10.04 Lucid. This will probably not work!')
      }
    }
  }
  'redhat', 'centos': {
    if is_hash($php_values) {
      if $php_values['version'] == '54' {
        class { 'yum::repo::remi': }
      }
      # remi_php55 requires the remi repo as well
      elsif $php_values['version'] == '55' {
        class { 'yum::repo::remi': }
        class { 'yum::repo::remi_php55': }
      }
    }
  }
}

if !empty($server_values['packages']) {
  ensure_packages( $server_values['packages'] )
}

define add_dotdeb ($release){
   apt::source { $name:
    location          => 'http://packages.dotdeb.org',
    release           => $release,
    repos             => 'all',
    required_packages => 'debian-keyring debian-archive-keyring',
    key               => '89DF5277',
    key_server        => 'keys.gnupg.net',
    include_src       => true
  }
}

## Begin Apache manifest

if $yaml_values == undef {
  $yaml_values = loadyaml('/vagrant/puppet/hieradata/common.yaml')
}

if $apache_values == undef {
  $apache_values = $yaml_values['apache']
}

include puphpet::params

$webroot_location = $puphpet::params::apache_webroot_location

exec { "exec mkdir -p ${webroot_location}":
  command => "mkdir -p ${webroot_location}",
  onlyif  => "test -d ${webroot_location}",
}

if ! defined(File[$webroot_location]) {
  file { $webroot_location:
    ensure  => directory,
    group   => 'www-data',
    mode    => 0775,
    require => [
      Exec["exec mkdir -p ${webroot_location}"],
      Group['www-data']
    ]
  }
}

class { 'apache':
  user          => $apache_values['user'],
  group         => $apache_values['group'],
  default_vhost => $apache_values['default_vhost'],
  mpm_module    => $apache_values['mpm_module'],
  manage_user   => false,
  manage_group  => false
}

if $::osfamily == 'debian' {
  case $apache_values['mpm_module'] {
    'prefork': { ensure_packages( ['apache2-mpm-prefork'] ) }
    'worker':  { ensure_packages( ['apache2-mpm-worker'] ) }
    'event':   { ensure_packages( ['apache2-mpm-event'] ) }
  }
} elsif $::osfamily == 'redhat' and ! defined(Iptables::Allow['tcp/80']) {
  iptables::allow { 'tcp/80':
    port     => '80',
    protocol => 'tcp'
  }
}

create_resources(apache::vhost, $apache_values['vhosts'])

define apache_mod {
  if ! defined(Class["apache::mod::${name}"]) {
    class { "apache::mod::${name}": }
  }
}

if count($apache_values['modules']) > 0 {
  apache_mod { $apache_values['modules']: }
}

## Begin PHP manifest

if $php_values == undef {
  $php_values = hiera('php', false)
}

if $apache_values == undef {
  $apache_values = hiera('apache', false)
}

if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

Class['Php'] -> Class['Php::Devel'] -> Php::Module <| |> -> Php::Pear::Module <| |> -> Php::Pecl::Module <| |>

if $php_prefix == undef {
  $php_prefix = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint|SLES|OpenSuSE)/ => 'php5-',
    default                                 => 'php-',
  }
}

if $php_fpm_ini == undef {
  $php_fpm_ini = $::operatingsystem ? {
    /(?i:Ubuntu|Debian|Mint|SLES|OpenSuSE)/ => '/etc/php5/fpm/php.ini',
    default                                 => '/etc/php.ini',
  }
}

if is_hash($apache_values) {
  include apache::params

  $php_webserver_service = 'httpd'
  $php_webserver_user = $apache::params::user

  class { 'php':
    service => $php_webserver_service
  }
} elsif is_hash($nginx_values) {
  include nginx::params

  $php_webserver_service = "${php_prefix}fpm"
  $php_webserver_user = $nginx::params::nx_daemon_user

  class { 'php':
    package             => $php_webserver_service,
    service             => $php_webserver_service,
    service_autorestart => false,
    config_file         => $php_fpm_ini,
  }

  service { $php_webserver_service:
    ensure     => running,
    enable     => true,
    hasrestart => true,
    hasstatus  => true,
    require    => Package[$php_webserver_service]
  }
}

class { 'php::devel': }

if count($php_values['modules']['php']) > 0 {
  php_mod { $php_values['modules']['php']:; }
}
if count($php_values['modules']['pear']) > 0 {
  php_pear_mod { $php_values['modules']['pear']:; }
}
if count($php_values['modules']['pecl']) > 0 {
  php_pecl_mod { $php_values['modules']['pecl']:; }
}
if count($php_values['ini']) > 0 {
  $php_values['ini'].each { |$key, $value|
    puphpet::ini { $key:
      entry       => "CUSTOM/${key}",
      value       => $value,
      php_version => $php_values['version'],
      webserver   => $php_webserver_service
    }
  }

  if $php_values['ini']['session.save_path'] != undef {
    exec {"mkdir -p ${php_values['ini']['session.save_path']}":
      onlyif  => "test ! -d ${php_values['ini']['session.save_path']}",
    }

    file { $php_values['ini']['session.save_path']:
      ensure  => directory,
      group   => 'www-data',
      mode    => 0775,
      require => Exec["mkdir -p ${php_values['ini']['session.save_path']}"]
    }
  }
}

puphpet::ini { $key:
  entry       => 'CUSTOM/date.timezone',
  value       => $php_values['timezone'],
  php_version => $php_values['version'],
  webserver   => $php_webserver_service
}

define php_mod {
  php::module { $name: }
}
define php_pear_mod {
  php::pear::module { $name: use_package => false }
}
define php_pecl_mod {
  php::pecl::module { $name: use_package => false }
}

if $php_values['composer'] == 1 {
  class { 'composer':
    target_dir      => '/usr/local/bin',
    composer_file   => 'composer',
    download_method => 'curl',
    logoutput       => false,
    tmp_path        => '/tmp',
    php_package     => "${php::params::module_prefix}cli",
    curl_package    => 'curl',
    suhosin_enabled => false,
  }
}

if $xdebug_values == undef {
  $xdebug_values = hiera('xdebug', false)
}

if is_hash($apache_values) {
  $xdebug_webserver_service = 'httpd'
} elsif is_hash($nginx_values) {
  $xdebug_webserver_service = 'nginx'
} else {
  $xdebug_webserver_service = undef
}

if $xdebug_values['install'] != undef and $xdebug_values['install'] == 1 {
  class { 'puphpet::xdebug':
    webserver => $xdebug_webserver_service
  }

  if is_hash($xdebug_values['settings']) and count($xdebug_values['settings']) > 0 {
    $xdebug_values['settings'].each { |$key, $value|
      puphpet::ini { $key:
        entry       => "XDEBUG/${key}",
        value       => $value,
        php_version => $php_values['version'],
        webserver   => $xdebug_webserver_service
      }
    }
  }
}

## Begin Xhprof manifest

if $xhprof_values == undef {
  $xhprof_values = hiera('xhprof', false)
}

if is_hash($xhprof_values) and $xhprof_values['install'] == 1 {
  $xhprofPath = $xhprof_values['location']

  php::pecl::module { 'xhprof':
    use_package     => false,
    preferred_state => 'beta',
  }

  exec { 'delete-xhprof-path-if-not-git-repo':
    command => "rm -rf ${xhprofPath}",
    onlyif => "test ! -d ${xhprofPath}/.git"
  }

  vcsrepo { $xhprofPath:
    ensure   => present,
    provider => git,
    source   => 'https://github.com/facebook/xhprof.git',
    require  => Exec['delete-xhprof-path-if-not-git-repo']
  }

  file { "${xhprofPath}/xhprof_html":
    ensure  => directory,
    mode    => 0775,
    require => Vcsrepo[$xhprofPath]
  }

  composer::exec { 'xhprof-composer-run':
    cmd     => 'install',
    cwd     => $xhprofPath,
    require => [
      Class['composer'],
      File["${xhprofPath}/xhprof_html"]
    ]
  }
}

## Begin MySQL manifest

if $mysql_values == undef {
  $mysql_values = hiera('mysql', false)
}

if $php_values == undef {
  $php_values = hiera('php', false)
}

if $apache_values == undef {
  $apache_values = hiera('apache', false)
}

if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if $mysql_values['root_password'] {
  class { 'mysql::server':
    root_password => $mysql_values['root_password'],
  }

  if is_hash($mysql_values['databases']) and count($mysql_values['databases']) > 0 {
    create_resources(mysql_db, $mysql_values['databases'])
  }

  if is_hash($php_values) {
    if $::osfamily == 'redhat' and $php_values['version'] == '53' and ! defined(Php::Module['mysql']) {
      php::module { 'mysql': }
    } elsif ! defined(Php::Module['mysqlnd']) {
      php::module { 'mysqlnd': }
    }
  }
}

define mysql_db (
  $user,
  $password,
  $host,
  $grant    = [],
  $sql_file = false
) {
  if $name == '' or $password == '' or $host == '' {
    fail( 'MySQL DB requires that name, password and host be set. Please check your settings!' )
  }

  mysql::db { $name:
    user     => $user,
    password => $password,
    host     => $host,
    grant    => $grant,
    sql      => $sql_file,
  }
}

if $mysql_values['phpmyadmin'] == 1 and is_hash($php_values) {
  if $::osfamily == 'debian' {
    if $::operatingsystem == 'ubuntu' {
      apt::key { '80E7349A06ED541C': }
      apt::ppa { 'ppa:nijel/phpmyadmin': require => Apt::Key['80E7349A06ED541C'] }
    }

    $phpMyAdmin_package = 'phpmyadmin'
    $phpMyAdmin_folder = 'phpmyadmin'
  } elsif $::osfamily == 'redhat' {
    $phpMyAdmin_package = 'phpMyAdmin.noarch'
    $phpMyAdmin_folder = 'phpMyAdmin'
  }

  if ! defined(Package[$phpMyAdmin_package]) {
    package { $phpMyAdmin_package:
      require => Class['mysql::server']
    }
  }

  include puphpet::params

  if is_hash($apache_values) {
    $mysql_webroot_location = $puphpet::params::apache_webroot_location
  } elsif is_hash($nginx_values) {
    $mysql_webroot_location = $puphpet::params::nginx_webroot_location

    mysql_nginx_default_conf { 'override_default_conf':
      webroot => $mysql_webroot_location
    }
  }

  file { "${mysql_webroot_location}/phpmyadmin":
    target  => "/usr/share/${phpMyAdmin_folder}",
    ensure  => link,
    replace => 'no',
    require => [
      Package[$phpMyAdmin_package],
      File[$mysql_webroot_location]
    ]
  }
}

define mysql_nginx_default_conf (
  $webroot
) {
  if $php5_fpm_sock == undef {
    $php5_fpm_sock = '/var/run/php5-fpm.sock'
  }

  if $fastcgi_pass == undef {
    $fastcgi_pass = $php_values['version'] ? {
      undef   => null,
      '53'    => '127.0.0.1:9000',
      default => "unix:${php5_fpm_sock}"
    }
  }

  class { 'puphpet::nginx':
    fastcgi_pass => $fastcgi_pass,
    notify       => Class['nginx::service'],
  }
}

