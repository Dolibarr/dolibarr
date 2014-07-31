# Configure EPEL (Extra Repository for Enterprise Linux)

# About
This module basically just mimics the epel-release rpm. The same repos are
enabled/disabled and the GPG key is imported.  In the end you will end up with
the EPEL repos configured.

The following Repos will be setup and enabled by default:

  * epel

Other repositories that will setup but disabled (as per the epel-release setup)

  * epel-debuginfo
  * epel-source
  * epel-testing
  * epel-testing-debuginfo
  * epel-testing-source

# Proxy
If you have an http proxy required to access the internet, you can use either
a class parameter in the _epel_ class, or edit the $proxy variable in the
params.pp file. By default no proxy is assumed.

# Why?
I am a big fan of EPEL. I actually was one of the people who helped get it
going. I am also the owner of the epel-release package, so in general this
module should stay fairly up to date with the official upstream package.

I just got sick of coding Puppet modules and basically having an assumption
that EPEL was setup or installed.  I can now depend on this module instead.

I realize it is fairly trivial to get EPEL setup. Every now-and-then however
the path to epel-release changes because something changes in the package (mass
rebuild, rpm build macros updates, etc).  This  module will bypass the changing
URL and just setup the package mirrors.

This does mean that if you are looking for RPM macros that are normally
included with EPEL release, this will not have them.

# Futher Information

* [EPEL Wiki](http://fedoraproject.org/wiki/EPEL)
* [epel-release package information](http://mirrors.servercentral.net/fedora/epel/6/i386/repoview/epel-release.html)

# Testing

* This is commonly used on Puppet Enterprise 3.x
* This was tested using Puppet 3.3.0 on Centos5/6
* This was tested using Puppet 3.1.1 on Amazon's AWS Linux
* I assume it will work on any RHEL variant (Amazon Linux is debatable as a variant)

# Lifecycle
* No functionality has been introduced that should break Puppet 2.6 or 2.7, but I am no longer testing these versions of Puppet as they are end-of-lifed from Puppet Labs.

## Unit tests

Install the necessary gems

    bundle install

Run the RSpec and puppet-lint tests

    bundle exec rake ci

## System tests

If you have Vagrant >=1.1.0 you can also run system tests:

    RSPEC_SET=centos-64-x64 bundle exec rake spec:system

Available RSPEC_SET options are in .nodeset.yml

# License
Apache Software License 2.0

# Author/Contributors
  *  Chad Metcalf <metcalfc@gmail.com>
  *  Joseph Swick <joseph.swick@meltwater.com>
  *  Matthaus Owens <mlitteken@gmail.com>
  *  Michael Stahnke <stahnma@puppetlabs.com>
  *  Michael Stahnke <stahnma@websages.com>
  *  Pro Cabales <proletaryo@gmail.com>
  *  Proletaryo Cabales <proletaryo@gmail.com>
  *  Stefan Goethals <stefan@zipkid.eu>
  *  Tim Rupp <caphrim007@gmail.com>
  *  Trey Dockendorf <treydock@gmail.com>
  *  Troy Bollinger <troy@us.ibm.com>
  *  Vlastimil Holer <holer@ics.muni.cz>
  *  Ewoud Kohl van Wijngaarden <e.kohlvanwijngaarden@oxilion.nl>
