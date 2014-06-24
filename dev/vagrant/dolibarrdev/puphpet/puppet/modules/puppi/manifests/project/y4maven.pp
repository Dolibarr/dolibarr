# = Define puppi::project::y4maven
#
# This is a shortcut define to build a puppi project for the
# deploying hybris-production-zips via Maven and published on
# a repository like artifactory.
#
# It uses different "core" defines (puppi::project, puppi:deploy (many),
# puppi::rollback (many)) to build a full featured template project for
# automatic deployments.
# If you need to customize it, either change the template defined here or
# build up your own custom ones.
#
# == Variables:
#
# [*source*]
#   The full URL of the maven-metadata.xml file to retrieve.
#   Format should be in URI standard (http:// file:// ssh:// rsync://).
#
# [*http_password*]
#   The http_password to use for authentication to the source in case of http.
#
# [*http_user*]
#   The http_user to use for authentication to the source in case of http.
#
# [*artifact_type*]
#   The artifact_type to parse the maven-metadata.xml. Either "release" or "latest"
#   Default is "release". With artifactory, don't use the
#   "Maven Snapshot Version Behavior" "unique" for your repository.
#
# [*deploy_root*]
#   The destination directory where file(s) are deployed.
#
# [*user*]
#   (Optional) - The user to be used for deploy operations.
#
# [*war_suffix*]
#   (Optional) - The suffix (Maven qualifier) that might be appended to the war
#
# [*zip_suffix*]
#   (Optional) - The suffix (Maven qualifier) that might be appended to the zip
#
# [*jar_root*]
#   (Optional) - The destination directory where the jar is copied.
#   If set a jar file is searched in Maven
#
# [*jar_user*]
#   (Optional) - The user to be used for deploy operations of the jar
#   (owner of the files deployed in $jar_root)
#
# [*jar_suffix*]
#   (Optional) - The suffix (Maven qualifier) that might be appended to the jar
#
# [*document_root*]
#   (Optional) - The destination directory where the eventual tarball generated
#   via Maven containing static files ("src tar") is unpacked
#
# [*document_suffix*]
#   (Optional) - The suffix (Maven qualifier) that might be appended to the
#   static files tarballs ("src tar")
#
# [*document_init_source*]
#   (Optional) - The full URL to be used to retrieve, for the first time, the
#    project files present in the source tarball. They are copied to the
#    $document_root. Format should be in URI standard (http:// file:// ...)
#
# [*document_user*]
#   (Optional) - The user to be used for deploy operations of the tarball
#   (owner of the files in $document_root)
#
# [*config_root*]
#   (Optional) - The destination directory where the cfg tar is unpacked
#
# [*config_suffix*]
#   (Optional) - The suffix (Maven qualifier) that might be appended to
#   configuration tarballs ("cfg tar")
#
# [*config_init_source*]
#   (Optional) - The full URL to be used to retrieve, for the first time, the
#   project files present in the cfg tar. They are copied to the $config_root.
#   Format should be in URI standard (http:// file:// ssh:// svn://)
#
# [*config_user*]
#   (Optional) - The user to be used for deploy operations of cfg tar
#   (owner of the files in $config_root)
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
# [*init_script*]
#   (Optional - Obsolete) - The name (ex: tomcat) of the init script of your
#   Application server. If you define it, the AS is stopped and then started
#   during deploy. This option is deprecated, you can use $disable_services
#   for the same functionality
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
# [*check_deploy*]
#   (Optional) - Checks if the war is deployed (Default yes). Set to no if
#   you deploy on Jboss or the deployed dir is different for the war filename
#
# [*auto_deploy*]
#   (Optional) - If you want to automatically run this puppi deploy when
#   Puppet runs. Default: 'false'
#
define puppi::project::y4maven (
  $source,
  $http_user                = '',
  $http_password            = '',
  $artifact_type            = 'release',
  $deploy_root              = '',
  $user                     = 'root',
  $war_suffix               = 'suffixnotset',
  $zip_suffix               = 'suffixnotset',
  $jar_root                 = '',
  $jar_user                 = '',
  $jar_suffix               = 'suffixnotset',
  $document_root            = '',
  $document_user            = '',
  $document_suffix          = 'suffixnotset',
  $document_init_source     = '',
  $config_root              = '',
  $config_user              = '',
  $config_suffix            = 'suffixnotset',
  $config_init_source       = '',
  $predeploy_customcommand  = '',
  $predeploy_user           = '',
  $predeploy_priority       = '39',
  $postdeploy_customcommand = '',
  $postdeploy_user          = '',
  $postdeploy_priority      = '41',
  $init_script              = '',
  $disable_services         = '',
  $firewall_src_ip          = '',
  $firewall_dst_port        = '0',
  $firewall_delay           = '1',
  $report_email             = '',
  $backup_rsync_options     = '--exclude .snapshot',
  $backup_retention         = '5',
  $run_checks               = true,
  $always_deploy            = true,
  $check_deploy             = true,
  $auto_deploy              = false,
  $enable                   = true ) {

  require puppi
  require puppi::params

  # Set default values
  $predeploy_real_user = $predeploy_user ? {
    ''      => $user,
    default => $predeploy_user,
  }

  $postdeploy_real_user = $postdeploy_user ? {
    ''      => $user,
    default => $postdeploy_user,
  }

  $config_real_user = $config_user ? {
    ''      => $user,
    default => $config_user,
  }

  $document_real_user = $document_user ? {
    ''      => $user,
    default => $document_user,
  }

  $jar_real_user = $jar_user ? {
    ''      => $user,
    default => $jar_user,
  }

  $real_always_deploy = any2bool($always_deploy) ? {
    false   => 'no',
    true    => 'yes',
  }

  $bool_run_checks = any2bool($run_checks)
  $bool_check_deploy = any2bool($check_deploy)
  $bool_auto_deploy = any2bool($auto_deploy)


### CREATE PROJECT
    puppi::project { $name:
      enable => $enable ,
    }


### INIT SEQUENCE
  if ($document_init_source != '') {
    puppi::initialize { "${name}-Deploy_Files":
      priority  => '40' ,
      command   => 'get_file.sh' ,
      arguments => "-s ${document_init_source} -d ${deploy_root}" ,
      user      => $document_real_user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($config_init_source != '') {
    puppi::initialize { "${name}-Deploy_CFG_Files":
      priority  => '40' ,
      command   => 'get_file.sh' ,
      arguments => "-s ${config_init_source} -d ${deploy_root}" ,
      user      => $config_real_user ,
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

    # First: hybrisServer-Platform
    $platform_metadata_arguments = $http_password ? {
      '' => "-s ${source}/hybrisServer-Platform/maven-metadata.xml -t maven-metadata -a ${real_always_deploy}" ,
      default => "-s ${source}/hybrisServer-Platform/maven-metadata.xml -t maven-metadata -a ${real_always_deploy} -u ${http_user} -p ${http_password}"
    }

    puppi::deploy { "${name}-Get_Maven_Metadata_hybrisServer-Platform_File":
      priority  => '20' ,
      command   => 'get_file.sh' ,
      arguments => $platform_metadata_arguments,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

    puppi::deploy { "${name}-Extract_Maven_Metadata_hybrisServer-Platform":
      priority  => '21' ,
      command   => 'get_metadata.sh' ,
      arguments => "-m ${document_suffix} -mc ${config_suffix} -mj ${jar_suffix} -mw ${war_suffix} -mz ${zip_suffix} -at ${artifact_type}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

  # Files retrieval
  $platform_zipfile_arguments = $http_password ? {
    ''      => "${source}/hybrisServer-Platform zipfile",
    default => "-u ${http_user} -p ${http_password} ${source}/hybrisServer-Platform zipfile",
  }

  if ($deploy_root != '') {
    puppi::deploy { "${name}-Get_Maven_Files_ZIP_hybrisServer-Platform":
      priority  => '22' ,
      command   => 'get_maven_files.sh' ,
      arguments => $platform_zipfile_arguments,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

    # Second: hybrisServer-AllExtensions
    $extensions_metadata_arguments = $http_password ? {
      ''      => "-s ${source}/hybrisServer-AllExtensions/maven-metadata.xml -t maven-metadata -a ${real_always_deploy}",
      default => "-s ${source}/hybrisServer-AllExtensions/maven-metadata.xml -t maven-metadata -a ${real_always_deploy} -u ${http_user} -p ${http_password}",
    }

    puppi::deploy { "${name}-Get_Maven_Metadata_hybrisServer-AllExtensions_File":
      priority  => '23' ,
      command   => 'get_file.sh' ,
      arguments => $extensions_metadata_arguments,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

    puppi::deploy { "${name}-Extract_Maven_Metadata_hybrisServer-AllExtensions":
      priority  => '24' ,
      command   => 'get_metadata.sh' ,
      arguments => "-m ${document_suffix} -mc ${config_suffix} -mj ${jar_suffix} -mw ${war_suffix} -mz ${zip_suffix} -at ${artifact_type}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

  # Files retrieval
  if ($deploy_root != '') {
    $extensions_zipfile_arguments = $http_password ? {
      ''      => "${source}/hybrisServer-AllExtensions zipfile",
      default => "-u ${http_user} -p ${http_password} ${source}/hybrisServer-AllExtensions zipfile",
    }

    puppi::deploy { "${name}-Get_Maven_Files_ZIP_hybrisServer-AllExtensions":
      priority  => '25' ,
      command   => 'get_maven_files.sh' ,
      arguments => $extensions_zipfile_arguments,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }


  if ($config_root != '') {
    # Third: config-tarball (optional, right now not supported)
    $config_metadata_arguments = $http_password ? {
      ''      => "-s ${source}/config/maven-metadata.xml -t maven-metadata -a ${real_always_deploy}",
      default => "-s ${source}/config/maven-metadata.xml -t maven-metadata -a ${real_always_deploy} -u ${http_user} -p ${http_password}"
    }

    puppi::deploy { "${name}-Get_Maven_Metadata_config-tarball_File":
      priority  => '26' ,
      command   => 'get_file.sh' ,
      arguments => $config_metadata_arguments,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

    puppi::deploy { "${name}-Extract_Maven_Metadata_config-tarball":
      priority  => '27' ,
      command   => 'get_metadata.sh' ,
      arguments => "-m ${document_suffix} -mc ${config_suffix} -mj ${jar_suffix} -mw ${war_suffix} -mz ${zip_suffix} -at ${artifact_type}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }

    puppi::deploy { "${name}-Get_Maven_Files_Config":
      priority  => '28' ,
      command   => 'get_maven_files.sh' ,
      arguments => "${source} configfile" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($firewall_src_ip != '') {
    puppi::deploy { "${name}-Load_Balancer_Block":
      priority  => '30' ,
      command   => 'firewall.sh' ,
      arguments => "${firewall_src_ip} ${firewall_dst_port} on ${firewall_delay}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  # Existing files backup
  if ($deploy_root != '') {
    puppi::deploy { "${name}-Backup_Existing_WAR":
      priority  => '30' ,
      command   => 'archive.sh' ,
      arguments => "-b ${deploy_root} -t war -s move -m diff -o '${backup_rsync_options}' -n ${backup_retention}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($jar_root != '') {
    puppi::deploy { "${name}-Backup_Existing_JAR":
      priority  => '30' ,
      command   => 'archive.sh' ,
      arguments => "-b ${jar_root} -t jar -s move -m diff -o '${backup_rsync_options}' -n ${backup_retention}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($config_root != '') {
    puppi::deploy { "${name}-Backup_Existing_ConfigDir":
      priority  => '30' ,
      command   => 'archive.sh' ,
      arguments => "-b ${config_root} -t config -d predeploydir_configfile -o '${backup_rsync_options}' -n ${backup_retention}" ,
      user      => 'root' ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($document_root != '') {
    puppi::deploy { "${name}-Backup_Existing_DocumentDir":
      priority  => '30' ,
      command   => 'archive.sh' ,
      arguments => "-b ${document_root} -t docroot -d predeploydir_configfile -o '${backup_rsync_options}' -n ${backup_retention}" ,
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

  if ($init_script != '') {
    puppi::deploy { "${name}-Service_stop":
      priority  => '38' ,
      command   => 'service.sh' ,
      arguments => "stop ${init_script}" ,
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

  # Deploys
  if ($deploy_root != '') {
    puppi::deploy { "${name}-Deploy_Maven_ZIP":
      priority  => '40' ,
      command   => 'deploy.sh' ,
      arguments => "${deploy_root} predeploydir_zipfile ",
      user      => $user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($config_root != '') {
    puppi::deploy { "${name}-Deploy_ConfigDir":
      priority  => '40' ,
      command   => 'deploy.sh' ,
      arguments => "${config_root} predeploydir_configfile" ,
      user      => $config_real_user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  puppi::deploy { "${name}-yant":
    priority  => '42' ,
    command   => 'yant.sh' ,
    arguments => "${deploy_root} clean all" ,
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

  if ($init_script != '') {
    puppi::deploy { "${name}-Service_start":
      priority  => '44' ,
      command   => 'service.sh' ,
      arguments => "start ${init_script}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($disable_services != '') {
    puppi::deploy { "${name}-Enable_extra_services":
      priority  => '45' ,
      command   => 'service.sh' ,
      arguments => "start ${disable_services}" ,
      user      => 'root',
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($firewall_src_ip != '') {
    puppi::deploy { "${name}-Load_Balancer_Unblock":
      priority  => '47' ,
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

  if ($init_script != '') {
    puppi::rollback { "${name}-Service_stop":
      priority  => '38' ,
      command   => 'service.sh' ,
      arguments => "stop ${init_script}" ,
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

  if ($deploy_root != '') {
    puppi::rollback { "${name}-Recover_ZIP":
      priority  => '40' ,
      command   => 'archive.sh' ,
      arguments => "-r ${deploy_root} -t zip -o '${backup_rsync_options}'" ,
      user      => $user ,
      project   => $name ,
      enable    => $enable ,
    }
  }

  if ($config_root != '') {
    puppi::rollback { "${name}-Recover_ConfigDir":
      priority  => '40' ,
      command   => 'archive.sh' ,
      arguments => "-r ${config_root} -t config -o '${backup_rsync_options}'" ,
      user      => $config_real_user ,
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

  if ($init_script != '') {
    puppi::rollback { "${name}-Service_start":
      priority  => '42' ,
      command   => 'service.sh' ,
      arguments => "start ${init_script}" ,
      user      => 'root',
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
