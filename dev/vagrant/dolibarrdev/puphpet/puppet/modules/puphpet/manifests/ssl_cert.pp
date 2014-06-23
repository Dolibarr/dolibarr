class puphpet::ssl_cert
  inherits puphpet::params {

  case $::osfamily {
    'debian': {
      if ! defined(Package['ssl-cert']) {
        package { 'ssl-cert':
          ensure => latest,
        }
      }

      exec { 'make-ssl-cert generate-default-snakeoil --force-overwrite':
        creates => [
          $puphpet::params::ssl_cert_location,
          $puphpet::params::ssl_key_location
        ],
        require => Package['ssl-cert'],
        path    => [ '/bin/', '/usr/bin/', '/usr/sbin/' ]
      }
    }
    'redhat': {
      if ! defined(Package['openssl']) {
        package { 'openssl':
          ensure => latest,
        }
      }

      exec { "make-dummy-cert ${puphpet::params::ssl_cert_location}":
        creates => $puphpet::params::ssl_cert_location,
        require => Package['openssl'],
        path    => [ '/bin/', '/usr/bin/', '/usr/sbin/', '/etc/pki/tls/certs/' ]
      }
    }
  }

}
