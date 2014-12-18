# This depends on
#   ajcrowe/supervisord: https://github.com/ajcrowe/puppet-supervisord

class puphpet::supervisord {

  if ! defined(Class['::supervisord']) {
    class{ 'puphpet::python::pip': }

    class { '::supervisord':
      install_pip => false,
      require     => [
        Class['::my_fw::post'],
        Class['puphpet::python::pip'],
      ],
    }
  }

}
