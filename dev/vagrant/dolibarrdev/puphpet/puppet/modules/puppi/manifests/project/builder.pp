# = Define puppi::project::builder
#
# This is a shortcut define to build a puppi project for the deploy of web
# applications based on different sources: a war file, a tar file, a source dir,
# a list of files or a nexus maven repository
# It uses different "core" defines (puppi::project, puppi:deploy (many),
# puppi::rollback (many)) to build a full featured template project for
# automatic deployments.
# If you need to customize it, either change the template defined here or
# build up your own custom ones.
#
# == Variables:
#
# [*source*]
#   The full URL of the main file to retrieve.
#   Format should be in URI standard (http:// file:// ssh:// rsync://).
#
# [*source_type*]
#   The type of file that is retrieved. Accepted values: tarball, zip, list,
#   war, dir, maven-metadata.
#
# [*deploy_root*]
#   The destination directory where the retrieved file(s) are deployed.
#
# [*init_source*]
#   (Optional) - The full URL to be used to retrieve, for the first time,
#   the project files. They are copied directly to the $deploy_root
#   Format should be in URI standard (http:// file:// ssh:// svn://).
#
# [*magicfix*]
#   (Optional) - A string that is used as prefix or suffix according to the
#   context and the scripts used in the deploy procedure.
#
# [*user*]
#   (Optional) - The user to be used for deploy operations.
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
#   (Optional) - How backups of files are made. Default: "full". Options:
#   "full" - Make full backup of the deploy_root before making the deploy
#   "diff" - Backup only the files that are going to be deployed. Note that
#     in order to make reliable rollbacks of versions older that the latest
#     you've to individually rollback every intermediate deploy
#   "false" - Do not make backups. This disables the option to make rollbacks
#
# [*backup_rsync_options*]
#   (Optional) - The extra options to pass to rsync for backup operations. Use
#   it, for example, to exclude directories that you don't want to archive.
#   IE: "--exclude .snapshot --exclude cache --exclude www/cache".
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
# == Usage
# A sample deploy of a zip with custom postdeploy command and mail notification
# puppi::project::builder { "cms":
#   source                   => "http://repo.example42.com/deploy/cms/cms.zip",
#   source_type              => "zip",
#   user                     => "root",
#   deploy_root              => "/var/www",
#   postdeploy_customcommand => "chown -R www-data /var/www/files",
#   postdeploy_user          => "root",
#   postdeploy_priority      => "41",
#   report_email             => "sysadmins@example42.com",
#   enable                   => "true",
# }
#
# [*auto_deploy*]
#   (Optional) - If you want to automatically run this puppi deploy when
#   Puppet runs. Default: 'false'
#
define puppi::project::builder (
  $source,
  $source_type,
  $deploy_root,
  $init_source              = '',
  $user                     = 'root',
  $magicfix                 = '',
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
  $backup                   = 'full',
  $backup_rsync_options     = '--exclude .snapshot',
  $backup_retention         = '5',
  $run_checks               = true,
  $always_deploy            = true,
  $auto_deploy              = false,
  $enable                   = true ) {

  require puppi
  require puppi::params

  # Autoinclude the puppi class
  include puppi

  # Set default values
  $predeploy_real_user = $predeploy_user ? {
    ''      => $user,
    default => $predeploy_user,
  }

  $postdeploy_real_user = $postdeploy_user ? {
    ''      => $user,
    default => $postdeploy_user,
  }

  $real_source_type = $source_type ? {
    'dir'            => 'dir',
    'tarball'        => 'tarball',
    'zip'            => 'zip',
    'gz'             => 'gz',
    'maven-metadata' => 'maven-metadata',
    'maven'          => 'maven-metadata',
    'war'            => 'war',
    'list'           => 'list',
  }

  $real_always_deploy = any2bool($always_deploy) ? {
    false   => 'no',
    true    => 'yes',
  }

  $bool_run_checks = any2bool($run_checks)
  $bool_auto_deploy = any2bool($auto_deploy)

  $source_filename = url_parse($source,'filename')

# Create Project
  puppi::project { $name: enable => $enable }


### INIT SEQUENCE
  if ($init_source != '') {
    puppi::initialize { "${name}-Deploy_Files":
      priority  => '40' ,
      command   => 'get_file.sh' ,
      arguments => "-s ${init_source} -d ${deploy_root}" ,
      user      => $user ,
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

  # Here source file is retrieved
  puppi::deploy { "${name}-Retrieve_SourceFile":
    priority  => '20' ,
    command   => 'get_file.sh' ,
    arguments => "-s ${source} -t ${real_source_type} -a ${real_always_deploy}" ,
    user      => 'root' ,
    project   => $name ,
    enable    => $enable ,
  }

  $args_magicfix = $magicfix ? {
    ''      => '',
    default => "-m ${magicfix}" ,
  }

  if ($real_source_type == 'tarball') {
    puppi::deploy { "${name}-PreDeploy_Tar":
      priority  => '25' ,
      command   => 'predeploy.sh' ,
      arguments => $args_magicfix,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($real_source_type == 'zip') {

    puppi::deploy { "${name}-PreDeploy_Zip":
      priority  => '25' ,
      command   => 'predeploy.sh' ,
      arguments => $args_magicfix,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($real_source_type == 'list') {
    puppi::deploy { "${name}-Extract_File_Metadata":
      priority  => '22' ,
      command   => 'get_metadata.sh' ,
      arguments => $args_magicfix,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

    $clean_file_list_magicfix = $magicfix ? {
      ''      => '',
      default => $magicfix,
    }

    puppi::deploy { "${name}-Clean_File_List":
      priority  => '24' ,
      command   => 'clean_filelist.sh' ,
      arguments => $clean_file_list_magicfix,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
    puppi::deploy { "${name}-Retrieve_Files":
      priority  => '25' ,
      command   => 'get_filesfromlist.sh' ,
      arguments => $source ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($backup == 'full') or ($backup == 'diff') {
    puppi::deploy { "${name}-Backup_existing_Files":
      priority  => '30' ,
      command   => 'archive.sh' ,
      arguments => "-b ${deploy_root} -m ${backup} -o '${backup_rsync_options}' -n ${backup_retention}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($firewall_src_ip != '') {
    puppi::deploy { "${name}-Load_Balancer_Block":
      priority  => '34' ,
      command   => 'firewall.sh' ,
      arguments => "${firewall_src_ip} ${firewall_dst_port} on ${firewall_delay}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($real_source_type == 'war') {
    puppi::deploy { "${name}-Remove_existing_WAR":
      priority  => '35' ,
      command   => 'delete.sh' ,
      arguments => "${deploy_root}/${source_filename}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
    puppi::deploy { "${name}-Check_undeploy":
      priority  => '36' ,
      command   => 'checkwardir.sh' ,
      arguments => "-a ${deploy_root}/${source_filename}" ,
      user      => $user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($disable_services != '') {
    puppi::deploy { "${name}-Disable_extra_services":
      priority  => '37' ,
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

    # Here is done the deploy on $deploy_root
    puppi::deploy { "${name}-Deploy":
      priority  => '40' ,
      command   => 'deploy.sh' ,
      arguments => $deploy_root ,
      user      => $user ,
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

  if ($real_source_type == 'war') {
    puppi::deploy { "${name}-Check_deploy":
      priority  => '45' ,
      command   => 'checkwardir.sh' ,
      arguments => "-p ${deploy_root}/${source_filename}" ,
      user      => $user ,
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
      priority  => '34' ,
      command   => 'firewall.sh' ,
      arguments => "${firewall_src_ip} ${firewall_dst_port} on ${firewall_delay}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($real_source_type == 'war') {
    puppi::rollback { "${name}-Remove_existing_WAR":
      priority  => '35' ,
      command   => 'delete.sh' ,
      arguments => "${deploy_root}/${source_filename}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
    puppi::rollback { "${name}-Check_undeploy":
      priority  => '36' ,
      command   => 'checkwardir.sh' ,
      arguments => "-a ${deploy_root}/${source_filename}" ,
      user      => $user ,
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

  if ($backup == 'full') or ($backup == 'diff') {
    puppi::rollback { "${name}-Recover_Files_To_Deploy":
      priority  => '40' ,
      command   => 'archive.sh' ,
      arguments => "-r ${deploy_root} -m ${backup} -o '${backup_rsync_options}'" ,
      user      => $user ,
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

  if ($real_source_type == 'war') {
    puppi::rollback { "${name}-Check_deploy":
      priority  => '45' ,
      command   => 'checkwardir.sh' ,
      arguments => "-p ${deploy_root}/${source_filename}" ,
      user      => $user ,
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
