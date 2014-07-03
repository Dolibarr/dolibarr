#
# Class puppi::skel
#
# Creates the base Puppi dirs
#
class puppi::skel {

  require puppi::params

  file { 'puppi_basedir':
    ensure  => directory,
    path    => $puppi::params::basedir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
  }

  file { 'puppi_checksdir':
    ensure  => directory,
    path    => $puppi::params::checksdir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
    recurse => true,
    purge   => true,
    force   => true,
  }

  file { 'puppi_logsdir':
    ensure  => directory,
    path    => $puppi::params::logsdir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
    recurse => true,
    purge   => true,
    force   => true,
  }

  file { 'puppi_helpersdir':
    ensure  => directory,
    path    => $puppi::params::helpersdir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
  }

  file { 'puppi_infodir':
    ensure  => directory,
    path    => $puppi::params::infodir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
    recurse => true,
    purge   => true,
    force   => true,
  }

  file { 'puppi_tododir':
    ensure  => directory,
    path    => $puppi::params::tododir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
    recurse => true,
    purge   => true,
    force   => true,
  }

  file { 'puppi_projectsdir':
    ensure  => directory,
    path    => $puppi::params::projectsdir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
    recurse => true,
    purge   => true,
    force   => true,
  }

  file { 'puppi_datadir':
    ensure  => directory,
    path    => $puppi::params::datadir,
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
    recurse => true,
    purge   => true,
    force   => true,
  }

  file { 'puppi_workdir':
    ensure  => directory,
    path    => $puppi::params::workdir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
  }

  file { 'puppi_archivedir':
    ensure  => directory,
    path    => $puppi::params::archivedir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_libdir'],
  }

  file { 'puppi_readmedir':
    ensure  => directory,
    path    => $puppi::params::readmedir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_libdir'],
  }

  file { 'puppi_libdir':
    ensure  => directory,
    path    => $puppi::params::libdir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
  }

  file { 'puppi_logdir':
    ensure  => directory,
    path    => $puppi::params::logdir,
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    require => File['puppi_basedir'],
  }

  # MailPuppiCheck script
  file { '/usr/bin/mailpuppicheck':
    ensure  => 'present',
    mode    => '0750',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    source  => 'puppet:///modules/puppi/mailpuppicheck',
  }

  # Puppi common scripts
  file { 'puppi.scripts':
    ensure  => present,
    path    => "${puppi::params::scriptsdir}/",
    mode    => '0755',
    owner   => $puppi::params::configfile_owner,
    group   => $puppi::params::configfile_group,
    source  => 'puppet:///modules/puppi/scripts/',
    recurse => true,
#   purge   => true,
    ignore  => '.svn',
  }

  # Logs cleanup script
  if $::kernel == 'Linux' {
    if $puppi::logs_retention_days
    and $puppi::logs_retention_days != '0' {
      $purge_cron_ensure = 'present'
    } else {
      $purge_cron_ensure = 'absent'
    }

    file { 'puppi_cron_logs_purge':
      ensure  => $purge_cron_ensure,
      path    => '/etc/cron.daily/puppi_clean',
      mode    => '0755',
      owner   => 'root',
      group   => 'root',
      content => template('puppi/puppi_clean.erb'),
    }
  } 
}
