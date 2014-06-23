# Class: puppi::two
#
# Installs Puppi NextGen
#
class puppi::two {

  # The Puppi command
  package { 'puppi':
    ensure   => present,
    provider => 'gem',
  }

}
