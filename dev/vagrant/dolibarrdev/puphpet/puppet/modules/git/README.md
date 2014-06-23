#git

####Table of Contents

1. [Overview - What is the [Modulename] module?](#overview)
2. [Module Description - What does the module do?](#module-description)
3. [Setup - The basics of getting started with [Modulename]](#setup)
    * [What [Modulename] affects](#what-registry-affects)
4. [Usage - Configuration options and additional functionality](#usage)
6. [Limitations - OS compatibility, etc.](#limitations)
7. [Development - Guide for contributing to the module](#development)

##Overview

Simple module that can install git or gitosis

##Module Description

This module installs the git revision control system on a target node. It does not manage a git server or any associated services; it simply ensures a bare minimum set of features (e.g. just a package) to use git.

##Setup

###What git affects

* Package['git']

The specifics managed by the module may vary depending on the platform.

##Usage

###I just want `git` installed
Simply include the `git` class.

```puppet
include git
```

###I want to use `git subtree` with bash completion

```puppet
include git::subtree
```

##Reference

###Classes

* `git`: Installs the git client package.
* `gitosis`: Installs the gitosis package. No configuration
* `subtree`: Installs and configures git-subtree for git 1.7 and up.

###Facts

* `git_exec_path`: Path to the directory containing all `git-*` commands.
* `git_version`: Version of git that is installed. Undefined if not installed.

##Limitations

This module is known to work with the following operating system families:

 - RedHat 5, 6
 - Debian 6.0.7 or newer
 - Ubuntu 12.04 or newer

##Development

Puppet Labs modules on the Puppet Forge are open projects, and community contributions are essential for keeping them great. We can’t access the huge number of platforms and myriad of hardware, software, and deployment configurations that Puppet is intended to serve.

We want to keep it as easy as possible to contribute changes so that our modules work in your environment. There are a few guidelines that we need contributors to follow so that we can have a chance of keeping on top of things.

You can read the complete module contribution guide [on the Puppet Labs wiki.](http://projects.puppetlabs.com/projects/module-site/wiki/Module_contributing)
