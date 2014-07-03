class rabbitmq::repo::rhel {

  $package_gpg_key = $rabbitmq::package_gpg_key

  Class['rabbitmq::repo::rhel'] -> Package<| title == 'rabbitmq-server' |>

  exec { "rpm --import ${package_gpg_key}":
    path   => ['/bin','/usr/bin','/sbin','/usr/sbin'],
    onlyif => 'test `rpm -qa | grep gpg-pubkey-056e8e56-468e43f2 | wc -l` -eq 0',
  }

}
