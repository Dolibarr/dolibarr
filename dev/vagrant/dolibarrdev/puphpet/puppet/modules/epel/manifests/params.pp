# Optional parameters in setting up EPEL
class epel::params {
  # Setting to 'absent' will fall back to the yum.conf
  # Setting proxy here will be the default for all repos.
  #
  #  If you wish to set a proxy for an individual set of repos,
  #   you can declare $proxy in that class, and should scope to
  #   the most specific declaration of proxy.
  $proxy = 'absent'

  $epel_mirrorlist                        = "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-${::os_maj_version}&arch=\$basearch"
  $epel_baseurl                           = 'absent'
  $epel_failovermethod                    = 'priority'
  $epel_proxy                             = $proxy
  $epel_enabled                           = '1'
  $epel_gpgcheck                          = '1'
  $epel_testing_baseurl                   = "http://download.fedoraproject.org/pub/epel/testing/${::os_maj_version}/\$basearch"
  $epel_testing_failovermethod            = 'priority'
  $epel_testing_proxy                     = $proxy
  $epel_testing_enabled                   = '0'
  $epel_testing_gpgcheck                  = '1'
  $epel_source_mirrorlist                 = "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-source-${::os_maj_version}&arch=\$basearch"
  $epel_source_baseurl                    = 'absent'
  $epel_source_failovermethod             = 'priority'
  $epel_source_proxy                      = $proxy
  $epel_source_enabled                    = '0'
  $epel_source_gpgcheck                   = '1'
  $epel_debuginfo_mirrorlist              = "http://mirrors.fedoraproject.org/mirrorlist?repo=epel-debug-${::os_maj_version}&arch=\$basearch"
  $epel_debuginfo_baseurl                 = 'absent'
  $epel_debuginfo_failovermethod          = 'priority'
  $epel_debuginfo_proxy                   = $proxy
  $epel_debuginfo_enabled                 = '0'
  $epel_debuginfo_gpgcheck                = '1'
  $epel_testing_source_baseurl            = "http://download.fedoraproject.org/pub/epel/testing/${::os_maj_version}/SRPMS"
  $epel_testing_source_failovermethod     = 'priority'
  $epel_testing_source_proxy              = $proxy
  $epel_testing_source_enabled            = '0'
  $epel_testing_source_gpgcheck           = '1'
  $epel_testing_debuginfo_baseurl         = "http://download.fedoraproject.org/pub/epel/testing/${::os_maj_version}/\$basearch/debug"
  $epel_testing_debuginfo_failovermethod  = 'priority'
  $epel_testing_debuginfo_proxy           = $proxy
  $epel_testing_debuginfo_enabled         = '0'
  $epel_testing_debuginfo_gpgcheck        = '1'

}
