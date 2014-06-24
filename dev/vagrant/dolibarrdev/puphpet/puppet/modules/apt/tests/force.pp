# force.pp

# force a package from a specific release
apt::force { 'package1':
  release => 'backports',
}

# force a package to be a specific version
apt::force { 'package2':
  version => '1.0.0-1',
}

# force a package from a specific release to be a specific version
apt::force { 'package3':
  release => 'sid',
  version => '2.0.0-1',
}
