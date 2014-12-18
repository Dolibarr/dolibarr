# == Class: erlang::params
#
# Default paramaters setting repository details for different
# operating systems
#
class erlang::params {
  $epel_enable = false

  case $::osfamily {
    'Debian' : {
      $key_signature            = 'D208507CA14F4FCA'
      $package_name             = 'erlang-nox'
      $remote_repo_key_location = 'http://packages.erlang-solutions.com/debian/erlang_solutions.asc'
      $remote_repo_location     = 'http://packages.erlang-solutions.com/debian'
      $repos                    = 'contrib'
    }
    'RedHat', 'SUSE', 'Archlinux' : {
      $package_name = 'erlang'

      if $::operatingsystemrelease =~ /^5/ {
        $local_repo_location  = '/etc/yum.repos.d/epel-erlang.repo'
        $remote_repo_location = 'http://repos.fedorapeople.org/repos/peter/erlang/epel-erlang.repo'
      }
    }
    default : {
      fail("The ${module_name} module is not supported on an ${::osfamily} based system.")
    }
  }
}
