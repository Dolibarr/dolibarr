node default {
  class { '::mongodb::globals':
    manage_package_repo => true
  } ->
  class { '::mongodb::server':
    smallfiles => true,
    bind_ip    => ['0.0.0.0'],
    replset    => 'rsmain'
  }
}

node /mongo1/ inherits default {
  mongodb_replset{'rsmain':
    members => ['mongo1:27017', 'mongo2:27017', 'mongo3:27017']
  }
}
