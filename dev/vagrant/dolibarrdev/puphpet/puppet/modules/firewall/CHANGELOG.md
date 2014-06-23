## 2014-05-16 Release 1.1.1
###Summary

This release reverts the alphabetical ordering of 1.1.0.  We found this caused
a regression in the Openstack modules so in the interest of safety we have
removed this for now.

## 2014-05-13 Release 1.1.0
###Summary

This release has a significant change from previous releases; we now apply the
firewall resources alphabetically by default, removing the need to create pre
and post classes just to enforce ordering.  It only effects default ordering
and further information can be found in the README about this.  Please test
this in development before rolling into production out of an abundance of
caution.

We've also added `mask` which is required for --recent in recent (no pun
intended) versions of iptables, as well as connlimit and connmark.  This
release has been validated against Ubuntu 14.04 and RHEL7 and should be fully
working on those platforms.

####Features

- Apply firewall resources alphabetically. 
- Add support for connlimit and connmark.
- Add `mask` as a parameter. (Used exclusively with the recent parameter).

####Bugfixes

- Add systemd support for RHEL7.
- Replace &&'s with the correct and in manifests.
- Fix tests on Trusty and RHEL7
- Fix for Fedora Rawhide.
- Fix boolean flag tests.
- Fix DNAT->SNAT typo in an error message.

####Known Bugs

* For Oracle, the `owner` and `socket` parameters require a workaround to function. Please see the Limitations section of the README.


## 2014-03-04 Supported Release 1.0.2
###Summary

This is a supported release.  This release removes a testing symlink that can
cause trouble on systems where /var is on a seperate filesystem from the
modulepath.

####Features
####Bugfixes
####Known Bugs

* For Oracle, the `owner` and `socket` parameters require a workaround to function. Please see the Limitations section of the README.

### Supported release - 2014-03-04 1.0.1

####Summary

An important bugfix was made to the offset calculation for unmanaged rules
to handle rules with 9000+ in the name.

####Features

####Bugfixes
- Offset calculations assumed unmanaged rules were numbered 9000+.
- Gracefully fail to manage ip6tables on iptables 1.3.x

####Known Bugs

* For Oracle, the `owner` and `socket` parameters require a workaround to function. Please see the Limitations section of the README.

---
### 1.0.0 - 2014-02-11

No changes, just renumbering to 1.0.0.

---
### 0.5.0 - 2014-02-10

##### Summary:
This is a bigger release that brings in "recent" connection limiting (think
"port knocking"), firewall chain purging on a per-chain/per-table basis, and
support for a few other use cases. This release also fixes a major bug which
could cause modifications to the wrong rules when unmanaged rules are present.

##### New Features:
* Add "recent" limiting via parameters `rdest`, `reap`, `recent`, `rhitcount`,
  `rname`, `rseconds`, `rsource`, and `rttl`
* Add negation support for source and destination
* Add per-chain/table purging support to `firewallchain`
* IPv4 specific
  * Add random port forwarding support
  * Add ipsec policy matching via `ipsec_dir` and `ipsec_policy`
* IPv6 specific
  * Add support for hop limiting via `hop_limit` parameter
  * Add fragmentation matchers via `ishasmorefrags`, `islastfrag`, and `isfirstfrag`
  * Add support for conntrack stateful firewall matching via `ctstate`

##### Bugfixes:
- Boolean fixups allowing false values
- Better detection of unmanaged rules
- Fix multiport rule detection
- Fix sport/dport rule detection
- Make INPUT, OUTPUT, and FORWARD not autorequired for firewall chain filter
- Allow INPUT with the nat table
- Fix `src_range` & `dst_range` order detection
- Documentation clarifications
- Fixes to spec tests

---------------------------------------

### 0.4.2 - 2013-09-10

Another attempt to fix the packaging issue.  We think we understand exactly
what is failing and this should work properly for the first time.

---------------------------------------

### 0.4.1 - 2013-08-09

Bugfix release to fix a packaging issue that may have caused puppet module
install commands to fail.

---------------------------------------

### 0.4.0 - 2013-07-11

This release adds support for address type, src/dest ip ranges, and adds
additional testing and bugfixes.

#### Features
* Add `src_type` and `dst_type` attributes (Nick Stenning)
* Add `src_range` and `dst_range` attributes (Lei Zhang)
* Add SL and SLC operatingsystems as supported (Steve Traylen)

#### Bugfixes
* Fix parser for bursts other than 5 (Chris Rutter)
* Fix parser for -f in --comment (Georg Koester)
* Add doc headers to class files (Dan Carley)
* Fix lint warnings/errors (Wolf Noble)

---------------------------------------

### 0.3.1 - 2013/6/10

This minor release provides some bugfixes and additional tests.

#### Changes

* Update tests for rspec-system-puppet 2 (Ken Barber)
* Update rspec-system tests for rspec-system-puppet 1.5 (Ken Barber)
* Ensure all services have 'hasstatus => true' for Puppet 2.6 (Ken Barber)
* Accept pre-existing rule with invalid name (Joe Julian)
* Swap log_prefix and log_level order to match the way it's saved (Ken Barber)
* Fix log test to replicate bug #182 (Ken Barber)
* Split argments while maintaining quoted strings (Joe Julian)
* Add more log param tests (Ken Barber)
* Add extra tests for logging parameters (Ken Barber)
* Clarify OS support (Ken Barber)

---------------------------------------

### 0.3.0 - 2013/4/25

This release introduces support for Arch Linux and extends support for Fedora 15 and up. There are also lots of bugs fixed and improved testing to prevent regressions.

##### Changes

* Fix error reporting for insane hostnames (Tomas Doran)
* Support systemd on Fedora 15 and up (Eduardo Gutierrez)
* Move examples to docs (Ken Barber)
* Add support for Arch Linux platform (Ingmar Steen)
* Add match rule for fragments (Georg Koester)
* Fix boolean rules being recognized as changed (Georg Koester)
* Same rules now get deleted (Anastasis Andronidis)
* Socket params test (Ken Barber)
* Ensure parameter can disable firewall (Marc Tardif)

---------------------------------------

### 0.2.1 - 2012/3/13

This maintenance release introduces the new README layout, and fixes a bug with iptables_persistent_version.

##### Changes

* (GH-139) Throw away STDERR from dpkg-query in Fact
* Update README to be consistent with module documentation template
* Fix failing spec tests due to dpkg change in iptables_persistent_version

---------------------------------------

### 0.2.0 - 2012/3/3

This release introduces automatic persistence, removing the need for the previous manual dependency requirement for persistent the running rules to the OS persistence file.

Previously you would have required the following in your site.pp (or some other global location):

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

You only need:

    class { 'firewall': }
    Firewall {
      before  => Class['my_fw::post'],
      require => Class['my_fw::pre'],
    }

To install pre-requisites and to create dependencies on your pre & post rules. Consult the README for more information.

##### Changes

* Firewall class manifests (Dan Carley)
* Firewall and firewallchain persistence (Dan Carley)
* (GH-134) Autorequire iptables related packages (Dan Carley)
* Typo in #persist_iptables OS normalisation (Dan Carley)
* Tests for #persist_iptables (Dan Carley)
* (GH-129) Replace errant return in autoreq block (Dan Carley)

---------------------------------------

### 0.1.1 - 2012/2/28

This release primarily fixes changing parameters in 3.x

##### Changes

* (GH-128) Change method_missing usage to define_method for 3.x compatibility
* Update travis.yml gem specifications to actually test 2.6
* Change source in Gemfile to use a specific URL for Ruby 2.0.0 compatibility

---------------------------------------

### 0.1.0 - 2012/2/24

This release is somewhat belated, so no summary as there are far too many changes this time around. Hopefully we won't fall this far behind again :-).

##### Changes

* Add support for MARK target and set-mark property (Johan Huysmans)
* Fix broken call to super for ruby-1.9.2 in munge (Ken Barber)
* simple fix of the error message for allowed values of the jump property (Daniel Black)
* Adding OSPF(v3) protocol to puppetlabs-firewall (Arnoud Vermeer)
* Display multi-value: port, sport, dport and state command seperated (Daniel Black)
* Require jump=>LOG for log params (Daniel Black)
* Reject and document icmp => "any" (Dan Carley)
* add firewallchain type and iptables_chain provider (Daniel Black)
* Various fixes for firewallchain resource (Ken Barber)
* Modify firewallchain name to be chain:table:protocol (Ken Barber)
* Fix allvalidchain iteration (Ken Barber)
* Firewall autorequire Firewallchains (Dan Carley)
* Tests and docstring for chain autorequire (Dan Carley)
* Fix README so setup instructions actually work (Ken Barber)
* Support vlan interfaces (interface containing ".") (Johan Huysmans)
* Add tests for VLAN support for iniface/outiface (Ken Barber)
* Add the table when deleting rules (Johan Huysmans)
* Fix tests since we are now prefixing -t)
* Changed 'jump' to 'action', commands to lower case (Jason Short)
* Support interface names containing "+" (Simon Deziel)
* Fix for when iptables-save spews out "FATAL" errors (Sharif Nassar)
* Fix for incorrect limit command arguments for ip6tables provider (Michael Hsu)
* Document Util::Firewall.host_to_ip (Dan Carley)
* Nullify addresses with zero prefixlen (Dan Carley)
* Add support for --tcp-flags (Thomas Vander Stichele)
* Make tcp_flags support a feature (Ken Barber)
* OUTPUT is a valid chain for the mangle table (Adam Gibbins)
* Enable travis-ci support (Ken Barber)
* Convert an existing test to CIDR (Dan Carley)
* Normalise iptables-save to CIDR (Dan Carley)
* be clearer about what distributions we support (Ken Barber)
* add gre protocol to list of acceptable protocols (Jason Hancock)
* Added pkttype property (Ashley Penney)
* Fix mark to not repeat rules with iptables 1.4.1+ (Sharif Nassar)
* Stub iptables_version for now so tests run on non-Linux hosts (Ken Barber)
* Stub iptables facts for set_mark tests (Dan Carley)
* Update formatting of README to meet Puppet Labs best practices (Will Hopper)
* Support for ICMP6 type code resolutions (Dan Carley)
* Insert order hash included chains from different tables (Ken Barber)
* rspec 2.11 compatibility (Jonathan Boyett)
* Add missing class declaration in README (sfozz)
* array_matching is contraindicated (Sharif Nassar)
* Convert port Fixnum into strings (Sharif Nassar)
* Update test framework to the modern age (Ken Barber)
* working with ip6tables support (wuwx)
* Remove gemfile.lock and add to gitignore (William Van Hevelingen)
* Update travis and gemfile to be like stdlib travis files (William Van Hevelingen)
* Add support for -m socket option (Ken Barber)
* Add support for single --sport and --dport parsing (Ken Barber)
* Fix tests for Ruby 1.9.3 from 3e13bf3 (Dan Carley)
* Mock Resolv.getaddress in #host_to_ip (Dan Carley)
* Update docs for source and dest - they are not arrays (Ken Barber)

---------------------------------------

### 0.0.4 - 2011/12/05

This release adds two new parameters, 'uid' and 'gid'. As a part of the owner module, these params allow you to specify a uid, username, gid, or group got a match:

    firewall { '497 match uid':
      port => '123',
      proto => 'mangle',
      chain => 'OUTPUT',
      action => 'drop'
      uid => '123'
    }

This release also adds value munging for the 'log_level', 'source', and 'destination' parameters. The 'source' and 'destination' now support hostnames:

    firewall { '498 accept from puppetlabs.com':
      port => '123',
      proto => 'tcp',
      source => 'puppetlabs.com',
      action => 'accept'
    }


The 'log_level' parameter now supports using log level names, such as 'warn', 'debug', and 'panic':

    firewall { '499 logging':
      port => '123',
      proto => 'udp',
      log_level => 'debug',
      action => 'drop'
    }

Additional changes include iptables and ip6tables version facts, general whitespace cleanup, and adding additional unit tests.

##### Changes

* (#10957) add iptables_version and ip6tables_version facts
* (#11093) Improve log_level property so it converts names to numbers
* (#10723) Munge hostnames and IPs to IPs with CIDR
* (#10718) Add owner-match support
* (#10997) Add fixtures for ipencap
* (#11034) Whitespace cleanup
* (#10690) add port property support to ip6tables

---------------------------------------

### 0.0.3 - 2011/11/12

This release introduces a new parameter 'port' which allows you to set both
source and destination ports for a match:

    firewall { "500 allow NTP requests":
      port => "123",
      proto => "udp",
      action => "accept",
    }

We also have the limit parameter finally working:

    firewall { "500 limit HTTP requests":
      dport => 80,
      proto => tcp,
      limit => "60/sec",
      burst => 30,
      action => accept,
    }

State ordering has been fixed now, and more characters are allowed in the
namevar:

* Alphabetical
* Numbers
* Punctuation
* Whitespace

##### Changes

* (#10693) Ensure -m limit is added for iptables when using 'limit' param
* (#10690) Create new port property
* (#10700) allow additional characters in comment string
* (#9082) Sort iptables --state option values internally to keep it consistent across runs
* (#10324) Remove extraneous whitespace from iptables rule line in spec tests

---------------------------------------

### 0.0.2 - 2011/10/26

This is largely a maintanence and cleanup release, but includes the ability to
specify ranges of ports in the sport/dport parameter:

    firewall { "500 allow port range":
      dport => ["3000-3030","5000-5050"],
      sport => ["1024-65535"],
      action => "accept",
    }

##### Changes

* (#10295) Work around bug #4248 whereby the puppet/util paths are not being loaded correctly on the puppetmaster
* (#10002) Change to dport and sport to handle ranges, and fix handling of name to name to port
* (#10263) Fix tests on Puppet 2.6.x
* (#10163) Cleanup some of the inline documentation and README file to align with general forge usage

---------------------------------------

### 0.0.1 - 2011/10/18

Initial release.

##### Changes

* (#9362) Create action property and perform transformation for accept, drop, reject value for iptables jump parameter
* (#10088) Provide a customised version of CONTRIBUTING.md
* (#10026) Re-arrange provider and type spec files to align with Puppet
* (#10026) Add aliases for test,specs,tests to Rakefile and provide -T as default
* (#9439) fix parsing and deleting existing rules
* (#9583) Fix provider detection for gentoo and unsupported linuxes for the iptables provider
* (#9576) Stub provider so it works properly outside of Linux
* (#9576) Align spec framework with Puppet core
* and lots of other earlier development tasks ...
