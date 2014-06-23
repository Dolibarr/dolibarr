node default {
  include composer

  composer::exec {'ohai':
    cmd => 'install',
    cwd => '/some/cool/dir',
  }
}
