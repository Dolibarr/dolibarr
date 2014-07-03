# = Define puppi::project::mysql
#
# This is a shortcut define to build a puppi project for the
# management of mysql queries, contained in the .sql file defined
# as $source
#
# == Variables:
#
# [*source*]
#   The full URL of the main sql file to retrieve.
#   Format should be in URI standard (http:// file:// ssh:// rsync://).
#
# [*mysql_database*]
#   (Required) - The Mysql database to work on
#   Default: root
#
# [*mysql_user*]
#   (Optional) - The Mysql user to be used to run the queries
#   Default: root
#
# [*mysql_password*]
#   (Optional) - The password to use for the specified mysql user
#   Default: '' (blank)
#
# [*mysql_host*]
#   (Optional) - The Mysql server. Use ip or hostname
#   Default: localhost
#
# [*init_source*]
#   (Optional) - The full URL to be used to retrieve a sql file to
#   process only for the first time,
#
# [*predeploy_customcommand*]
#   (Optional) -  Full path with arguments of an eventual custom command to
#   execute before the deploy. The command is executed as $predeploy_user.
#
# [*predeploy_user*]
#   (Optional) - The user to be used to execute the $predeploy_customcommand.
#   By default is the same of $user.
#
# [*predeploy_priority*]
#   (Optional) - The priority (execution sequence number) that defines when,
#   during the deploy procedure, the $predeploy_customcommand is executed
#   Default: 39 (immediately before the copy of files on the deploy root).
#
# [*postdeploy_customcommand*]
#   (Optional) -  Full path with arguments of an eventual custom command to
#   execute after the deploy. The command is executed as $postdeploy_user.
#
# [*postdeploy_user*]
#   (Optional) - The user to be used to execute the $postdeploy_customcommand.
#   By default is the same of $user.
#
# [*postdeploy_priority*]
#   (Optional) - The priority (execution sequence number) that defines when,
#   during the deploy procedure, the $postdeploy_customcommand is executed
#   Default: 41 (immediately after the copy of files on the deploy root).
#
# [*disable_services*]
#   (Optional) - The names (space separated) of the services you might want to
#   stop during deploy. By default is blank. Example: "apache puppet monit".
#
# [*firewall_src_ip*]
#   (Optional) - The IP address of a loadbalancer you might want to block out
#   during a deploy.
#
# [*firewall_dst_port*]
#   (Optional) - The local port to block from the loadbalancer during deploy
#   (Default all).
#
# [*firewall_delay*]
#   (Optional) - A delay time in seconds to wait after the block of
#   $firewall_src_ip. Should be at least as long as the loadbalancer check
#   interval for the services stopped during deploy (Default: 1).
#
# [*report_email*]
#   (Optional) - The (space separated) email(s) to notify of deploy/rollback
#   operations. If none is specified, no email is sent.
#
# [*backup*]
#   (Optional) - If and how backups of existing databases are made.
#   Default: true
#
# [*backup_retention*]
#   (Optional) - Number of backup archives to keep. (Default 5).
#   Lower the default value if your backups are too large and may fill up the
#   filesystem.
#
# [*run_checks*]
#   (Optional) - If you want to run local puppi checks before and after the
#   deploy procedure. Default: "true".
#
# [*always_deploy*]
#   (Optional) - If you always deploy what has been downloaded. Default="yes",
#   if set to "no" a checksum is made between the files previously downloaded
#   and the new files. If they are the same the deploy is not done.
#
# [*auto_deploy*]
#   (Optional) - If you want to automatically run this puppi deploy when
#   Puppet runs. Default: 'false'
#
define puppi::project::mysql (
  $source,
  $mysql_database,
  $mysql_user               = 'root',
  $mysql_host               = 'localhost',
  $mysql_password           = '',
  $init_source              = '',
  $predeploy_customcommand  = '',
  $predeploy_user           = '',
  $predeploy_priority       = '39',
  $postdeploy_customcommand = '',
  $postdeploy_user          = '',
  $postdeploy_priority      = '41',
  $disable_services         = '',
  $firewall_src_ip          = '',
  $firewall_dst_port        = '0',
  $firewall_delay           = '1',
  $report_email             = '',
  $backup                   = true,
  $backup_retention         = '5',
  $run_checks               = true,
  $always_deploy            = true,
  $auto_deploy              = false,
  $enable                   = true ) {

  require puppi
  require puppi::params

  # Set default values
  $predeploy_real_user = $predeploy_user ? {
    ''      => 'root',
    default => $predeploy_user,
  }

  $postdeploy_real_user = $postdeploy_user ? {
    ''      => 'root',
    default => $postdeploy_user,
  }

  $init_real_source = $init_source ? {
    ''      => $source,
    default => $init_source,
  }

  $real_always_deploy = any2bool($always_deploy) ? {
    false   => 'no',
    true    => 'yes',
  }

  $bool_run_checks = any2bool($run_checks)
  $bool_backup = any2bool($backup)
  $real_source_type = 'mysql'
  $bool_auto_deploy = any2bool($auto_deploy)

  $source_filename = url_parse($source,'filename')

### CREATE PROJECT
    puppi::project { $name:
      enable => $enable ,
    }


### INIT SEQUENCE
  if ($init_source != '') {
    puppi::initialize { "${name}-Deploy_Files":
      priority  => '40' ,
      command   => 'get_file.sh' ,
      arguments => "-s ${init_source} -t ${real_source_type}" ,
      user      => root ,
      project   => $name ,
      enable    => $enable ,
    }
    puppi::initialize { "${name}-Run_SQL":
      priority  => '42' ,
      command   => 'database.sh' ,
      arguments => "-t mysql -a run -u ${mysql_user} -p '${mysql_password}' -d ${mysql_database} -h ${mysql_host}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }


### DEPLOY SEQUENCE
  if ($bool_run_checks == true) {
    puppi::deploy { "${name}-Run_PRE-Checks":
      priority  => '10' ,
      command   => 'check_project.sh' ,
      arguments => $name ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

    puppi::deploy { "${name}-Retrieve_SQLFile":
      priority  => '20' ,
      command   => 'get_file.sh' ,
      arguments => "-s ${source} -t ${real_source_type} -a ${real_always_deploy}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

  if ($firewall_src_ip != '') {
    puppi::deploy { "${name}-Load_Balancer_Block":
      priority  => '25' ,
      command   => 'firewall.sh' ,
      arguments => "${firewall_src_ip} ${firewall_dst_port} on ${firewall_delay}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($bool_backup == true) {
    puppi::deploy { "${name}-Backup_Database":
      priority  => '30' ,
      command   => 'database.sh' ,
      arguments => "-t mysql -a dump -u ${mysql_user} -p '${mysql_password}' -d ${mysql_database} -h ${mysql_host}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($disable_services != '') {
    puppi::deploy { "${name}-Disable_extra_services":
      priority  => '36' ,
      command   => 'service.sh' ,
      arguments => "stop ${disable_services}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($predeploy_customcommand != '') {
    puppi::deploy { "${name}-Run_Custom_PreDeploy_Script":
      priority  => $predeploy_priority ,
      command   => 'execute.sh' ,
      arguments => $predeploy_customcommand ,
      user      => $predeploy_real_user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

    # Here is done the db change
    puppi::deploy { "${name}-Run_SQL":
      priority  => '40' ,
      command   => 'database.sh' ,
      arguments => "-t mysql -a run -u ${mysql_user} -p '${mysql_password}' -d ${mysql_database} -h ${mysql_host}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

  if ($postdeploy_customcommand != '') {
    puppi::deploy { "${name}-Run_Custom_PostDeploy_Script":
      priority  => $postdeploy_priority ,
      command   => 'execute.sh' ,
      arguments => $postdeploy_customcommand ,
      user      => $postdeploy_real_user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($disable_services != '') {
    puppi::deploy { "${name}-Enable_extra_services":
      priority  => '44' ,
      command   => 'service.sh' ,
      arguments => "start ${disable_services}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($firewall_src_ip != '') {
    puppi::deploy { "${name}-Load_Balancer_Unblock":
      priority  => '46' ,
      command   => 'firewall.sh' ,
      arguments => "${firewall_src_ip} ${firewall_dst_port} off 0" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($bool_run_checks == true) {
    puppi::deploy { "${name}-Run_POST-Checks":
      priority  => '80' ,
      command   => 'check_project.sh' ,
      arguments => $name ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }


### ROLLBACK PROCEDURE

  if ($firewall_src_ip != '') {
    puppi::rollback { "${name}-Load_Balancer_Block":
      priority  => '25' ,
      command   => 'firewall.sh' ,
      arguments => "${firewall_src_ip} ${firewall_dst_port} on ${firewall_delay}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($bool_backup == true) {
    puppi::rollback { "${name}-Backup_Database_PreRollback":
      priority  => '30' ,
      command   => 'database.sh' ,
      arguments => "-t mysql -a dump -u ${mysql_user} -p '${mysql_password}' -d ${mysql_database} -h ${mysql_host}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($disable_services != '') {
    puppi::rollback { "${name}-Disable_extra_services":
      priority  => '37' ,
      command   => 'service.sh' ,
      arguments => "stop ${disable_services}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($predeploy_customcommand != '') {
    puppi::rollback { "${name}-Run_Custom_PreDeploy_Script":
      priority  => $predeploy_priority ,
      command   => 'execute.sh' ,
      arguments => $predeploy_customcommand ,
      user      => $predeploy_real_user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($bool_backup == true) {
    puppi::rollback { "${name}-Recover_Database":
      priority  => '40' ,
      command   => 'database.sh' ,
      arguments => "-t mysql -a restore -u ${mysql_user} -p '${mysql_password}' -d ${mysql_database} -h ${mysql_host}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($postdeploy_customcommand != '') {
    puppi::rollback { "${name}-Run_Custom_PostDeploy_Script":
      priority  => $postdeploy_priority ,
      command   => 'execute.sh' ,
      arguments => $postdeploy_customcommand ,
      user      => $postdeploy_real_user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($disable_services != '') {
    puppi::rollback { "${name}-Enable_extra_services":
      priority  => '44' ,
      command   => 'service.sh' ,
      arguments => "start ${disable_services}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($firewall_src_ip != '') {
    puppi::rollback { "${name}-Load_Balancer_Unblock":
      priority  => '46' ,
      command   => 'firewall.sh' ,
      arguments => "${firewall_src_ip} ${firewall_dst_port} off 0" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($bool_run_checks == true) {
    puppi::rollback { "${name}-Run_POST-Checks":
      priority  => '80' ,
      command   => 'check_project.sh' ,
      arguments => $name ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }


### REPORTING

  if ($report_email != '') {
    puppi::report { "${name}-Mail_Notification":
      priority  => '20' ,
      command   => 'report_mail.sh' ,
      arguments => $report_email ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

### AUTO DEPLOY DURING PUPPET RUN
  if ($bool_auto_deploy == true) {
    puppi::run { $name: }
  }

}
