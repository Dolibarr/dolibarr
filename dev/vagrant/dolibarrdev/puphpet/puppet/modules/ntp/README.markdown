#ntp

####Table of Contents

1. [Overview](#overview)
2. [Module Description - What the module does and why it is useful](#module-description)
3. [Setup - The basics of getting started with ntp](#setup)
    * [What ntp affects](#what-ntp-affects)
    * [Setup requirements](#setup-requirements)
    * [Beginning with ntp](#beginning-with-ntp)
4. [Usage - Configuration options and additional functionality](#usage)
5. [Reference - An under-the-hood peek at what the module is doing and how](#reference)
5. [Limitations - OS compatibility, etc.](#limitations)
6. [Development - Guide for contributing to the module](#development)

##Overview

The ntp module installs, configures, and manages the NTP service.

##Module Description

The ntp module handles installing, configuring, and running NTP across a range of operating systems and distributions.

##Setup

###What ntp affects

* ntp package.
* ntp configuration file.
* ntp service.

###Beginning with ntp

`include '::ntp'` is enough to get you up and running.  If you wish to pass in
parameters specifying which servers to use, then:

```puppet
class { '::ntp':
  servers => [ 'ntp1.corp.com', 'ntp2.corp.com' ],
}
```

##Usage

All interaction with the ntp module can do be done through the main ntp class.
This means you can simply toggle the options in `::ntp` to have full functionality of the module.

###I just want NTP, what's the minimum I need?

```puppet
include '::ntp'
```

###I just want to tweak the servers, nothing else.

```puppet
class { '::ntp':
  servers => [ 'ntp1.corp.com', 'ntp2.corp.com' ],
}
```

###I'd like to make sure I restrict who can connect as well.

```puppet
class { '::ntp':
  servers  => [ 'ntp1.corp.com', 'ntp2.corp.com' ],
  restrict => ['127.0.0.1'],
}
```

###I'd like to opt out of having the service controlled; we use another tool for that.

```puppet
class { '::ntp':
  servers        => [ 'ntp1.corp.com', 'ntp2.corp.com' ],
  restrict       => ['127.0.0.1'],
  manage_service => false,
}
```

###Looks great!  But I'd like a different template; we need to do something unique here.

```puppet
class { '::ntp':
  servers         => [ 'ntp1.corp.com', 'ntp2.corp.com' ],
  restrict        => ['127.0.0.1'],
  manage_service  => false,
  config_template => 'different/module/custom.template.erb',
}
```

##Reference

###Classes

####Public Classes

* ntp: Main class, includes all other classes.

####Private Classes

* ntp::install: Handles the packages.
* ntp::config: Handles the configuration file.
* ntp::service: Handles the service.

###Parameters

The following parameters are available in the ntp module:

####`autoupdate`

**Deprecated:** This parameter determined whether the ntp module should be
automatically updated to the latest version available.  Replaced by `package_ensure`.

####`config`

Sets the file that ntp configuration is written into.

####`config_template`

Determines which template Puppet should use for the ntp configuration.

####`driftfile`

Sets the location of the drift file for ntp.

####`keys_controlkey`

The key to use as the control key.

####`keys_enable`

Whether the ntp keys functionality is enabled.

####`keys_file`

Location of the keys file.

####`keys_requestkey`

Which of the keys is the request key.

#### `keys_trusted`

Array of trusted keys.

####`package_ensure`

Sets the ntp package to be installed. Can be set to 'present', 'latest', or a specific version. 

####`package_name`

Determines the name of the package to install.

####`panic`

Determines if ntp should 'panic' in the event of a very large clock skew.
This defaults to false for virtual machines, as they don't do a great job with keeping time.

####`preferred_servers`

List of ntp servers to prefer.  Will append 'prefer' for any server in this list
that also appears in the servers list.

####`restrict`

Sets the restrict options in the ntp configuration.  The lines are
prefixed with 'restrict', so you just need to list the rest of the restriction.

####`servers`

Selects the servers to use for ntp peers.

####`service_enable`

Determines if the service should be enabled at boot.

####`service_ensure`

Determines if the service should be running or not.

####`service_manage`

Selects whether Puppet should manage the service.

####`service_name`

Selects the name of the ntp service for Puppet to manage.

####`udlc`

Enables configs for undisciplined local clock, regardless of
status as a virtual machine. 


##Limitations

This module has been built on and tested against Puppet 2.7 and higher.

The module has been tested on:

* RedHat Enterprise Linux 5/6
* Debian 6/7
* CentOS 5/6
* Ubuntu 12.04
* Gentoo
* Arch Linux
* FreeBSD

Testing on other platforms has been light and cannot be guaranteed. 

##Development

Puppet Labs modules on the Puppet Forge are open projects, and community
contributions are essential for keeping them great. We can’t access the
huge number of platforms and myriad of hardware, software, and deployment
configurations that Puppet is intended to serve.

We want to keep it as easy as possible to contribute changes so that our
modules work in your environment. There are a few guidelines that we need
contributors to follow so that we can have a chance of keeping on top of things.

You can read the complete module contribution guide [on the Puppet Labs wiki.](http://projects.puppetlabs.com/projects/module-site/wiki/Module_contributing)

###Contributors

The list of contributors can be found at: [https://github.com/puppetlabs/puppetlabs-ntp/graphs/contributors](https://github.com/puppetlabs/puppetlabs-ntp/graphs/contributors)
