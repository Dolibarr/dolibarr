class supervisord::pip inherits supervisord {

  Exec {
    path => '/usr/local/bin:/bin:/usr/bin:/usr/local/sbin:/usr/sbin:/sbin'
  }

  exec { 'install_setuptools':
    command => "curl ${supervisord::setuptools_url} | python",
    cwd     => '/tmp',
    unless  => 'which easy_install',
    before  => Exec['install_pip']
  }

  exec { 'install_pip':
    command     => 'easy_install pip',
    unless      => 'which pip'
  }

  if $::osfamily == 'RedHat' {
    exec { 'pip_provider_name_fix':
      command     => 'alternatives --install /usr/bin/pip-python pip-python /usr/bin/pip 1',
      subscribe   => Exec['install_pip'],
      unless      => 'which pip-python'
    }
  }
}
