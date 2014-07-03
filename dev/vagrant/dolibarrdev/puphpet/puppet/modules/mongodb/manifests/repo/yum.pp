# PRIVATE CLASS: do not use directly
class mongodb::repo::yum inherits mongodb::repo {
  # We try to follow/reproduce the instruction
  # http://docs.mongodb.org/manual/tutorial/install-mongodb-on-red-hat-centos-or-fedora-linux/

  if($::mongodb::repo::ensure == 'present' or $::mongodb::repo::ensure == true) {
    yumrepo { 'mongodb':
      descr    => 'MongoDB/10gen Repository',
      baseurl  => $::mongodb::repo::location,
      gpgcheck => '0',
      enabled  => '1',
    }
    Yumrepo['mongodb'] -> Package<|tag == 'mongodb'|>
  }
  else {
    yumrepo { 'mongodb':
      enabled => absent,
    }
  }
}
