Vagrant development box for Dolibarr
====================================

Introduction
------------

[Vagrant](http://vagrantup.com) is a tool to make development easier using [VirtualBox](http://virtualbox.org) virtual machines.

These machines have been created with [PuPHEt](http://puphpet.com) and combine the power of Vagrant with [Puppet](http://puppetlabs.com) to automate the development machine provisionning.

What you need
-------------

Latest versions of:

- [VirtualBox](https://www.virtualbox.org/wiki/Downloads)
- [Vagrant](http://downloads.vagrantup.com/)

Usage
-----

### VM startup

`cd` into the vagrant box directory and simply type `vagrant up`.

That's all you need to do. It will build a brand new VirtalBox machine for you with everything you need to develop on Dolibarr.

### Name resolution
For easy access to the VM you need to setup name resolution to the machines IP.

Edit the [hosts](https://en.wikipedia.org/wiki/Hosts_(file)) file on the machine you run Vagrant on to map the virtual machine's IP to it's Vhost name.

Example syntax:

    192.168.42.101  dev.dolibarr.org

Once this is done, you will be able to access your VM's service at <http://dev.dolibarr.org>

Available boxes
---------------

### dolibardev

Somewhat bleeding edge vagrant box for develop branch related work.

- IP: 192.168.42.101
- Vhost: dev.dolibarr.org
- OS: Debian Wheezy 7.5
- Webserver: Apache 2.2.22
- PHP: mod_php 5.5.13-1~dotdeb.1
- Database: MySQL 5.5
    - Root user: root
    - Root password: root
    - Database name: dolibarr
    - Database user: user
    - Database password: user
    - Initial data: dev/initdata/mysqldump_dolibarr-3.5.0.sql
- Debugger: XDebug
- Profiler: Xhprof

You can access MailCatcher to read all outgoing emails at http://192.168.42.101:1080
