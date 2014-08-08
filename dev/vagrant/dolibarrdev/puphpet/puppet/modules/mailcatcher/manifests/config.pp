# class mailcatcher::config
#
class mailcatcher::config {
  user { 'mailcatcher':
    ensure  => present,
    comment => 'Mailcatcher Mock Smtp Service User',
    home    => '/var/spool/mailcatcher',
    shell   => '/bin/true',
  }

  file { $mailcatcher::params::log_path:
    ensure  => directory,
    owner   => 'mailcatcher',
    group   => 'mailcatcher',
    mode    => 0755,
    require => User['mailcatcher']
  }
}
