# force.pp
# force a package from a specific release

define apt::force(
  $release = 'testing',
  $version = false,
  $timeout = 300
) {

  $provider = $apt::params::provider

  $version_string = $version ? {
    false   => undef,
    default => "=${version}",
  }

  $release_string = $release ? {
    false   => undef,
    default => "-t ${release}",
  }

  if $version == false {
    if $release == false {
      $install_check = "/usr/bin/dpkg -s ${name} | grep -q 'Status: install'"
    } else {
      # If installed version and candidate version differ, this check returns 1 (false).
      $install_check = "/usr/bin/test \$(/usr/bin/apt-cache policy -t ${release} ${name} | /bin/grep -E 'Installed|Candidate' | /usr/bin/uniq -s 14 | /usr/bin/wc -l) -eq 1"
    }
  } else {
    if $release == false {
      $install_check = "/usr/bin/dpkg -s ${name} | grep -q 'Version: ${version}'"
    } else {
      $install_check = "/usr/bin/apt-cache policy -t ${release} ${name} | /bin/grep -q 'Installed: ${version}'"
    }
  }

  exec { "${provider} -y ${release_string} install ${name}${version_string}":
    unless    => $install_check,
    logoutput => 'on_failure',
    timeout   => $timeout,
  }
}
