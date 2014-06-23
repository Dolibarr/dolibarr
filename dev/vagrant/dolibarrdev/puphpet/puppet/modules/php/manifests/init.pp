# = Class: php
#
# This is the main php class
#
#
# == Parameters
#
# Module specific parameters
# [*package_devel*]
#   Name of the php-devel package
#
# [*package_pear*]
#   Name of the php-pear package
#
# Standard class parameters
# Define the general class behaviour and customizations
#
# [*my_class*]
#   Name of a custom class to autoload to manage module's customizations
#   If defined, php class will automatically "include $my_class"
#   Can be defined also by the (top scope) variable $php_myclass
#
# [*service*]
#   The service that runs the php interpreter. Defines what service gets
#   notified. Default: apache2|httpd.
#
# [*source*]
#   Sets the content of source parameter for main configuration file
#   If defined, php main config file will have the param: source => $source
#   Can be defined also by the (top scope) variable $php_source
#
# [*source_dir*]
#   If defined, the whole php configuration directory content is retrieved
#   recursively from the specified source
#   (source => $source_dir , recurse => true)
#   Can be defined also by the (top scope) variable $php_source_dir
#
# [*source_dir_purge*]
#   If set to true (default false) the existing configuration directory is
#   mirrored with the content retrieved from source_dir
#   (source => $source_dir , recurse => true , purge => true, force => true)
#   Can be defined also by the (top scope) variable $php_source_dir_purge
#
# [*template*]
#   Sets the path to the template to use as content for main configuration file
#   If defined, php main config file has: content => content("$template")
#   Note source and template parameters are mutually exclusive: don't use both
#   Can be defined also by the (top scope) variable $php_template
#
# [*augeas*]
#   If set to true (default false), the php.ini will be managed through
#   augeas. This will make php::pecl automatically add extensions to the
#   php.ini.
#   Can be defined also by the (top scope) variable $php_augeas
#
# [*options*]
#   An hash of custom options to be used in templates for arbitrary settings.
#   Can be defined also by the (top scope) variable $php_options
#
# [*version*]
#   The package version, used in the ensure parameter of package type.
#   Default: present. Can be 'latest' or a specific version number.
#   Note that if the argument absent (see below) is set to true, the
#   package is removed, whatever the value of version parameter.
#
# [*absent*]
#   Set to 'true' to remove package(s) installed by module
#   Can be defined also by the (top scope) variable $php_absent
#
# [*puppi*]
#   Set to 'true' to enable creation of module data files that are used by puppi
#   Can be defined also by the (top scope) variables $php_puppi and $puppi
#
# [*puppi_helper*]
#   Specify the helper to use for puppi commands. The default for this module
#   is specified in params.pp and is generally a good choice.
#   You can customize the output of puppi commands for this module using another
#   puppi helper. Use the define puppi::helper to create a new custom helper
#   Can be defined also by the (top scope) variables $php_puppi_helper
#   and $puppi_helper
#
# [*debug*]
#   Set to 'true' to enable modules debugging
#   Can be defined also by the (top scope) variables $php_debug and $debug
#
# [*audit_only*]
#   Set to 'true' if you don't intend to override existing configuration files
#   and want to audit the difference between existing files and the ones
#   managed by Puppet.
#   Can be defined also by the (top scope) variables $php_audit_only
#   and $audit_only
#
# Default class params - As defined in php::params.
# Note that these variables are mostly defined and used in the module itself,
# overriding the default values might not affected all the involved components.
# Set and override them only if you know what you're doing.
# Note also that you can't override/set them via top scope variables.
#
# [*package*]
#   The name of php package
#
# [*config_dir*]
#   Main configuration directory. Used by puppi
#
# [*config_file*]
#   Main configuration file path
#
# [*config_file_mode*]
#   Main configuration file path mode
#
# [*config_file_owner*]
#   Main configuration file path owner
#
# [*config_file_group*]
#   Main configuration file path group
#
# [*config_file_init*]
#   Path of configuration file sourced by init script
#
# [*pid_file*]
#   Path of pid file. Used by monitor
#
# [*data_dir*]
#   Path of application data directory. Used by puppi
#
# [*log_dir*]
#   Base logs directory. Used by puppi
#
# [*log_file*]
#   Log file(s). Used by puppi
#
# == Examples
#
# You can use this class in 2 ways:
# - Set variables (at top scope level on in a ENC) and "include php"
# - Call php as a parametrized class
#
# See README for details.
#
#
class php (
  $package_devel       = params_lookup( 'package_devel' ),
  $package_pear        = params_lookup( 'package_pear' ),
  $my_class            = params_lookup( 'my_class' ),
  $service             = params_lookup( 'service' ),
  $service_autorestart = params_lookup( 'service_autorestart' ),
  $source              = params_lookup( 'source' ),
  $source_dir          = params_lookup( 'source_dir' ),
  $source_dir_purge    = params_lookup( 'source_dir_purge' ),
  $template            = params_lookup( 'template' ),
  $augeas              = params_lookup( 'augeas' ),
  $options             = params_lookup( 'options' ),
  $version             = params_lookup( 'version' ),
  $absent              = params_lookup( 'absent' ),
  $monitor             = params_lookup( 'monitor' , 'global' ),
  $monitor_tool        = params_lookup( 'monitor_tool' , 'global' ),
  $monitor_target      = params_lookup( 'monitor_target' , 'global' ),
  $puppi               = params_lookup( 'puppi' , 'global' ),
  $puppi_helper        = params_lookup( 'puppi_helper' , 'global' ),
  $debug               = params_lookup( 'debug' , 'global' ),
  $audit_only          = params_lookup( 'audit_only' , 'global' ),
  $package             = params_lookup( 'package' ),
  $module_prefix       = params_lookup( 'module_prefix' ),
  $config_dir          = params_lookup( 'config_dir' ),
  $config_file         = params_lookup( 'config_file' ),
  $config_file_mode    = params_lookup( 'config_file_mode' ),
  $config_file_owner   = params_lookup( 'config_file_owner' ),
  $config_file_group   = params_lookup( 'config_file_group' ),
  $config_file_init    = params_lookup( 'config_file_init' ),
  $pid_file            = params_lookup( 'pid_file' ),
  $data_dir            = params_lookup( 'data_dir' ),
  $log_dir             = params_lookup( 'log_dir' ),
  $log_file            = params_lookup( 'log_file' ),
  $port                = params_lookup( 'port' ),
  $protocol            = params_lookup( 'protocol' )
  ) inherits php::params {

  $bool_service_autorestart=any2bool($service_autorestart)
  $bool_source_dir_purge=any2bool($source_dir_purge)
  $bool_augeas=any2bool($augeas)
  $bool_absent=any2bool($absent)
  $bool_monitor=any2bool($monitor)
  $bool_puppi=any2bool($puppi)
  $bool_debug=any2bool($debug)
  $bool_audit_only=any2bool($audit_only)

  ### Definition of some variables used in the module
  $manage_package = $php::bool_absent ? {
    true  => 'absent',
    false => $php::version,
  }

  $manage_file = $php::bool_absent ? {
    true    => 'absent',
    default => 'present',
  }

  if $php::bool_absent == true {
    $manage_monitor = false
  } else {
    $manage_monitor = true
  }

  $manage_audit = $php::bool_audit_only ? {
    true  => 'all',
    false => undef,
  }

  $manage_file_replace = $php::bool_audit_only ? {
    true  => false,
    false => true,
  }

  if ($php::source and $php::template) {
    fail ('PHP: cannot set both source and template')
  }
  if ($php::source and $php::bool_augeas) {
    fail ('PHP: cannot set both source and augeas')
  }
  if ($php::template and $php::bool_augeas) {
    fail ('PHP: cannot set both template and augeas')
  }

  $manage_file_source = $php::source ? {
    ''        => undef,
    default   => $php::source,
  }

  $manage_file_content = $php::template ? {
    ''        => undef,
    default   => template($php::template),
  }

  ### Managed resources
  package { 'php':
    ensure => $php::manage_package,
    name   => $php::package,
  }

  file { 'php.conf':
    ensure  => $php::manage_file,
    path    => $php::config_file,
    mode    => $php::config_file_mode,
    owner   => $php::config_file_owner,
    group   => $php::config_file_group,
    require => Package['php'],
    source  => $php::manage_file_source,
    content => $php::manage_file_content,
    replace => $php::manage_file_replace,
    audit   => $php::manage_audit,
  }

  # The whole php configuration directory can be recursively overriden
  if $php::source_dir {
    file { 'php.dir':
      ensure  => directory,
      path    => $php::config_dir,
      require => Package['php'],
      source  => $php::source_dir,
      recurse => true,
      purge   => $php::bool_source_dir_purge,
      force   => $php::bool_source_dir_purge,
      replace => $php::manage_file_replace,
      audit   => $php::manage_audit,
    }
  }


  ### Include custom class if $my_class is set
  if $php::my_class {
    include $php::my_class
  }


  ### Provide puppi data, if enabled ( puppi => true )
  if $php::bool_puppi == true {
    $classvars=get_class_args()
    puppi::ze { 'php':
      ensure    => $php::manage_file,
      variables => $classvars,
      helper    => $php::puppi_helper,
    }
  }


  ### Debugging, if enabled ( debug => true )
  if $php::bool_debug == true {
    file { 'debug_php':
      ensure  => $php::manage_file,
      path    => "${settings::vardir}/debug-php",
      mode    => '0640',
      owner   => 'root',
      group   => 'root',
      content => inline_template('<%= scope.to_hash.reject { |k,v| k.to_s =~ /(uptime.*|path|timestamp|free|.*password.*|.*psk.*|.*key)/ }.to_yaml %>'),
    }
  }

}
