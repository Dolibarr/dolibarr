#rabbitmq

####Table of Contents

1. [Overview](#overview)
2. [Module Description - What the module does and why it is useful](#module-description)
3. [Setup - The basics of getting started with rabbitmq](#setup)
    * [What rabbitmq affects](#what-rabbitmq-affects)
    * [Setup requirements](#setup-requirements)
    * [Beginning with rabbitmq](#beginning-with-rabbitmq)
4. [Usage - Configuration options and additional functionality](#usage)
5. [Reference - An under-the-hood peek at what the module is doing and how](#reference)
5. [Limitations - OS compatibility, etc.](#limitations)
   * [RedHat module dependencies](#redhat-module-dependecies)
6. [Development - Guide for contributing to the module](#development)

##Overview

This module manages RabbitMQ (www.rabbitmq.com)

##Module Description
The rabbitmq module sets up rabbitmq and has a number of providers to manage
everything from vhosts to exchanges after setup.

This module has been tested against 2.7.1 and is known to not support
all features against earlier versions.

##Setup

###What rabbitmq affects

* rabbitmq repository files.
* rabbitmq package.
* rabbitmq configuration file.
* rabbitmq service.

###Beginning with rabbitmq

```puppet
include '::rabbitmq'
```

##Usage

All options and configuration can be done through interacting with the parameters
on the main rabbitmq class.  These are documented below.

##rabbitmq class

To begin with the rabbitmq class controls the installation of rabbitmq.  In here
you can control many parameters relating to the package and service, such as
disabling puppet support of the service:

```puppet
class { '::rabbitmq':
  service_manage    => false,
  port              => '5672',
  delete_guest_user => true,
}
```

### Environment Variables
To use RabbitMQ Environment Variables, use the parameters `environment_variables` e.g.:

```puppet
class { 'rabbitmq':
  port              => '5672',
  environment_variables   => {
    'RABBITMQ_NODENAME'     => 'node01',
    'RABBITMQ_SERVICENAME'  => 'RabbitMQ'
  }
}
```

### Variables Configurable in rabbitmq.config
To change RabbitMQ Config Variables in rabbitmq.config, use the parameters `config_variables` e.g.:

```puppet
class { 'rabbitmq':
  port              => '5672',
  config_variables   => {
    'hipe_compile'  => true,
    'frame_max'     => 131072,
    'log_levels'    => "[{connection, info}]"
  }
}
```

To change Erlang Kernel Config Variables in rabbitmq.config, use the parameters
`config_kernel_variables` e.g.:

```puppet
class { 'rabbitmq':
  port              => '5672',
  config_kernel_variables  => {
    'inet_dist_listen_min' => 9100,
    'inet_dist_listen_max' => 9105,
  }
}
```

### Clustering
To use RabbitMQ clustering facilities, use the rabbitmq parameters
`config_cluster`, `cluster_nodes`, and `cluster_node_type`, e.g.:

```puppet
class { 'rabbitmq':
  config_cluster    => true,
  cluster_nodes     => ['rabbit1', 'rabbit2'],
  cluster_node_type => 'ram',
}
```

**NOTE:** If you are using a version of RabbitMQ less than 3.0,
you still need to use `x-ha-policy: all` in your client
applications for any particular queue to take advantage of H/A via
mirrored queues.

If you are using a version of RabbitMQ >= 3.0 You should set the
'config_mirrored_queues' parameter if you plan
on using RabbitMQ Mirrored Queues within your cluster:

```puppet
class { 'rabbitmq':
  config_cluster         => true,
  config_mirrored_queues => true,
  cluster_nodes          => ['rabbit1', 'rabbit2'],
}
```

##Reference

##Classes

* rabbitmq: Main class for installation and service management.
* rabbitmq::config: Main class for rabbitmq configuration/management.
* rabbitmq::install: Handles package installation.
* rabbitmq::params: Different configuration data for different systems.
* rabbitmq::service: Handles the rabbitmq service.
* rabbitmq::repo::apt: Handles apt repo for Debian systems.
* rabbitmq::repo::rhel: Handles yum repo for Redhat systems.

###Parameters

####`admin_enable`

If enabled sets up the management interface/plugin for RabbitMQ.

####`cluster_disk_nodes`

DEPRECATED AND REPLACED BY CLUSTER_NODES.

####`cluster_node_type`

Choose between disk and ram nodes.

####`cluster_nodes`

An array of nodes for clustering.

####`config`

The file to use as the rabbitmq.config template.

####`config_cluster`

Boolean to enable or disable clustering support.

####`config_mirrored_queues`

DEPRECATED

Configuring queue mirroring should be done by setting the according policy for
the queue. You can read more about it
[here](http://www.rabbitmq.com/ha.html#genesis)

####`config_path`

The path to write the RabbitMQ configuration file to.

####`config_stomp`

Boolean to enable or disable stomp.

####`delete_guest_user`

Boolean to decide if we should delete the default guest user.

####`env_config`

The template file to use for rabbitmq_env.config.

####`env_config_path`

The path to write the rabbitmq_env.config file to.

####`erlang_cookie`

The erlang cookie to use for clustering - must be the same between all nodes.

####`config_variables`

To set config variables in rabbitmq.config

####`node_ip_address`

The value of RABBITMQ_NODE_IP_ADDRESS in rabbitmq_env.config

####`environment_variables`

RabbitMQ Environment Variables in rabbitmq_env.config

####`package_ensure`

Determines the ensure state of the package.  Set to installed by default, but could
be changed to latest.

####`package_name`

The name of the package to install.

####`package_provider`

What provider to use to install the package.

####`package_source`

Where should the package be installed from?

####`plugin_dir`

Location of RabbitMQ plugins.

####`port`

The RabbitMQ port.

####`management_port`

The port for the RabbitMQ management interface.

####`service_ensure`

The state of the service.

####`service_manage`

Determines if the service is managed.

####`service_name`

The name of the service to manage.

####`ssl`

Configures the service for using SSL.

####`ssl_only`

Configures the service to only use SSL.  No cleartext TCP listeners will be created.
Requires that ssl => true also.

####`stomp_port`

The port to use for Stomp.

####`stomp_ensure`

Boolean to install the stomp plugin.

####`wipe_db_on_cookie_change`

Boolean to determine if we should DESTROY AND DELETE the RabbitMQ database.

####`version`

Sets the version to install.

##Native Types

### rabbitmq\_user

query all current users: `$ puppet resource rabbitmq_user`

```
rabbitmq_user { 'dan':
  admin    => true,
  password => 'bar',
}
```
Optional parameter tags will set further rabbitmq tags like monitoring, policymaker, etc.
To set the administrator tag use admin-flag.
```puppet
rabbitmq_user { 'dan':
  admin    => true,
  password => 'bar',
  tags     => ['monitoring', 'tag1'],
}
```


### rabbitmq\_vhost

query all current vhosts: `$ puppet resource rabbitmq_vhost`

```puppet
rabbitmq_vhost { 'myhost':
  ensure => present,
}
```

### rabbitmq\_exchange

```puppet
rabbitmq_exchange { 'myexchange@myhost':
  user     => 'dan',
  password => 'bar',
  type     => 'topic',
  ensure   => present,
}
```

### rabbitmq\_user\_permissions

```puppet
rabbitmq_user_permissions { 'dan@myhost':
  configure_permission => '.*',
  read_permission      => '.*',
  write_permission     => '.*',
}
```

### rabbitmq\_plugin

query all currently enabled plugins `$ puppet resource rabbitmq_plugin`

```puppet
rabbitmq_plugin {'rabbitmq_stomp':
  ensure => present,
}
```

##Limitations

This module has been built on and tested against Puppet 2.7 and higher.

The module has been tested on:

* RedHat Enterprise Linux 5/6
* Debian 6/7
* CentOS 5/6
* Ubuntu 12.04

Testing on other platforms has been light and cannot be guaranteed.

### Module dependencies
To have a suitable erlang version installed on RedHat and Debian systems,
you have to install another puppet module from http://forge.puppetlabs.com/garethr/erlang with:

    puppet module install garethr-erlang

This module handles the packages for erlang.
To use the module, add the following snippet to your site.pp or an appropriate profile class:

For RedHat systems:

    include 'erlang'
    class { 'erlang': epel_enable => true}

For Debian systems:

    include 'erlang'
    package { 'erlang-base':
      ensure => 'latest',
    }

##Development

Puppet Labs modules on the Puppet Forge are open projects, and community
contributions are essential for keeping them great. We can’t access the
huge number of platforms and myriad of hardware, software, and deployment
configurations that Puppet is intended to serve.

We want to keep it as easy as possible to contribute changes so that our
modules work in your environment. There are a few guidelines that we need
contributors to follow so that we can have a chance of keeping on top of things.

You can read the complete module contribution guide [on the Puppet Labs wiki.](http://projects.puppetlabs.com/projects/module-site/wiki/Module_contributing)

### Authors
* Jeff McCune <jeff@puppetlabs.com>
* Dan Bode <dan@puppetlabs.com>
* RPM/RHEL packages by Vincent Janelle <randomfrequency@gmail.com>
* Puppetlabs Module Team
