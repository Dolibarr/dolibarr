class puphpet::python::pip {

  Exec { path => [ '/usr/bin/', '/usr/local/bin', '/bin', '/usr/local/sbin', '/usr/sbin', '/sbin' ] }

  if ! defined(Package['python-setuptools']) {
    package { 'python-setuptools': }
  }

  exec { 'easy_install pip':
    unless  => 'which pip',
    require => Package['python-setuptools'],
  }

  if $::osfamily == 'RedHat' {
    exec { 'rhel pip_provider_name_fix':
      command     => 'alternatives --install /usr/bin/pip-python pip-python /usr/bin/pip 1',
      subscribe   => Exec['easy_install pip'],
      unless      => 'which pip-python',
    }
  }

}
