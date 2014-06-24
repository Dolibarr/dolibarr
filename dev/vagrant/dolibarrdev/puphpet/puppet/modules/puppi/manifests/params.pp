# Class: puppi::params
#
# Sets internal variables and defaults for puppi module
#
class puppi::params  {

## PARAMETERS
  $version              = '1'
  $install_dependencies = true
  $template             = 'puppi/puppi.conf.erb'
  $helpers_class        = 'puppi::helpers'
  $logs_retention_days  = '30'
  $extra_class          = 'puppi::extras'


## INTERNALVARS
  $basedir     = '/etc/puppi'
  $scriptsdir  = '/etc/puppi/scripts'
  $checksdir   = '/etc/puppi/checks'
  $logsdir     = '/etc/puppi/logs'
  $infodir     = '/etc/puppi/info'
  $tododir     = '/etc/puppi/todo'
  $projectsdir = '/etc/puppi/projects'
  $datadir     = '/etc/puppi/data'
  $helpersdir  = '/etc/puppi/helpers'
  $libdir      = '/var/lib/puppi'
  $readmedir   = '/var/lib/puppi/readme'
  $logdir      = '/var/log/puppi'

  $archivedir = $::puppi_archivedir ? {
    ''      => '/var/lib/puppi/archive',
    default => $::puppi_archivedir,
  }

  $workdir = $::puppi_workdir ? {
    ''      => '/tmp/puppi',
    default => $::puppi_workdir,
  }

  $configfile_mode  = '0644'
  $configfile_owner = 'root'
  $configfile_group = 'root'

# External tools
# Directory where are placed the checks scripts
# By default we use Nagios plugins
  $checkpluginsdir = $::operatingsystem ? {
    /(?i:RedHat|CentOS|Scientific|Amazon|Linux)/ => $::architecture ? {
      x86_64  => '/usr/lib64/nagios/plugins',
      default => '/usr/lib/nagios/plugins',
    },
    default                    => '/usr/lib/nagios/plugins',
  }

  $package_nagiosplugins = $::operatingsystem ? {
    /(?i:RedHat|CentOS|Scientific|Amazon|Linux|Fedora)/ => 'nagios-plugins-all',
    default                       => 'nagios-plugins',
  }

  $package_mail = $::operatingsystem ? {
    /(?i:Debian|Ubuntu|Mint)/ => 'bsd-mailx',
    default           => 'mailx',
  }

  $ntp = $::ntp_server ? {
    ''    => 'pool.ntp.org' ,
    default => is_array($::ntp_server) ? {
      false   => $::ntp_server,
      true  => $::ntp_server[0],
      default => $::ntp_server,
    }
  }

# Mcollective paths
# TODO: Add Paths for Puppet Enterprise:
# /opt/puppet/libexec/mcollective/mcollective/
  $mcollective = $::operatingsystem ? {
    debian  => '/usr/share/mcollective/plugins/mcollective',
    ubuntu  => '/usr/share/mcollective/plugins/mcollective',
    centos  => '/usr/libexec/mcollective/mcollective',
    redhat  => '/usr/libexec/mcollective/mcollective',
    default => '/usr/libexec/mcollective/mcollective',
  }

  $mcollective_user = 'root'
  $mcollective_group = 'root'


# Commands used in puppi info templates
  $info_package_query = $::operatingsystem ? {
    /(?i:RedHat|CentOS|Scientific|Amazon|Linux)/ => 'rpm -qi',
    /(?i:Ubuntu|Debian|Mint)/          => 'dpkg -s',
    default                    => 'echo',
  }
  $info_package_list = $::operatingsystem ? {
    /(?i:RedHat|CentOS|Scientific|Amazon|Linux)/ => 'rpm -ql',
    /(?i:Ubuntu|Debian|Mint)/                    => 'dpkg -L',
    default                                      => 'echo',
  }
  $info_service_check = $::operatingsystem ? {
    default => '/etc/init.d/',
  }

}
