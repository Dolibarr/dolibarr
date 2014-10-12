Puppet module for installing Erlang from alternative repositories.

On debian it will use the official repositories
mentioned on the [Erlang
docs](https://www.erlang-solutions.com/downloads/download-erlang-otp).

On Redhat 5 it'll use an additional EPEL repository hosted by
[Redhat](http://repos.fedorapeople.org/repos/peter/erlang/epel-erlang.repo).

On Redhat 6 it'll require EPEL.

On SUSE it'll use the official repos.

On Archlinux it'll use community repos.

This module is also available on the [Puppet
Forge](https://forge.puppetlabs.com/garethr/erlang)

[![Build
Status](https://secure.travis-ci.org/garethr/garethr-erlang.png)](http://travis-ci.org/garethr/garethr-erlang)

## Usage

The module includes a single class:

    include 'erlang'

By default this sets up the repository and installs the erlang package.
