# Define: puppi::netinstall
#
# This defines simplifies the installation of a file
# downloaded from the web. It provides arguments to manage
# different kind of downloads and custom commands.
# It's used, among the others, by NextGen modules of webapps
# when the argument install is set to => source
#
# == Variables
#
# [*url*]
#   The Url of the file to retrieve. Required.
#   Example: http://www.example42.com/file.tar.gz
#
# [*destination_dir*]
#   The final destination where to unpack or copy what has been
#   downloaded. Required.
#   Example: /var/www/html
#
# [*retrieve_args*]
#   A string of arguments to pass to wget.
#
# [*extracted_dir*]
#   The name of a directory or file created after the extraction
#   Needed only if its name is different from the downloaded file name
#   (without suffixes). Optional.
#
# [*owner*]
#   The user owner of the directory / file created. Default: root
#
# [*group*]
#   The group owner of the directory / file created. Default: root
#
# [*timeout*]
#   The timeout in seconds for each command executed
#
# [*work_dir*]
#   A temporary work dir where file is downloaded. Default: /var/tmp
#
# [*path*]
#  Define the path for the exec commands.
#  Default: /bin:/sbin:/usr/bin:/usr/sbin
#
# [*exec_env*]
#   Define any additional environment variables to be used with the
#   exec commands. Note that if you use this to set PATH, it will
#   override the path attribute. Multiple environment variables
#   should be specified as an array.
#
# [*extract_command*]
#   The command used to extract the downloaded file.
#   By default is autocalculated accoring to the file extension
#   Set 'rsync' if the file has to be placed in the destination_dir
#   as is (for example for war files)
#
# [*preextract_command*]
#   An optional custom command to run before extracting the file.
#
# [*postextract_command*]
#   An optional custom command to run after having extracted the file.
#
define puppi::netinstall (
  $url,
  $destination_dir,
  $extracted_dir       = '',
  $retrieve_command    = 'wget',
  $retrieve_args       = '',
  $owner               = 'root',
  $group               = 'root',
  $timeout             = '3600',
  $work_dir            = '/var/tmp',
  $path                = '/bin:/sbin:/usr/bin:/usr/sbin',
  $extract_command     = '',
  $preextract_command  = '',
  $postextract_command = '',
  $postextract_cwd     = '',
  $exec_env            = []
  ) {

  $source_filename = url_parse($url,'filename')
  $source_filetype = url_parse($url,'filetype')
  $source_dirname = url_parse($url,'filedir')

  $real_extract_command = $extract_command ? {
    ''      => $source_filetype ? {
      '.tgz'     => 'tar -zxf',
      '.gz'      => 'tar -zxf',
      '.bz2'     => 'tar -jxf',
      '.tar'     => 'tar -xf',
      '.zip'     => 'unzip',
      default    => 'tar -zxf',
    },
    default => $extract_command,
  }

  $extract_command_second_arg = $real_extract_command ? {
    /^cp.*/    => '.',
    /^rsync.*/ => '.',
    default    => '',
  }

  $real_extracted_dir = $extracted_dir ? {
    ''      => $real_extract_command ? {
      /(^cp.*|^rsync.*)/         => $source_filename,
      /(^tar -zxf*|^tar -jxf*)/  => regsubst($source_dirname,'.tar',''),
      default                    => $source_dirname,
    },
    default => $extracted_dir,
  }

  $real_postextract_cwd = $postextract_cwd ? {
    ''      => "${destination_dir}/${real_extracted_dir}",
    default => $postextract_cwd,
  }

  if $preextract_command {
    exec { "PreExtract ${source_filename} in ${destination_dir}":
      command     => $preextract_command,
      subscribe   => Exec["Retrieve ${url} in ${work_dir}"],
      refreshonly => true,
      path        => $path,
      environment => $exec_env,
      timeout     => $timeout,
    }
  }

  exec { "Retrieve ${url} in ${work_dir}":
    cwd         => $work_dir,
    command     => "${retrieve_command} ${retrieve_args} ${url}",
    creates     => "${work_dir}/${source_filename}",
    timeout     => $timeout,
    path        => $path,
    environment => $exec_env,
  }

  exec { "Extract ${source_filename} from ${work_dir}":
    command     => "mkdir -p ${destination_dir} && cd ${destination_dir} && ${real_extract_command} ${work_dir}/${source_filename} ${extract_command_second_arg}",
    unless      => "ls ${destination_dir}/${real_extracted_dir}",
    creates     => "${destination_dir}/${real_extracted_dir}",
    timeout     => $timeout,
    require     => Exec["Retrieve ${url} in ${work_dir}"],
    path        => $path,
    environment => $exec_env,
    notify      => Exec["Chown ${source_filename} in ${destination_dir}"],
  }

  exec { "Chown ${source_filename} in ${destination_dir}":
    command     => "chown -R ${owner}:${group} ${destination_dir}/${real_extracted_dir}",
    refreshonly => true,
    timeout     => $timeout,
    require     => Exec["Extract ${source_filename} from ${work_dir}"],
    path        => $path,
    environment => $exec_env,
  }

  if $postextract_command {
    exec { "PostExtract ${source_filename} in ${destination_dir}":
      command     => $postextract_command,
      cwd         => $real_postextract_cwd,
      subscribe   => Exec["Extract ${source_filename} from ${work_dir}"],
      refreshonly => true,
      timeout     => $timeout,
      require     => [Exec["Retrieve ${url} in ${work_dir}"],Exec["Chown ${source_filename} in ${destination_dir}"]],
      path        => $path,
      environment => $exec_env,
    }
  }
}

