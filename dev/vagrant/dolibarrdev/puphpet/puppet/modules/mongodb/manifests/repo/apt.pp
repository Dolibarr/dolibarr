# PRIVATE CLASS: do not use directly
class mongodb::repo::apt inherits mongodb::repo {
  # we try to follow/reproduce the instruction
  # from http://docs.mongodb.org/manual/tutorial/install-mongodb-on-ubuntu/

  include ::apt

  if($::mongodb::repo::ensure == 'present' or $::mongodb::repo::ensure == true) {
    apt::source { 'downloads-distro.mongodb.org':
      location    => $::mongodb::repo::location,
      release     => 'dist',
      repos       => '10gen',
      key         => '9ECBEC467F0CEB10',
      key_server  => 'keyserver.ubuntu.com',
      include_src => false,
    }

    Apt::Source['downloads-distro.mongodb.org']->Package<|tag == 'mongodb'|>
  }
  else {
    apt::source { 'downloads-distro.mongodb.org':
      ensure => absent,
    }
  }
}
