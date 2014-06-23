# The baseline for module testing used by Puppet Labs is that each manifest
# should have a corresponding test manifest that declares that class or defined
# type.
#
# Tests are then run by using puppet apply --noop (to check for compilation errors
# and view a log of events) or by fully applying the test in a virtual environment
# (to compare the resulting system state to the desired state).
#
# Learn more about module testing here: http://docs.puppetlabs.com/guides/tests_smoke.html
#

composer::project { 'my_first_test':
  project_name => 'fabpot/silex-skeleton',
  target_dir   => '/tmp/first_test',
}

composer::project { 'my_second_test':
  project_name  => 'fabpot/silex-skeleton',
  target_dir    => '/tmp/second_test',
  prefer_source => true,
  stability     => 'dev',
}

