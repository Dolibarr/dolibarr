# = Class yum::params
#
class yum::params  {

  $install_all_keys = false

  $update = false

  $defaultrepo = true

  $extrarepo = 'epel'

  $clean_repos = false

  $plugins_config_dir = '/etc/yum/pluginconf.d'

  $source_repo_dir = undef

  $repo_dir = '/etc/yum.repos.d'

  $config_dir = '/etc/yum'

  $config_file = '/etc/yum.conf'

  $config_file_mode = '0644'

  $config_file_owner = 'root'

  $config_file_group = 'root'

  $log_file = '/var/log/yum.log'

  # parameters for the auto-update classes cron.pp/updatesd.pp
  $update_disable = false

  $update_template = $::operatingsystemrelease ? {
    /6.*/ => 'yum/yum-cron.erb',
    default => undef,
  }

  # The following two params are for cron.pp only

  $cron_param = ''

  $cron_mailto = ''

  $cron_dotw = '0123456'

  $source = ''
  $source_dir = ''
  $source_dir_purge = false
  $template = ''
  $options = ''
  $absent = false
  $disable = false
  $disableboot = false
  $puppi = false
  $puppi_helper = 'standard'
  $debug = false
  $audit_only = false

}
