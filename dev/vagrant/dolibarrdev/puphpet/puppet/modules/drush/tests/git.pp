include drush
drush::git { 'git://git.drupal.org:project/provision.git' :
  path => '/var/aegir/.drush',
}
