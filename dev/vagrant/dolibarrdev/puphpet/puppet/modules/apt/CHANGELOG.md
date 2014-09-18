##2014-03-04 - Supported Release 1.4.2
###Summary

This is a supported release. This release tidies up 1.4.1 and re-enables
support for Ubuntu 10.04

####Features

####Bugfixes
- Fix apt:ppa to include the -y Ubuntu 10.04 requires.
- Documentation changes.
- Test fixups.

####Known Bugs

* No known issues.



##2014-02-13 1.4.1
###Summary
This is a bugfix release.

####Bugfixes
- Fix apt::force unable to upgrade packages from releases other than its original
- Removed a few refeneces to aptitude instead of apt-get for portability
- Removed call to getparam() due to stdlib dependency
- Correct apt::source template when architecture is provided
- Retry package installs if apt is locked
- Use root to exec in apt::ppa
- Updated tests and converted acceptance tests to beaker

##2013-10-08 - Release 1.4.0

###Summary

Minor bugfix and allow the timeout to be adjusted.

####Features
- Add an `updates_timeout` to apt::params

####Bugfixes
- Ensure apt::ppa can read a ppa removed by hand.


##2013-10-08 - Release 1.3.0
###Summary

This major feature in this release is the new apt::unattended_upgrades class,
allowing you to handle Ubuntu's unattended feature.  This allows you to select
specific packages to automatically upgrade without any further user
involvement.

In addition we extend our Wheezy support, add proxy support to apt:ppa and do
various cleanups and tweaks.

####Features
- Add apt::unattended_upgrades support for Ubuntu.
- Add wheezy backports support.
- Use the geoDNS http.debian.net instead of the main debian ftp server.
- Add `options` parameter to apt::ppa in order to pass options to apt-add-repository command.
- Add proxy support for apt::ppa (uses proxy_host and proxy_port from apt).

####Bugfixes
- Fix regsubst() calls to quote single letters (for future parser).
- Fix lint warnings and other misc cleanup.


##2013-07-03 - Release 1.2.0

####Features
- Add geppetto `.project` natures
- Add GH auto-release
- Add `apt::key::key_options` parameter
- Add complex pin support using distribution properties for `apt::pin` via new properties:
  - `apt::pin::codename`
  - `apt::pin::release_version`
  - `apt::pin::component`
  - `apt::pin::originator`
  - `apt::pin::label`
- Add source architecture support to `apt::source::architecture`

####Bugfixes
- Use apt-get instead of aptitude in apt::force
- Update default backports location
- Add dependency for required packages before apt-get update


##2013-06-02 - Release 1.1.1
###Summary

This is a bug fix release that resolves a number of issues:

* By changing template variable usage, we remove the deprecation warnings
  for Puppet 3.2.x
* Fixed proxy file removal, when proxy absent

Some documentation, style and whitespaces changes were also merged. This
release also introduced proper rspec-puppet unit testing on Travis-CI to help
reduce regression.

Thanks to all the community contributors below that made this patch possible.

#### Detail Changes

* fix minor comment type (Chris Rutter)
* whitespace fixes (Michael Moll)
* Update travis config file (William Van Hevelingen)
* Build all branches on travis (William Van Hevelingen)
* Standardize travis.yml on pattern introduced in stdlib (William Van Hevelingen)
* Updated content to conform to README best practices template (Lauren Rother)
* Fix apt::release example in readme (Brian Galey)
* add @ to variables in template (Peter Hoeg)
* Remove deprecation warnings for pin.pref.erb as well (Ken Barber)
* Update travis.yml to latest versions of puppet (Ken Barber)
* Fix proxy file removal (Scott Barber)
* Add spec test for removing proxy configuration (Dean Reilly)
* Fix apt::key listing longer than 8 chars (Benjamin Knofe)




## Release 1.1.0
###Summary

This release includes Ubuntu 12.10 (Quantal) support for PPAs.

---

##2012-05-25 - Puppet Labs <info@puppetlabs.com> - Release 0.0.4
###Summary

 * Fix ppa list filename when there is a period in the PPA name
 * Add .pref extension to apt preferences files
 * Allow preferences to be purged
 * Extend pin support


##2012-05-04 - Puppet Labs <info@puppetlabs.com> - Release 0.0.3
###Summary
 
 * only invoke apt-get update once
 * only install python-software-properties if a ppa is added
 * support 'ensure => absent' for all defined types
 * add apt::conf
 * add apt::backports
 * fixed Modulefile for module tool dependency resolution
 * configure proxy before doing apt-get update
 * use apt-get update instead of aptitude for apt::ppa
 * add support to pin release


##2012-03-26 - Puppet Labs <info@puppetlabs.com> - Release 0.0.2
###Summary

* 41cedbb (#13261) Add real examples to smoke tests.
* d159a78 (#13261) Add key.pp smoke test
* 7116c7a (#13261) Replace foo source with puppetlabs source
* 1ead0bf Ignore pkg directory.
* 9c13872 (#13289) Fix some more style violations
* 0ea4ffa (#13289) Change test scaffolding to use a module & manifest dir fixture path
* a758247 (#13289) Clean up style violations and fix corresponding tests
* 99c3fd3 (#13289) Add puppet lint tests to Rakefile
* 5148cbf (#13125) Apt keys should be case insensitive
* b9607a4 Convert apt::key to use anchors


##2012-03-07 - Puppet Labs <info@puppetlabs.com> - Release 0.0.1
###Summary

* d4fec56 Modify apt::source release parameter test
* 1132a07 (#12917) Add contributors to README
* 8cdaf85 (#12823) Add apt::key defined type and modify apt::source to use it
* 7c0d10b (#12809) $release should use $lsbdistcodename and fall back to manual input
* be2cc3e (#12522) Adjust spec test for splitting purge
* 7dc60ae (#12522) Split purge option to spare sources.list
* 9059c4e Fix source specs to test all key permutations
* 8acb202 Add test for python-software-properties package
* a4af11f Check if python-software-properties is defined before attempting to define it.
* 1dcbf3d Add tests for required_packages change
* f3735d2 Allow duplicate $required_packages
* 74c8371 (#12430) Add tests for changes to apt module
* 97ebb2d Test two sources with the same key
* 1160bcd (#12526) Add ability to reverse apt { disable_keys => true }
* 2842d73 Add Modulefile to puppet-apt
* c657742 Allow the use of the same key in multiple sources
* 8c27963 (#12522) Adding purge option to apt class
* 997c9fd (#12529) Add unit test for apt proxy settings
* 50f3cca (#12529) Add parameter to support setting a proxy for apt
* d522877 (#12094) Replace chained .with_* with a hash
* 8cf1bd0 (#12094) Remove deprecated spec.opts file
* 2d688f4 (#12094) Add rspec-puppet tests for apt
* 0fb5f78 (#12094) Replace name with path in file resources
* f759bc0 (#11953) Apt::force passes $version to aptitude
* f71db53 (#11413) Add spec test for apt::force to verify changes to unless
* 2f5d317 (#11413) Update dpkg query used by apt::force
* cf6caa1 (#10451) Add test coverage to apt::ppa
* 0dd697d include_src parameter in example; Whitespace cleanup
* b662eb8 fix typos in "repositories"
* 1be7457 Fix (#10451) - apt::ppa fails to "apt-get update" when new PPA source is added
* 864302a Set the pin priority before adding the source (Fix #10449)
* 1de4e0a Refactored as per mlitteken
* 1af9a13 Added some crazy bash madness to check if the ppa is installed already. Otherwise the manifest tries to add it on every run!
* 52ca73e (#8720) Replace Apt::Ppa with Apt::Builddep
* 5c05fa0 added builddep command.
* a11af50 added the ability to specify the content of a key
* c42db0f Fixes ppa test.
* 77d2b0d reformatted whitespace to match recommended style of 2 space indentation.
* 27ebdfc ignore swap files.
* 377d58a added smoke tests for module.
* 18f614b reformatted apt::ppa according to recommended style.
* d8a1e4e Created a params class to hold global data.
* 636ae85 Added two params for apt class
* 148fc73 Update LICENSE.
* ed2d19e Support ability to add more than one PPA
* 420d537 Add call to apt-update after add-apt-repository in apt::ppa
* 945be77 Add package definition for python-software-properties
* 71fc425 Abs paths for all commands
* 9d51cd1 Adding LICENSE
* 71796e3 Heading fix in README
* 87777d8 Typo in README
* f848bac First commit
