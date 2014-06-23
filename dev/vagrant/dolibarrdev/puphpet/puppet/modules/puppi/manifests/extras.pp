# Class puppi::extras
#
# Default extras class with predefined puppi
# check, log , info content.
# You can provide a custom extra class to use instead of this
# with a parameter like:
#   extra_class=> 'example42::puppi::extras',
#
class puppi::extras {

  # Default Checks

  puppi::check { 'NTP_Sync':
    command  => "check_ntp -H ${puppi::params::ntp}" ,
    priority => '99' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Disks_Usage':
    command  => 'check_disk -w 20% -c 10% -L -X tmpfs' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'System_Load':
    command  => 'check_load -w 15,10,5 -c 30,25,20' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Zombie_Processes':
    command  => 'check_procs -w 5 -c 10 -s Z' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Local_Mail_Queue':
    command  => 'check_mailq -w 2 -c 5' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'Connected_Users':
    command  => 'check_users -w 5 -c 10' ,
    priority => '10' ,
    hostwide => 'yes' ,
  }

  puppi::check { 'DNS_Resolution':
    command  => 'check_dns -H example.com' ,
    priority => '15' ,
    hostwide => 'yes' ,
  }


  # Info Pages
  $network_run = $::operatingsystem ? {
    Solaris => [ 'ifconfig -a' , 'netstat -nr' , 'cat /etc/resolv.conf' , 'arp -an' , 'netstat -na' ],
    default => [ 'ifconfig' , 'route -n' , 'cat /etc/resolv.conf' , 'arp -an' , 'netstat -natup | grep LISTEN' ],
  }

  puppi::info { 'network':
    description => 'Network settings and stats' ,
    run         => $network_run,
  }

  $users_run = $::operatingsystem ? {
    Solaris => [ 'who' , 'last' ],
    default => [ 'who' , 'last' , 'LANG=C lastlog | grep -v \'Never logged in\'' ],
  }

  puppi::info { 'users':
    description => 'Users and logins information' ,
    run         => $users_run,
  }

  $perf_run = $::operatingsystem ? {
    Solaris => [ 'uptime' , 'vmstat 1 5' ],
    default => [ 'uptime' , 'free' , 'vmstat 1 5' ],
  }

  puppi::info { 'perf':
    description => 'System performances and resources utilization' ,
    run         => $perf_run,
  }

  $disks_run = $::operatingsystem ? {
    Solaris => [ 'df -h' , 'mount' ],
    default => [ 'df -h' , 'mount' , 'blkid' , 'fdisk -l' ],
  }

  puppi::info { 'disks':
    description => 'Disks and filesystem information' ,
    run         => $disks_run,
  }

  $hardware_run = $::operatingsystem ? {
    Solaris => [ 'find /devices/' ],
    default => [ 'lspci' , 'cat /proc/cpuinfo' ],
  }

  puppi::info { 'hardware':
    description => 'Hardware information' ,
    run         => $hardware_run,
  }

  $packages_run = $::operatingsystem ? {
    /(?i:RedHat|CentOS|Scientific|Amazon|Linux)/ => [ 'yum repolist' , 'rpm -qa' ] ,
    /(?i:Debian|Ubuntu|Mint)/                    => [ 'apt-config dump' , 'apt-cache stats' , 'apt-key list' , 'dpkg -l' ],
    /(Solaris)/                                  => [ 'pkginfo' ],
    /(Archlinux)/                                => [ 'pacman -Qet' ],
    default                                      => [ 'echo' ],
  }

  puppi::info { 'packages':
    description => 'Packages information' ,
    run         => $packages_run,
  }

  puppi::info::module { 'puppi':
    configfile  => ["${puppi::params::basedir}/puppi.conf"],
    configdir   => [$puppi::params::basedir],
    datadir     => [$puppi::params::archivedir],
    logdir      => [$puppi::params::logdir],
    description => 'What Puppet knows about puppi' ,
    verbose     => 'yes',
#   run         => "ls -lR ${puppi::params::logdir}/puppi-data/",
  }

  ### Default Logs
  case $::operatingsystem {

    Debian,Ubuntu: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => ['/var/log/syslog'],
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => ['/var/log/user.log','/var/log/auth.log'],
      }
      puppi::log { 'mail':
        description => 'Mail messages',
        log         => ['/var/log/mail.log'],
      }
    }

    RedHat,CentOS,Scientific,Amazon,Linux: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => ['/var/log/messages'],
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => ['/var/log/secure'],
      }
      puppi::log { 'mail':
        description => 'Mail messages',
        log         => ['/var/log/maillog'],
      }
    }

    SLES,OpenSuSE: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => ['/var/log/messages'],
      }
      puppi::log { 'mail':
        description => 'Mail messages',
        log         => ['/var/log/mail'],
      }
      puppi::log { 'zypper':
        description => 'Zypper messages',
        log         => ['/var/log/zypper.log'],
      }
    }

    Solaris: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => ['/var/adm/messages'],
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => ['/var/log/authlog'],
      }
    }

    Archlinux: {
      puppi::log { 'system':
        description => 'General System Messages',
        log         => ['/var/log/messages.log','/var/log/syslog.log'],
      }
      puppi::log { 'auth':
        description => 'Users and authentication',
        log         => ['/var/log/user.log','/var/log/auth.log'],
      }
      puppi::log { 'mail':
        description => 'Mail messages',
        log         => ['/var/log/mail.log'],
      }
    }

    default: { }

  }

}
