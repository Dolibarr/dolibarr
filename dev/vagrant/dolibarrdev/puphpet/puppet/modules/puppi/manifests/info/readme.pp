# Define puppi::info::readme
#
# This is a puppi info plugin that provides a Readme text which can be
# used to show local info on the managed server and eventually run custom commands.
#
#  puppi::info::readme { "myapp":
#    description => "Guidelines for myapp setup",
#    readme => "myapp/readme.txt" ,
#    run     => "myapp -V",
#  }
#
define puppi::info::readme (
  $description       = '',
  $readme            = '',
  $autoreadme        = 'no',
  $run               = '',
  $source_module     = 'undefined',
  $templatefile      = 'puppi/info/readme.erb' ) {

  require puppi
  require puppi::params

  $bool_autoreadme = any2bool($autoreadme)

  file { "${puppi::params::infodir}/${name}":
    ensure  => present,
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => Class['puppi'],
    content => template($templatefile),
    tag     => 'puppi_info',
  }

  $readme_source = $readme ? {
    ''      => 'puppet:///modules/puppi/info/readme/readme',
    default => $readme,
  }

  file { "${puppi::params::readmedir}/${name}":
    ensure  => present,
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_readmedir'],
    source  => $readme_source,
    tag     => 'puppi_info',
  }

  if $bool_autoreadme == true {
  file { "${puppi::params::readmedir}/${name}-custom":
    ensure  => present,
    mode    => '0644',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_readmedir'],
    source  => [
      "puppet:///modules/${source_module}/puppi/info/readme/readme-${::hostname}" ,
      "puppet:///modules/${source_module}/puppi/info/readme/readme-${::role}" ,
      "puppet:///modules/${source_module}/puppi/info/readme/readme-default" ,
      "puppet:///modules/puppi/info/readme/readme-${::hostname}" ,
      "puppet:///modules/puppi/info/readme/readme-${::role}" ,
      'puppet:///modules/puppi/info/readme/readme-default'
    ],
    tag     => 'puppi_info',
    }
  }

}
