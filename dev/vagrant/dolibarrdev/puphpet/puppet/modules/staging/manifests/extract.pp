# Define resource to extract files from staging directories to target directories.
define staging::extract (
  $target,              #: the target extraction directory
  $source      = undef, #: the source compression file, supports tar, tar.gz, zip, war
  $creates     = undef, #: the file created after extraction. if unspecified defaults ${staging::path}/${caller_module_name}/${name} ${target}/${name}
  $unless      = undef, #: alternative way to conditionally check whether to extract file.
  $onlyif      = undef, #: alternative way to conditionally check whether to extract file.
  $user        = undef, #: extract file as this user.
  $group       = undef, #:  extract file as this group.
  $environment = undef, #: environment variables.
  $subdir      = $caller_module_name #: subdir per module in staging directory.
) {

  include staging

  if $source {
    $source_path = $source
  } else {
    $source_path = "${staging::path}/${subdir}/${name}"
  }

  # Use user supplied creates path, set default value if creates, unless or
  # onlyif is not supplied.
  if $creates {
    $creates_path = $creates
  } elsif ! ($unless or $onlyif) {
    if $name =~ /.tar.gz$/ {
      $folder       = staging_parse($name, 'basename', '.tar.gz')
    } elsif $name =~ /.tar.bz2$/ {
      $folder       = staging_parse($name, 'basename', '.tar.bz2')
    } else {
      $folder       = staging_parse($name, 'basename')
    }
    $creates_path = "${target}/${folder}"
  }

  if scope_defaults('Exec','path') {
    Exec{
      cwd         => $target,
      user        => $user,
      group       => $group,
      environment => $environment,
      creates     => $creates_path,
      unless      => $unless,
      onlyif      => $onlyif,
      logoutput   => on_failure,
    }
  } else {
    Exec{
      path        => $::path,
      cwd         => $target,
      user        => $user,
      group       => $group,
      environment => $environment,
      creates     => $creates_path,
      unless      => $unless,
      onlyif      => $onlyif,
      logoutput   => on_failure,
    }
  }

  case $name {
    /.tar$/: {
      $command = "tar xf ${source_path}"
    }

    /(.tgz|.tar.gz)$/: {
      if $::osfamily == 'Solaris' {
        $command = "gunzip -dc < ${source_path} | tar xf - "
      } else {
        $command = "tar xzf ${source_path}"
      }
    }

    /.tar.bz2$/: {
      $command = "tar xjf ${source_path}"
    }

    /.zip$/: {
      $command = "unzip ${source_path}"
    }

    /(.war|.jar)$/: {
      $command = "jar xf ${source_path}"
    }

    default: {
      fail("staging::extract: unsupported file format ${name}.")
    }
  }

  exec { "extract ${name}":
    command => $command,
  }
}
