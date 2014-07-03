#firewall

[![Build Status](https://travis-ci.org/puppetlabs/puppetlabs-firewall.png?branch=master)](https://travis-ci.org/puppetlabs/puppetlabs-firewall)

####Table of Contents

1. [Overview - What is the Firewall module?](#overview)
2. [Module Description - What does the module do?](#module-description)
3. [Setup - The basics of getting started with Firewall](#setup)
    * [What Firewall affects](#what-firewall-affects)
    * [Setup Requirements](#setup-requirements)
    * [Beginning with Firewall](#beginning-with-firewall)
    * [Upgrading](#upgrading)
4. [Usage - Configuration and customization options](#usage)
    * [Default rules - Setting up general configurations for all firewalls](#default-rules)
    * [Application-specific rules - Options for configuring and managing firewalls across applications](#application-specific-rules)
    * [Other Rules](#other-rules)
5. [Reference - An under-the-hood peek at what the module is doing](#reference)
6. [Limitations - OS compatibility, etc.](#limitations)
7. [Development - Guide for contributing to the module](#development)
    * [Tests - Testing your configuration](#tests)

##Overview

The Firewall module lets you manage firewall rules with Puppet.

##Module Description

PuppetLabs' Firewall introduces the resource `firewall`, which is used to manage and configure firewall rules from within the Puppet DSL. This module offers support for iptables, ip6tables, and ebtables.

The module also introduces the resource `firewallchain`, which allows you to manage chains or firewall lists. At the moment, only iptables and ip6tables chains are supported.

##Setup

###What Firewall affects:

* every node running a firewall
* system's firewall settings
* connection settings for managed nodes
* unmanaged resources (get purged)
* site.pp

###Setup Requirements

Firewall uses Ruby-based providers, so you must have [pluginsync enabled](http://docs.puppetlabs.com/guides/plugins_in_modules.html#enabling-pluginsync).

###Beginning with Firewall

To begin, you need to provide some initial top-scope configuration to ensure your firewall configurations are ordered properly and you do not lock yourself out of your box or lose any configuration.

Persistence of rules between reboots is handled automatically, although there are known issues with ip6tables on older Debian/Ubuntu, as well as known issues with ebtables.

In your `site.pp` (or some similarly top-scope file), set up a metatype to purge unmanaged firewall resources. This will clear any existing rules and make sure that only rules defined in Puppet exist on the machine.

    resources { "firewall":
      purge => true
    }

Next, set up the default parameters for all of the firewall rules you will be establishing later. These defaults will ensure that the pre and post classes (you will be setting up in just a moment) are run in the correct order to avoid locking you out of your box during the first puppet run.

    Firewall {
      before  => Class['my_fw::post'],
      require => Class['my_fw::pre'],
    }

You also need to declare the `my_fw::pre` & `my_fw::post` classes so that dependencies are satisfied. This can be achieved using an External Node Classifier or the following

    class { ['my_fw::pre', 'my_fw::post']: }

Finally, you should include the `firewall` class to ensure the correct packages are installed.

    class { 'firewall': }

Now to create the `my_fw::pre` and `my_fw::post` classes. Firewall acts on your running firewall, making immediate changes as the catalog executes. Defining default pre and post rules allows you provide global defaults for your hosts before and after any custom rules; it is also required to avoid locking yourself out of your own boxes when Puppet runs. This approach employs a whitelist setup, so you can define what rules you want and everything else is ignored rather than removed.

The `pre` class should be located in `my_fw/manifests/pre.pp` and should contain any default rules to be applied first.

    class my_fw::pre {
      Firewall {
        require => undef,
      }

      # Default firewall rules
      firewall { '000 accept all icmp':
        proto   => 'icmp',
        action  => 'accept',
      }->
      firewall { '001 accept all to lo interface':
        proto   => 'all',
        iniface => 'lo',
        action  => 'accept',
      }->
      firewall { '002 accept related established rules':
        proto   => 'all',
        state => ['RELATED', 'ESTABLISHED'],
        action  => 'accept',
      }
    }

The rules in `pre` should allow basic networking (such as ICMP and TCP), as well as ensure that existing connections are not closed.

The `post` class should be located in `my_fw/manifests/post.pp` and include any default rules to be applied last.

    class my_fw::post {
      firewall { '999 drop all':
        proto   => 'all',
        action  => 'drop',
        before  => undef,
      }
    }

To put it all together: the `require` parameter in `Firewall {}` ensures `my_fw::pre` is run before any other rules and the `before` parameter ensures `my_fw::post` is run after any other rules. So the run order is:

* run the rules in `my_fw::pre`
* run your rules (defined in code)
* run the rules in `my_fw::post`

###Upgrading

####Upgrading from version 0.2.0 and newer

Upgrade the module with the puppet module tool as normal:

    puppet module upgrade puppetlabs/firewall

####Upgrading from version 0.1.1 and older

Start by upgrading the module using the puppet module tool:

    puppet module upgrade puppetlabs/firewall

Previously, you would have required the following in your `site.pp` (or some other global location):

    # Always persist firewall rules
    exec { 'persist-firewall':
      command     => $operatingsystem ? {
        'debian'          => '/sbin/iptables-save > /etc/iptables/rules.v4',
        /(RedHat|CentOS)/ => '/sbin/iptables-save > /etc/sysconfig/iptables',
      },
      refreshonly => true,
    }
    Firewall {
      notify  => Exec['persist-firewall'],
      before  => Class['my_fw::post'],
      require => Class['my_fw::pre'],
    }
    Firewallchain {
      notify  => Exec['persist-firewall'],
    }
    resources { "firewall":
      purge => true
    }

With the latest version, we now have in-built persistence, so this is no longer needed. However, you will still need some basic setup to define pre & post rules.

    resources { "firewall":
      purge => true
    }
    Firewall {
      before  => Class['my_fw::post'],
      require => Class['my_fw::pre'],
    }
    class { ['my_fw::pre', 'my_fw::post']: }
    class { 'firewall': }

Consult the the documentation below for more details around the classes `my_fw::pre` and `my_fw::post`.

##Usage

There are two kinds of firewall rules you can use with Firewall: default rules and application-specific rules. Default rules apply to general firewall settings, whereas application-specific rules manage firewall settings of a specific application, node, etc.

All rules employ a numbering system in the resource's title that is used for ordering. When titling your rules, make sure you prefix the rule with a number.

      000 this runs first
      999 this runs last

###Default rules

You can place default rules in either `my_fw::pre` or `my_fw::post`, depending on when you would like them to run. Rules placed in the `pre` class will run first, rules in the `post` class, last.

Depending on the provider, the title of the rule can be stored using the comment feature of the underlying firewall subsystem. Values can match `/^\d+[[:alpha:][:digit:][:punct:][:space:]]+$/`.

####Examples of default rules

Basic accept ICMP request example:

    firewall { "000 accept all icmp requests":
      proto  => "icmp",
      action => "accept",
    }

Drop all:

    firewall { "999 drop all other requests":
      action => "drop",
    }

###Application-specific rules

Puppet doesn't care where you define rules, and this means that you can place
your firewall resources as close to the applications and services that you
manage as you wish.  If you use the [roles and profiles
pattern](https://puppetlabs.com/learn/roles-profiles-introduction) then it
would make sense to create your firewall rules in the profiles, so that they
remain close to the services managed by the profile.

An example of this might be:

```puppet
class profile::apache {
  include apache
  apache::vhost { 'mysite': ensure => present }

  firewall { '100 allow http and https access':
    port   => [80, 443],
    proto  => tcp,
    action => accept,
  }
}
```


However, if you're not using that pattern then you can place them directly into
the individual module that manages a service, such as:

```puppet
class apache {
  firewall { '100 allow http and https access':
    port   => [80, 443],
    proto  => tcp,
    action => accept,
  }
  # ... the rest of your code ...
}
```

This means if someone includes either the profile:

```puppet
include profile::apache
```

Or the module, if you're not using roles and profiles:

```puppet
  include ::apache
```

Then they would automatically get appropriate firewall rules.

###Other rules

You can also apply firewall rules to specific nodes. Usually, you will want to put the firewall rule in another class and apply that class to a node. But you can apply a rule to a node.

    node 'foo.bar.com' {
      firewall { '111 open port 111':
        dport => 111
      }
    }

You can also do more complex things with the `firewall` resource. Here we are doing some NAT configuration.

    firewall { '100 snat for network foo2':
      chain    => 'POSTROUTING',
      jump     => 'MASQUERADE',
      proto    => 'all',
      outiface => "eth0",
      source   => '10.1.2.0/24',
      table    => 'nat',
    }

In the below example, we are creating a new chain and forwarding any port 5000 access to it.

    firewall { '100 forward to MY_CHAIN':
      chain   => 'INPUT',
      jump    => 'MY_CHAIN',
    }
    # The namevar here is in the format chain_name:table:protocol
    firewallchain { 'MY_CHAIN:filter:IPv4':
      ensure  => present,
    }
    firewall { '100 my rule':
      chain   => 'MY_CHAIN',
      action  => 'accept',
      proto   => 'tcp',
      dport   => 5000,
    }

###Additional Information

You can access the inline documentation:

    puppet describe firewall

Or

    puppet doc -r type
    (and search for firewall)

##Reference

Classes:

* [firewall](#class-firewall)

Types:

* [firewall](#type-firewall)
* [firewallchain](#type-firewallchain)

Facts:

* [ip6tables_version](#fact-ip6tablesversion)
* [iptables_version](#fact-iptablesversion)
* [iptables_persistent_version](#fact-iptablespersistentversion)

###Class: firewall

This class is provided to do the basic setup tasks required for using the firewall resources.

At the moment this takes care of:

* iptables-persistent package installation

You should include the class for nodes that need to use the resources in this module. For example

    class { 'firewall': }

####`ensure`

Indicates the state of `iptables` on your system, allowing you to disable `iptables` if desired.

Can either be `running` or `stopped`. Default to `running`.

###Type: firewall

This type provides the capability to manage firewall rules within puppet.

For more documentation on the type, access the 'Types' tab on the Puppet Labs Forge:

<http://forge.puppetlabs.com/puppetlabs/firewall#types>

###Type:: firewallchain

This type provides the capability to manage rule chains for firewalls.

For more documentation on the type, access the 'Types' tab on the Puppet Labs Forge:

<http://forge.puppetlabs.com/puppetlabs/firewall#types>

###Fact: ip6tables_version

The module provides a Facter fact that can be used to determine what the default version of ip6tables is for your operating system/distribution.

###Fact: iptables_version

The module provides a Facter fact that can be used to determine what the default version of iptables is for your operating system/distribution.

###Fact: iptables_persistent_version

Retrieves the version of iptables-persistent from your OS. This is a Debian/Ubuntu specific fact.

##Limitations

###SLES

The `socket` parameter is not supported on SLES.  In this release it will cause
the catalog to fail with iptables failures, rather than correctly warn you that
the features are unusable.

###Oracle Enterprise Linux

The `socket` and `owner` parameters are unsupported on Oracle Enterprise Linux
when the "Unbreakable" kernel is used. These may function correctly when using
the stock RedHat kernel instead. Declaring either of these parameters on an
unsupported system will result in iptable rules failing to apply.

###Other

Bugs can be reported using JIRA issues

<http://tickets.puppetlabs.com>

##Development

Puppet Labs modules on the Puppet Forge are open projects, and community contributions are essential for keeping them great. We can’t access the huge number of platforms and myriad of hardware, software, and deployment configurations that Puppet is intended to serve.

We want to keep it as easy as possible to contribute changes so that our modules work in your environment. There are a few guidelines that we need contributors to follow so that we can have a chance of keeping on top of things.

You can read the complete module contribution guide [on the Puppet Labs wiki.](http://projects.puppetlabs.com/projects/module-site/wiki/Module_contributing)

For this particular module, please also read CONTRIBUTING.md before contributing.

Currently we support:

* iptables
* ip6tables
* ebtables (chains only)

But plans are to support lots of other firewall implementations:

* FreeBSD (ipf)
* Mac OS X (ipfw)
* OpenBSD (pf)
* Cisco (ASA and basic access lists)

If you have knowledge in these technologies, know how to code, and wish to contribute to this project, we would welcome the help.

###Testing

Make sure you have:

* rake
* bundler

Install the necessary gems:

    bundle install

And run the tests from the root of the source code:

    rake test

If you have a copy of Vagrant 1.1.0 you can also run the system tests:

    RSPEC_SET=debian-606-x64 rake spec:system
    RSPEC_SET=centos-58-x64 rake spec:system

*Note:* system testing is fairly alpha at this point, your mileage may vary.
