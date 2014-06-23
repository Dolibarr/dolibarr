# testing.pp

class apt::debian::testing {
  include apt

  # deb http://debian.mirror.iweb.ca/debian/ testing main contrib non-free
  # deb-src http://debian.mirror.iweb.ca/debian/ testing main contrib non-free
  # Key: 46925553  Server: subkeys.pgp.net
  # debian-keyring
  # debian-archive-keyring

  apt::source { 'debian_testing':
    location          => 'http://debian.mirror.iweb.ca/debian/',
    release           => 'testing',
    repos             => 'main contrib non-free',
    required_packages => 'debian-keyring debian-archive-keyring',
    key               => '46925553',
    key_server        => 'subkeys.pgp.net',
    pin               => '-10',
  }
}
