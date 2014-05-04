## Begin Server manifest

if $server_values == undef {
  $server_values = hiera('server', false)
} if $vm_values == undef {
  $vm_values = hiera($::vm_target_key, false)
}
# Ensure the time is accurate, reducing the possibilities of apt repositories
# failing for invalid certificates
class { 'ntp': }

include 'puphpet'
include 'puphpet::params'

Exec { path => [ '/bin/', '/sbin/', '/usr/bin/', '/usr/sbin/' ] }
group { 'puppet':   ensure => present }
group { 'www-data': ensure => present }
group { 'www-user': ensure => present }

user { $::ssh_username:
  shell   => '/bin/bash',
  home    => "/home/${::ssh_username}",
  ensure  => present,
  groups  => ['www-data', 'www-user'],
  require => [Group['www-data'], Group['www-user']]
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
  command => "cp -r /vagrant/puphpet/files/dot/.[a-zA-Z0-9]* /home/${::ssh_username}/ \
              && chown -R ${::ssh_username} /home/${::ssh_username}/.[a-zA-Z0-9]* \
              && cp -r /vagrant/puphpet/files/dot/.[a-zA-Z0-9]* /root/",
  onlyif  => 'test -d /vagrant/puphpet/files/dot',
  returns => [0, 1],
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

    class { 'yum::repo::rpmforge': }
    class { 'yum::repo::repoforgeextras': }

    Class['::yum'] -> Yum::Managed_yumrepo <| |> -> Package <| |>

    if defined(Package['git']) == false {
      package { 'git':
        ensure  => latest,
        require => Class['yum::repo::repoforgeextras']
      }
    }

    exec { 'bash_git':
      cwd     => "/home/${::ssh_username}",
      command => "curl https://raw.github.com/git/git/master/contrib/completion/git-prompt.sh > /home/${::ssh_username}/.bash_git",
      creates => "/home/${::ssh_username}/.bash_git"
    }

    exec { 'bash_git for root':
      cwd     => '/root',
      command => "cp /home/${::ssh_username}/.bash_git /root/.bash_git",
      creates => '/root/.bash_git',
      require => Exec['bash_git']
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

    file_line { 'link ~/.bash_git for root':
      ensure  => present,
      line    => 'if [ -f ~/.bash_git ] ; then source ~/.bash_git; fi',
      path    => '/root/.bashrc',
      require => [
        Exec['dotfiles'],
        Exec['bash_git'],
      ]
    }

    file_line { 'link ~/.bash_aliases':
      ensure  => present,
      line    => 'if [ -f ~/.bash_aliases ] ; then source ~/.bash_aliases; fi',
      path    => "/home/${::ssh_username}/.bash_profile",
      require => File_line['link ~/.bash_git']
    }

    file_line { 'link ~/.bash_aliases for root':
      ensure  => present,
      line    => 'if [ -f ~/.bash_aliases ] ; then source ~/.bash_aliases; fi',
      path    => '/root/.bashrc',
      require => File_line['link ~/.bash_git for root']
    }

    ensure_packages( ['augeas'] )
  }
}

if $php_values == undef {
  $php_values = hiera('php', false)
}

case $::operatingsystem {
  'debian': {
    include apt::backports

    add_dotdeb { 'packages.dotdeb.org': release => $lsbdistcodename }

   if hash_key_equals($php_values, 'install', 1) {
      # Debian Squeeze 6.0 can do PHP 5.3 (default) and 5.4
      if $lsbdistcodename == 'squeeze' and $php_values['version'] == '54' {
        add_dotdeb { 'packages.dotdeb.org-php54': release => 'squeeze-php54' }
      }
      # Debian Wheezy 7.0 can do PHP 5.4 (default) and 5.5
      elsif $lsbdistcodename == 'wheezy' and $php_values['version'] == '55' {
        add_dotdeb { 'packages.dotdeb.org-php55': release => 'wheezy-php55' }
      }
    }

    $server_lsbdistcodename = downcase($lsbdistcodename)

    apt::force { 'git':
      release => "${server_lsbdistcodename}-backports",
      timeout => 60
    }
  }
  'ubuntu': {
    apt::key { '4F4EA0AAE5267A6C':
      key_server => 'hkp://keyserver.ubuntu.com:80'
    }
    apt::key { '4CBEDD5A':
      key_server => 'hkp://keyserver.ubuntu.com:80'
    }

    if $lsbdistcodename in ['lucid', 'precise'] {
      apt::ppa { 'ppa:pdoes/ppa': require => Apt::Key['4CBEDD5A'], options => '' }
    } else {
      apt::ppa { 'ppa:pdoes/ppa': require => Apt::Key['4CBEDD5A'] }
    }

    if hash_key_equals($php_values, 'install', 1) {
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
    if hash_key_equals($php_values, 'install', 1) {
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

# Begin open ports for Iptables
if $::osfamily == 'redhat'
  and has_key($vm_values, 'vm')
  and has_key($vm_values['vm'], 'network')
  and has_key($vm_values['vm']['network'], 'forwarded_port')
{
  create_resources( iptables_port, $vm_values['vm']['network']['forwarded_port'] )
}

define iptables_port (
  $host,
  $guest,
) {
  if ( ! defined(Iptables::Allow["tcp/${guest}"]) ) {
    iptables::allow { "tcp/${guest}":
      port     => $guest,
      protocol => 'tcp'
    }
  }
}

## Begin MailCatcher manifest

if $mailcatcher_values == undef {
  $mailcatcher_values = hiera('mailcatcher', false)
}

if hash_key_equals($mailcatcher_values, 'install', 1) {
  if ! defined(Package['tilt']) {
    package { 'tilt':
      ensure   => '1.3',
      provider => 'gem',
      before   => Class['mailcatcher']
    }
  }

  create_resources('class', { 'mailcatcher' => $mailcatcher_values['settings'] })

  if $::osfamily == 'redhat'
    and ! defined(Iptables::Allow["tcp/${mailcatcher_values['settings']['smtp_port']}"])
  {
    iptables::allow { "tcp/${mailcatcher_values['settings']['smtp_port']}":
      port     => $mailcatcher_values['settings']['smtp_port'],
      protocol => 'tcp'
    }
  }

  if $::osfamily == 'redhat'
    and ! defined(Iptables::Allow["tcp/${mailcatcher_values['settings']['http_port']}"])
  {
    iptables::allow { "tcp/${mailcatcher_values['settings']['http_port']}":
      port     => $mailcatcher_values['settings']['http_port'],
      protocol => 'tcp'
    }
  }

  if ! defined(Class['supervisord']) {
    class { 'supervisord':
      install_pip => true,
    }
  }

  $supervisord_mailcatcher_options = sort(join_keys_to_values({
    ' --smtp-ip'   => $mailcatcher_values['settings']['smtp_ip'],
    ' --smtp-port' => $mailcatcher_values['settings']['smtp_port'],
    ' --http-ip'   => $mailcatcher_values['settings']['http_ip'],
    ' --http-port' => $mailcatcher_values['settings']['http_port']
  }, ' '))

  $supervisord_mailcatcher_cmd = "mailcatcher ${supervisord_mailcatcher_options} -f  >> ${mailcatcher_values['settings']['log']}"

  supervisord::program { 'mailcatcher':
    command     => $supervisord_mailcatcher_cmd,
    priority    => '100',
    user        => 'mailcatcher',
    autostart   => true,
    autorestart => true,
    environment => {
      'PATH' => "/bin:/sbin:/usr/bin:/usr/sbin:${mailcatcher_values['settings']['path']}"
    },
    require => Package['mailcatcher']
  }
}

## Begin Apache manifest

if $yaml_values == undef {
  $yaml_values = loadyaml('/vagrant/puphpet/config.yaml')
} if $apache_values == undef {
  $apache_values = $yaml_values['apache']
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $hhvm_values == undef {
  $hhvm_values = hiera('hhvm', false)
}

if hash_key_equals($apache_values, 'install', 1) {
  include puphpet::params
  include apache::params

  $webroot_location = $puphpet::params::apache_webroot_location

  exec { "exec mkdir -p ${webroot_location}":
    command => "mkdir -p ${webroot_location}",
    creates => $webroot_location,
  }

  if (downcase($::provisioner_type) in ['virtualbox', 'vmware_fusion'])
    and ! defined(File[$webroot_location])
  {
    file { $webroot_location:
      ensure  => directory,
      mode    => 0775,
      require => [
        Exec["exec mkdir -p ${webroot_location}"],
        Group['www-data']
      ]
    }
  }

  if !(downcase($::provisioner_type) in ['virtualbox', 'vmware_fusion'])
    and ! defined(File[$webroot_location])
  {
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

  if hash_key_equals($hhvm_values, 'install', 1) {
    $mpm_module           = 'worker'
    $disallowed_modules   = ['php']
    $apache_conf_template = 'puphpet/apache/hhvm-httpd.conf.erb'
    $apache_php_package   = 'hhvm'
  } elsif hash_key_equals($php_values, 'install', 1) {
    $mpm_module           = 'prefork'
    $disallowed_modules   = []
    $apache_conf_template = $apache::params::conf_template
    $apache_php_package   = 'php'
  } else {
    $mpm_module           = 'prefork'
    $disallowed_modules   = []
    $apache_conf_template = $apache::params::conf_template
    $apache_php_package   = ''
  }

  if $::operatingsystem == 'ubuntu'
  and hash_key_equals($php_values, 'install', 1)
  and hash_key_equals($php_values, 'version', 55)
  {
    $apache_version = '2.4'
  } else {
    $apache_version = $apache::version::default
  }

  $apache_settings = merge($apache_values['settings'], {
    'mpm_module'     => $mpm_module,
    'conf_template'  => $apache_conf_template,
    'sendfile'       => $apache_values['settings']['sendfile'] ? { 1 => 'On', default => 'Off' },
    'apache_version' => $apache_version
  })

  create_resources('class', { 'apache' => $apache_settings })

  if $::osfamily == 'redhat' and ! defined(Iptables::Allow['tcp/80']) {
    iptables::allow { 'tcp/80':
      port     => '80',
      protocol => 'tcp'
    }
  }

  if $::osfamily == 'redhat' and ! defined(Iptables::Allow['tcp/443']) {
    iptables::allow { 'tcp/443':
      port     => '443',
      protocol => 'tcp'
    }
  }

  if hash_key_equals($apache_values, 'mod_pagespeed', 1) {
    class { 'puphpet::apache::modpagespeed': }
  }

  if hash_key_equals($apache_values, 'mod_spdy', 1) {
    class { 'puphpet::apache::modspdy':
      php_package => $apache_php_package
    }
  }

  if count($apache_values['vhosts']) > 0 {
    each( $apache_values['vhosts'] ) |$key, $vhost| {
      exec { "exec mkdir -p ${vhost['docroot']} @ key ${key}":
        command => "mkdir -p ${vhost['docroot']}",
        creates => $vhost['docroot'],
      }

      if (downcase($::provisioner_type) in ['virtualbox', 'vmware_fusion'])
        and ! defined(File[$vhost['docroot']])
      {
        file { $vhost['docroot']:
          ensure  => directory,
          mode    => 0765,
          require => Exec["exec mkdir -p ${vhost['docroot']} @ key ${key}"]
        }
      }

      if !(downcase($::provisioner_type) in ['virtualbox', 'vmware_fusion'])
        and ! defined(File[$vhost['docroot']])
      {
        file { $vhost['docroot']:
          ensure  => directory,
          group   => 'www-user',
          mode    => 0765,
          require => [
            Exec["exec mkdir -p ${vhost['docroot']} @ key ${key}"],
            Group['www-user']
          ]
        }
      }

      create_resources(apache::vhost, { "${key}" => merge($vhost, {
          'custom_fragment' => template('puphpet/apache/custom_fragment.erb'),
          'ssl'             => 'ssl' in $vhost and str2bool($vhost['ssl']) ? { true => true, default => false },
          'ssl_cert'        => $vhost['ssl_cert'] ? { undef => undef, '' => undef, default => $vhost['ssl_cert'] },
          'ssl_key'         => $vhost['ssl_key'] ? { undef => undef, '' => undef, default => $vhost['ssl_key'] },
          'ssl_chain'       => $vhost['ssl_chain'] ? { undef => undef, '' => undef, default => $vhost['ssl_chain'] },
          'ssl_certs_dir'   => $vhost['ssl_certs_dir'] ? { undef => undef, '' => undef, default => $vhost['ssl_certs_dir'] }
        })
      })
    }
  }

  if count($apache_values['modules']) > 0 {
    apache_mod { $apache_values['modules']: }
  }
}

define apache_mod {
  if ! defined(Class["apache::mod::${name}"]) and !($name in $disallowed_modules) {
    class { "apache::mod::${name}": }
  }
}

## Begin Nginx manifest

if $nginx_values == undef {
   $nginx_values = hiera('nginx', false)
} if $php_values == undef {
   $php_values = hiera('php', false)
} if $hhvm_values == undef {
  $hhvm_values = hiera('hhvm', false)
}

if hash_key_equals($nginx_values, 'install', 1) {
  if $lsbdistcodename == 'lucid' and hash_key_equals($php_values, 'version', '53') {
    apt::key { '67E15F46': key_server => 'hkp://keyserver.ubuntu.com:80' }
    apt::ppa { 'ppa:l-mierzwa/lucid-php5':
      options => '',
      require => Apt::Key['67E15F46']
    }
  }

  $webroot_location = $puphpet::params::nginx_webroot_location

  exec { "exec mkdir -p ${webroot_location}":
    command => "mkdir -p ${webroot_location}",
    onlyif  => "test -d ${webroot_location}",
  }

  if (downcase($::provisioner_type) in ['virtualbox', 'vmware_fusion'])
    and ! defined(File[$webroot_location])
  {
    file { $webroot_location:
      ensure  => directory,
      mode    => 0775,
      require => [
        Exec["exec mkdir -p ${webroot_location}"],
        Group['www-data']
      ]
    }
  }

  if !(downcase($::provisioner_type) in ['virtualbox', 'vmware_fusion'])
    and ! defined(File[$webroot_location])
  {
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

  if hash_key_equals($php_values, 'install', 1) {
    $php5_fpm_sock = '/var/run/php5-fpm.sock'

    if $php_values['version'] == undef {
      $fastcgi_pass = null
    } elsif $php_values['version'] == '53' {
      $fastcgi_pass = '127.0.0.1:9000'
    } else {
      $fastcgi_pass = "unix:${php5_fpm_sock}"
    }

    $fastcgi_param_parts = [
      'PATH_INFO $fastcgi_path_info',
      'PATH_TRANSLATED $document_root$fastcgi_path_info',
      'SCRIPT_FILENAME $document_root$fastcgi_script_name'
    ]

    if $::osfamily == 'redhat' and $fastcgi_pass == "unix:${php5_fpm_sock}" {
      exec { "create ${php5_fpm_sock} file":
        command => "touch ${php5_fpm_sock} && chmod 777 ${php5_fpm_sock}",
        onlyif  => ["test ! -f ${php5_fpm_sock}", "test ! -f ${php5_fpm_sock}="],
        require => Package['nginx']
      }

      exec { "listen = 127.0.0.1:9000 => listen = ${php5_fpm_sock}":
        command => "perl -p -i -e 's#listen = 127.0.0.1:9000#listen = ${php5_fpm_sock}#gi' /etc/php-fpm.d/www.conf",
        unless  => "grep -c 'listen = 127.0.0.1:9000' '${php5_fpm_sock}'",
        notify  => [
          Class['nginx::service'],
          Service['php-fpm']
        ],
        require => Exec["create ${php5_fpm_sock} file"]
      }
    }
  } elsif hash_key_equals($hhvm_values, 'install', 1) {
    $fastcgi_pass        = '127.0.0.1:9000'
    $fastcgi_param_parts = [
      'SCRIPT_FILENAME $document_root$fastcgi_script_name'
    ]
  } else {
    $fastcgi_pass        = ''
    $fastcgi_param_parts = []
  }

  class { 'nginx': }

  if count($nginx_values['vhosts']) > 0 {
    each( $nginx_values['vhosts'] ) |$key, $vhost| {
      exec { "exec mkdir -p ${vhost['www_root']} @ key ${key}":
        command => "mkdir -p ${vhost['www_root']}",
        creates => $vhost['docroot'],
      }

      if ! defined(File[$vhost['www_root']]) {
        file { $vhost['www_root']:
          ensure  => directory,
          require => Exec["exec mkdir -p ${vhost['www_root']} @ key ${key}"]
        }
      }
    }

    create_resources(nginx_vhost, $nginx_values['vhosts'])
  }

  if $::osfamily == 'redhat' and ! defined(Iptables::Allow['tcp/80']) {
    iptables::allow { 'tcp/80':
      port     => '80',
      protocol => 'tcp'
    }
  }
}

define nginx_vhost (
  $server_name,
  $server_aliases = [],
  $www_root,
  $listen_port,
  $index_files,
  $envvars = [],
){
  $merged_server_name = concat([$server_name], $server_aliases)

  if is_array($index_files) and count($index_files) > 0 {
    $try_files = $index_files[count($index_files) - 1]
  } else {
    $try_files = 'index.php'
  }

  nginx::resource::vhost { $server_name:
    server_name      => $merged_server_name,
    www_root         => $www_root,
    listen_port      => $listen_port,
    index_files      => $index_files,
    try_files        => ['$uri', '$uri/', "/${try_files}?\$args"],
    vhost_cfg_append => {
       sendfile => 'off'
    }
  }

  $fastcgi_param = concat($fastcgi_param_parts, $envvars)

  nginx::resource::location { "${server_name}-php":
    ensure              => present,
    vhost               => $server_name,
    location            => '~ \.php$',
    proxy               => undef,
    try_files           => ['$uri', '$uri/', "/${try_files}?\$args"],
    www_root            => $www_root,
    location_cfg_append => {
      'fastcgi_split_path_info' => '^(.+\.php)(/.+)$',
      'fastcgi_param'           => $fastcgi_param,
      'fastcgi_pass'            => $fastcgi_pass,
      'fastcgi_index'           => 'index.php',
      'include'                 => 'fastcgi_params'
    },
    notify              => Class['nginx::service'],
  }
}

## Begin PHP manifest

if $php_values == undef {
  $php_values = hiera('php', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($php_values, 'install', 1) {
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

  if hash_key_equals($apache_values, 'install', 1) {
    include apache::params

    if has_key($apache_values, 'mod_spdy') and $apache_values['mod_spdy'] == 1 {
      $php_webserver_service_ini = 'cgi'
    } else {
      $php_webserver_service_ini = 'httpd'
    }

    $php_webserver_service = 'httpd'
    $php_webserver_user    = $apache::params::user
    $php_webserver_restart = true

    class { 'php':
      service => $php_webserver_service
    }
  } elsif hash_key_equals($nginx_values, 'install', 1) {
    include nginx::params

    $php_webserver_service     = "${php_prefix}fpm"
    $php_webserver_service_ini = $php_webserver_service
    $php_webserver_user        = $nginx::params::nx_daemon_user
    $php_webserver_restart     = true

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
  } else {
    $php_webserver_service     = undef
    $php_webserver_service_ini = undef
    $php_webserver_restart     = false

    class { 'php':
      package             => "${php_prefix}cli",
      service             => $php_webserver_service,
      service_autorestart => false,
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
    each( $php_values['ini'] ) |$key, $value| {
      if is_array($value) {
        each( $php_values['ini'][$key] ) |$innerkey, $innervalue| {
          puphpet::ini { "${key}_${innerkey}":
            entry       => "CUSTOM_${innerkey}/${key}",
            value       => $innervalue,
            php_version => $php_values['version'],
            webserver   => $php_webserver_service_ini
          }
        }
      } else {
        puphpet::ini { $key:
          entry       => "CUSTOM/${key}",
          value       => $value,
          php_version => $php_values['version'],
          webserver   => $php_webserver_service_ini
        }
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
    webserver   => $php_webserver_service_ini
  }

  if hash_key_equals($php_values, 'composer', 1) {
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
}

define php_mod {
  if ! defined(Php::Module[$name]) {
    php::module { $name:
      service_autorestart => $php_webserver_restart,
    }
  }
}
define php_pear_mod {
  if ! defined(Php::Pear::Module[$name]) {
    php::pear::module { $name:
      use_package         => false,
      service_autorestart => $php_webserver_restart,
    }
  }
}
define php_pecl_mod {
  if ! defined(Php::Pecl::Module[$name]) {
    php::pecl::module { $name:
      use_package         => false,
      service_autorestart => $php_webserver_restart,
    }
  }
}

## Begin Xdebug manifest

if $xdebug_values == undef {
  $xdebug_values = hiera('xdebug', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($apache_values, 'install', 1) {
  $xdebug_webserver_service = 'httpd'
} elsif hash_key_equals($nginx_values, 'install', 1) {
  $xdebug_webserver_service = 'nginx'
} else {
  $xdebug_webserver_service = undef
}

if hash_key_equals($xdebug_values, 'install', 1)
  and hash_key_equals($php_values, 'install', 1)
{
  class { 'puphpet::xdebug':
    webserver => $xdebug_webserver_service
  }

  if is_hash($xdebug_values['settings']) and count($xdebug_values['settings']) > 0 {
    each( $xdebug_values['settings'] ) |$key, $value| {
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

if $php_values == undef {
  $php_values = hiera('php', false)
} if $xhprof_values == undef {
  $xhprof_values = hiera('xhprof', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($xhprof_values, 'install', 1)
  and hash_key_equals($php_values, 'install', 1)
{
  if $::operatingsystem == 'ubuntu' {
    apt::key { '8D0DC64F':
      key_server => 'hkp://keyserver.ubuntu.com:80'
    }

    apt::ppa { 'ppa:brianmercer/php5-xhprof': require => Apt::Key['8D0DC64F'] }
  }

  if hash_key_equals($apache_values, 'install', 1) {
    $xhprof_webroot_location = $puphpet::params::apache_webroot_location
    $xhprof_webserver_service = 'httpd'
  } elsif hash_key_equals($nginx_values, 'install', 1) {
    $xhprof_webroot_location = $puphpet::params::nginx_webroot_location
    $xhprof_webserver_service = 'nginx'
  } else {
    $xhprof_webroot_location = $xhprof_values['location']
    $xhprof_webserver_service = undef
  }

  if ! defined(Package['graphviz']) {
    package { 'graphviz':
      ensure  => present,
    }
  }

  class { 'puphpet::xhprof':
    php_version       => $php_values['version'],
    webroot_location  => $xhprof_webroot_location,
    webserver_service => $xhprof_webserver_service
  }
}

## Begin Drush manifest

if $drush_values == undef {
  $drush_values = hiera('drush', false)
}

if hash_key_equals($drush_values, 'install', 1) {
  if ($drush_values['settings']['drush.tag_branch'] != undef) {
    $drush_tag_branch = $drush_values['settings']['drush.tag_branch']
  } else {
    $drush_tag_branch = ''
  }

  include drush::git::drush
}

## Begin MySQL manifest

if $mysql_values == undef {
  $mysql_values = hiera('mysql', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

include 'mysql::params'

if hash_key_equals($mysql_values, 'install', 1) {
  if hash_key_equals($apache_values, 'install', 1) or hash_key_equals($nginx_values, 'install', 1) {
    $mysql_webserver_restart = true
  } else {
    $mysql_webserver_restart = false
  }

  if $::osfamily == 'redhat' {
    exec { 'mysql-community-repo':
      command => 'yum -y --nogpgcheck install "http://dev.mysql.com/get/mysql-community-release-el6-5.noarch.rpm" && touch /.puphpet-stuff/mysql-community-release',
      creates => '/.puphpet-stuff/mysql-community-release'
    }

    $mysql_server_require             = Exec['mysql-community-repo']
    $mysql_server_server_package_name = 'mysql-community-server'
    $mysql_server_client_package_name = 'mysql-community-client'
  } else {
    $mysql_server_require             = []
    $mysql_server_server_package_name = $mysql::params::server_package_name
    $mysql_server_client_package_name = $mysql::params::client_package_name
  }

  if hash_key_equals($php_values, 'install', 1) {
    $mysql_php_installed = true
    $mysql_php_package   = 'php'
  } elsif hash_key_equals($hhvm_values, 'install', 1) {
    $mysql_php_installed = true
    $mysql_php_package   = 'hhvm'
  } else {
    $mysql_php_installed = false
  }

  if $mysql_values['root_password'] {
    class { 'mysql::server':
      package_name  => $mysql_server_server_package_name,
      root_password => $mysql_values['root_password'],
      require       => $mysql_server_require
    }

    class { 'mysql::client':
      package_name => $mysql_server_client_package_name,
      require      => $mysql_server_require
    }

    if is_hash($mysql_values['databases']) and count($mysql_values['databases']) > 0 {
      create_resources(mysql_db, $mysql_values['databases'])
    }

    if $mysql_php_installed and $mysql_php_package == 'php' {
      if $::osfamily == 'redhat' and $php_values['version'] == '53' {
        $mysql_php_module = 'mysql'
      } elsif $lsbdistcodename == 'lucid' or $lsbdistcodename == 'squeeze' {
        $mysql_php_module = 'mysql'
      } else {
        $mysql_php_module = 'mysqlnd'
      }

      if ! defined(Php::Module[$mysql_php_module]) {
        php::module { $mysql_php_module:
          service_autorestart => $mysql_webserver_restart,
        }
      }
    }
  }

  if hash_key_equals($mysql_values, 'phpmyadmin', 1) and $mysql_php_installed {
    if hash_key_equals($apache_values, 'install', 1) {
      $mysql_pma_webroot_location = $puphpet::params::apache_webroot_location
    } elsif hash_key_equals($nginx_values, 'install', 1) {
      $mysql_pma_webroot_location = $puphpet::params::nginx_webroot_location

      mysql_nginx_default_conf { 'override_default_conf':
        webroot => $mysql_pma_webroot_location
      }
    } else {
      $mysql_pma_webroot_location = '/var/www'
    }

    class { 'puphpet::phpmyadmin':
      dbms             => 'mysql::server',
      webroot_location => $mysql_pma_webroot_location,
    }
  }

  if hash_key_equals($mysql_values, 'adminer', 1) and $mysql_php_installed {
    if hash_key_equals($apache_values, 'install', 1) {
      $mysql_adminer_webroot_location = $puphpet::params::apache_webroot_location
    } elsif hash_key_equals($nginx_values, 'install', 1) {
      $mysql_adminer_webroot_location = $puphpet::params::nginx_webroot_location
    } else {
      $mysql_adminer_webroot_location = $puphpet::params::apache_webroot_location
    }

    class { 'puphpet::adminer':
      location    => "${mysql_adminer_webroot_location}/adminer",
      owner       => 'www-data',
      php_package => $mysql_php_package
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

# @todo update this
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

## Begin PostgreSQL manifest

if $postgresql_values == undef {
  $postgresql_values = hiera('postgresql', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $hhvm_values == undef {
  $hhvm_values = hiera('hhvm', false)
}

if hash_key_equals($postgresql_values, 'install', 1) {
  if hash_key_equals($apache_values, 'install', 1) or hash_key_equals($nginx_values, 'install', 1) {
    $postgresql_webserver_restart = true
  } else {
    $postgresql_webserver_restart = false
  }

  if hash_key_equals($php_values, 'install', 1) {
    $postgresql_php_installed = true
    $postgresql_php_package   = 'php'
  } elsif hash_key_equals($hhvm_values, 'install', 1) {
    $postgresql_php_installed = true
    $postgresql_php_package   = 'hhvm'
  } else {
    $postgresql_php_installed = false
  }

  if $postgresql_values['settings']['root_password'] {
    group { $postgresql_values['settings']['user_group']:
      ensure => present
    }

    class { 'postgresql::globals':
      manage_package_repo => true,
      encoding            => $postgresql_values['settings']['encoding'],
      version             => $postgresql_values['settings']['version']
    }->
    class { 'postgresql::server':
      postgres_password => $postgresql_values['settings']['root_password'],
      version           => $postgresql_values['settings']['version'],
      require           => Group[$postgresql_values['settings']['user_group']]
    }

    if is_hash($postgresql_values['databases']) and count($postgresql_values['databases']) > 0 {
      create_resources(postgresql_db, $postgresql_values['databases'])
    }

    if $postgresql_php_installed and $postgresql_php_package == 'php' and ! defined(Php::Module['pgsql']) {
      php::module { 'pgsql':
        service_autorestart => $postgresql_webserver_restart,
      }
    }
  }

  if hash_key_equals($postgresql_values, 'adminer', 1) and $postgresql_php_installed {
    if hash_key_equals($apache_values, 'install', 1) {
      $postgresql_adminer_webroot_location = $puphpet::params::apache_webroot_location
    } elsif hash_key_equals($nginx_values, 'install', 1) {
      $postgresql_adminer_webroot_location = $puphpet::params::nginx_webroot_location
    } else {
      $postgresql_adminer_webroot_location = $puphpet::params::apache_webroot_location
    }

    class { 'puphpet::adminer':
      location    => "${postgresql_adminer_webroot_location}/adminer",
      owner       => 'www-data',
      php_package => $postgresql_php_package
    }
  }
}

define postgresql_db (
  $user,
  $password,
  $grant,
  $sql_file = false
) {
  if $name == '' or $user == '' or $password == '' or $grant == '' {
    fail( 'PostgreSQL DB requires that name, user, password and grant be set. Please check your settings!' )
  }

  postgresql::server::db { $name:
    user     => $user,
    password => $password,
    grant    => $grant
  }

  if $sql_file {
    $table = "${name}.*"

    exec{ "${name}-import":
      command     => "psql ${name} < ${sql_file}",
      logoutput   => true,
      refreshonly => $refresh,
      require     => Postgresql::Server::Db[$name],
      onlyif      => "test -f ${sql_file}"
    }
  }
}

## Begin MariaDb manifest

if $mariadb_values == undef {
  $mariadb_values = hiera('mariadb', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $hhvm_values == undef {
  $hhvm_values = hiera('hhvm', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($mariadb_values, 'install', 1) {
  if hash_key_equals($apache_values, 'install', 1) or hash_key_equals($nginx_values, 'install', 1) {
    $mariadb_webserver_restart = true
  } else {
    $mariadb_webserver_restart = false
  }

  if hash_key_equals($php_values, 'install', 1) {
    $mariadb_php_installed = true
    $mariadb_php_package   = 'php'
  } elsif hash_key_equals($hhvm_values, 'install', 1) {
    $mariadb_php_installed = true
    $mariadb_php_package   = 'hhvm'
  } else {
    $mariadb_php_installed = false
  }

  if has_key($mariadb_values, 'root_password') and $mariadb_values['root_password'] {
    class { 'puphpet::mariadb':
      version => $mariadb_values['version']
    }

    class { 'mysql::server':
      package_name  => $puphpet::params::mariadb_package_server_name,
      root_password => $mariadb_values['root_password']
    }

    class { 'mysql::client':
      package_name => $puphpet::params::mariadb_package_client_name
    }

    if is_hash($mariadb_values['databases']) and count($mariadb_values['databases']) > 0 {
      create_resources(mariadb_db, $mariadb_values['databases'])
    }

    if $mariadb_php_installed and $mariadb_php_package == 'php' {
      if $::osfamily == 'redhat' and $php_values['version'] == '53' {
        $mariadb_php_module = 'mysql'
      } elsif $lsbdistcodename == 'lucid' or $lsbdistcodename == 'squeeze' {
        $mariadb_php_module = 'mysql'
      } else {
        $mariadb_php_module = 'mysqlnd'
      }

      if ! defined(Php::Module[$mariadb_php_module]) {
        php::module { $mariadb_php_module:
          service_autorestart => $mariadb_webserver_restart,
        }
      }
    }
  }

  if hash_key_equals($mariadb_values, 'phpmyadmin', 1) and $mariadb_php_installed {
    if hash_key_equals($apache_values, 'install', 1) {
      $mariadb_pma_webroot_location = $puphpet::params::apache_webroot_location
    } elsif hash_key_equals($nginx_values, 'install', 1) {
      $mariadb_pma_webroot_location = $puphpet::params::nginx_webroot_location

      mariadb_nginx_default_conf { 'override_default_conf':
        webroot => $mariadb_pma_webroot_location
      }
    } else {
      $mariadb_pma_webroot_location = '/var/www'
    }

    class { 'puphpet::phpmyadmin':
      dbms             => 'mysql::server',
      webroot_location => $mariadb_pma_webroot_location,
    }
  }

  if hash_key_equals($mariadb_values, 'adminer', 1) and $mariadb_php_installed {
    if hash_key_equals($apache_values, 'install', 1) {
      $mariadb_adminer_webroot_location = $puphpet::params::apache_webroot_location
    } elsif hash_key_equals($nginx_values, 'install', 1) {
      $mariadb_adminer_webroot_location = $puphpet::params::nginx_webroot_location
    } else {
      $mariadb_adminer_webroot_location = $puphpet::params::apache_webroot_location
    }

    class { 'puphpet::adminer':
      location    => "${mariadb_adminer_webroot_location}/adminer",
      owner       => 'www-data',
      php_package => $mariadb_php_package
    }
  }
}

define mariadb_db (
  $user,
  $password,
  $host,
  $grant    = [],
  $sql_file = false
) {
  if $name == '' or $password == '' or $host == '' {
    fail( 'MariaDB requires that name, password and host be set. Please check your settings!' )
  }

  mysql::db { $name:
    user     => $user,
    password => $password,
    host     => $host,
    grant    => $grant,
    sql      => $sql_file,
  }
}

# @todo update this!
define mariadb_nginx_default_conf (
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

## Begin MongoDb manifest

if $mongodb_values == undef {
  $mongodb_values = hiera('mongodb', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($apache_values, 'install', 1)
  or hash_key_equals($nginx_values, 'install', 1)
{
  $mongodb_webserver_restart = true
} else {
  $mongodb_webserver_restart = false
}

if hash_key_equals($mongodb_values, 'install', 1) {
  Class['Mongodb::Globals'] -> Class['Mongodb::Server']

  class { 'mongodb::globals':
    manage_package_repo => true,
  }

  create_resources('class', { 'mongodb::server' => $mongodb_values['settings'] })

  case $::osfamily {
    'debian': {
      $mongodb_pecl = 'mongo'
    }
    'redhat': {
      class { '::mongodb::client':
        require => Class['::Mongodb::Server']
      }

      $mongodb_pecl = 'pecl-mongo'
    }
  }

  if is_hash($mongodb_values['databases']) and count($mongodb_values['databases']) > 0 {
    create_resources(mongodb_db, $mongodb_values['databases'])
  }

  if hash_key_equals($php_values, 'install', 1)
    and ! defined(Php::Pecl::Module[$mongodb_pecl])
  {
    php::pecl::module { $mongodb_pecl:
      use_package         => false,
      service_autorestart => $mongodb_webserver_restart,
      require             => Class['mongodb::server']
    }
  }
}

define mongodb_db (
  $user,
  $password
) {
  if $name == '' or $password == '' {
    fail( 'MongoDB requires that name and password be set. Please check your settings!' )
  }

  mongodb::db { $name:
    user     => $user,
    password => $password
  }
}

# Begin redis

if $redis_values == undef {
  $redis_values = hiera('redis', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($apache_values, 'install', 1)
  or hash_key_equals($nginx_values, 'install', 1)
{
  $redis_webserver_restart = true
} else {
  $redis_webserver_restart = false
}

if hash_key_equals($redis_values, 'install', 1) {
  create_resources('class', { 'redis' => $redis_values['settings'] })

  if hash_key_equals($php_values, 'install', 1) and ! defined(Php::Pecl::Module['redis']) {
    php::pecl::module { 'redis':
      use_package         => false,
      service_autorestart => $redis_webserver_restart,
      require             => Class['redis']
    }
  }
}

# Begin beanstalkd

if $beanstalkd_values == undef {
  $beanstalkd_values = hiera('beanstalkd', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $hhvm_values == undef {
  $hhvm_values = hiera('hhvm', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($apache_values, 'install', 1) {
  $beanstalk_console_webroot_location = "${puphpet::params::apache_webroot_location}/beanstalk_console"
} elsif hash_key_equals($nginx_values, 'install', 1) {
  $beanstalk_console_webroot_location = "${puphpet::params::nginx_webroot_location}/beanstalk_console"
} else {
  $beanstalk_console_webroot_location = undef
}

if hash_key_equals($php_values, 'install', 1) or hash_key_equals($hhvm_values, 'install', 1) {
  $beanstalkd_php_installed = true
} else {
  $beanstalkd_php_installed = false
}

if hash_key_equals($beanstalkd_values, 'install', 1) {
  create_resources(beanstalkd::config, { 'beanstalkd' => $beanstalkd_values['settings'] })

  if hash_key_equals($beanstalkd_values, 'beanstalk_console', 1)
    and $beanstalk_console_webroot_location != undef
    and $beanstalkd_php_installed
  {
    exec { 'delete-beanstalk_console-path-if-not-git-repo':
      command => "rm -rf ${beanstalk_console_webroot_location}",
      onlyif  => "test ! -d ${beanstalk_console_webroot_location}/.git"
    }

    vcsrepo { $beanstalk_console_webroot_location:
      ensure   => present,
      provider => git,
      source   => 'https://github.com/ptrofimov/beanstalk_console.git',
      require  => Exec['delete-beanstalk_console-path-if-not-git-repo']
    }

    file { "${beanstalk_console_webroot_location}/storage.json":
      ensure  => present,
      group   => 'www-data',
      mode    => 0775,
      require => Vcsrepo[$beanstalk_console_webroot_location]
    }
  }
}

# Begin rabbitmq

if $rabbitmq_values == undef {
  $rabbitmq_values = hiera('rabbitmq', false)
} if $php_values == undef {
  $php_values = hiera('php', false)
} if $apache_values == undef {
  $apache_values = hiera('apache', false)
} if $nginx_values == undef {
  $nginx_values = hiera('nginx', false)
}

if hash_key_equals($apache_values, 'install', 1)
  or hash_key_equals($nginx_values, 'install', 1)
{
  $rabbitmq_webserver_restart = true
} else {
  $rabbitmq_webserver_restart = false
}

if hash_key_equals($rabbitmq_values, 'install', 1) {
  create_resources('class', { 'rabbitmq' => $rabbitmq_values['settings'] })

  if hash_key_equals($php_values, 'install', 1) and ! defined(Php::Pecl::Module['amqp']) {
    php::pecl::module { 'amqp':
      use_package         => false,
      service_autorestart => $rabbitmq_webserver_restart,
      require             => Class['rabbitmq']
    }
  }
}

