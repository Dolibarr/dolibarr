# = Class: yum::repo::centalt
#
# This class installs the centalt repo
#
class yum::repo::centalt {
  $osver = split($::operatingsystemrelease, '[.]')
  $release = $::operatingsystem ? {
    /(?i:Centos|RedHat|Scientific)/ => $osver[0],
    default                         => '6',
  }

  yum::managed_yumrepo { 'centalt':
    descr          => 'CentALT RPM Repository',
    baseurl        => "http://centos.alt.ru/repository/centos/${release}/\$basearch/",
    enabled        => 1,
    gpgcheck       => 1,
    failovermethod => 'priority',
    gpgkey         => 'http://centos.alt.ru/repository/centos/RPM-GPG-KEY-CentALT',
    priority       => 1,
  }
}
