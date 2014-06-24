include drush
drush::make { '/var/aegir/platforms/drupal7' :
  makefile => '/var/aegir/makefiles/drupal7.make',
}
