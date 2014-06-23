##2014-04-09 - Supported Release 3.0.4
###Summary
This is a supported release.

The only functional change in this release is to split up the restrict
defaults to be per operating system so that we can provide safer defaults
for AIX, to resolve cases where IPv6 are disabled.

####Features
- Rework restrict defaults.

####Bugfixes
- Fix up a comment.
- Fix a test to work better on PE.

#####Known Bugs
* No known bugs

##2014-03-04 - Supported Release 3.0.3
###Summary
This is a supported release. Correct stdlib compatibility

####Bugfixes
- Remove `dirname()` call for correct stdlib compatibility.
- Improved tests

####Known Bugs
* No known bugs


## 2014-02-13 - Release 3.0.2
###Summary

No functional changes: Update the README and allow custom gem sources.

## 2013-12-17 - Release 3.0.1
### Summary

Work around a packaging bug with symlinks, no other functional changes.

## 2013-12-13 - Release 3.0.0
### Summary

Final release of 3.0, enjoy!


## 2013-10-14 - Version 3.0.0-rc1

###Summary

This release changes the behavior of restrict and adds AIX osfamily support.

####Backwards-incompatible Changes:

`restrict` no longer requires you to pass in parameters as:

restrict => [ 'restrict x', 'restrict y' ]

but just as:

restrict => [ 'x', 'y' ]

As the template now prefixes each line with restrict.

####Features
- Change the behavior of `restrict` so you no longer need the restrict
keyword.
- Add `udlc` parameter to enable undisciplined local clock regardless of the
machines status as a virtual machine.
- Add AIX support.

####Fixes
- Use class{} instead of including and then anchoring. (style)
- Extend Gentoo coverage to Facter 1.7.

---
##2013-09-05 - Version 2.0.1

###Summary

Correct the LICENSE file.

####Bugfixes
- Add in the appropriate year and name in LICENSE.


##2013-07-31 - Version 2.0.0

###Summary

The 2.0 release focuses on merging all the distro specific
templates into a single reusable template across all platforms.

To aid in that goal we now allow you to change the driftfile,
ntp keys, and perferred_servers.

####Backwards-incompatible changes

As all the distro specific templates have been removed and a
unified one created you may be missing functionality you
previously relied on.  Please test carefully before rolling
out globally.

Configuration directives that might possibly be affected:
- `filegen`
- `fudge` (for virtual machines)
- `keys`
- `logfile`
- `restrict`
- `restrictkey`
- `statistics`
- `trustedkey`

####Features:
- All templates merged into a single template.
- NTP Keys support added.
- Add preferred servers support.
- Parameters in `ntp` class:
  - `driftfile`: path for the ntp driftfile.
  - `keys_enable`: Enable NTP keys feature.
  - `keys_file`: Path for the NTP keys file.
  - `keys_trusted`: Which keys to trust.
  - `keys_controlkey`: Which key to use for the control key.
  - `keys_requestkey`: Which key to use for the request key.
  - `preferred_servers`: Array of servers to prefer.
  - `restrict`: Array of restriction options to apply.

---
###2013-07-15 - Version 1.0.1
####Bugfixes
- Fix deprecated warning in `autoupdate` parameter.
- Correctly quote is_virtual fact.


##2013-07-08 - Version 1.0.0
####Features
- Completely refactored to split across several classes.
- rspec-puppet tests rewritten to cover more options.
- rspec-system tests added.
- ArchLinux handled via osfamily instead of special casing.
- parameters in `ntp` class:
  - `autoupdate`: deprecated in favor of directly setting package_ensure.
  - `panic`: set to false if you wish to allow large clock skews. 

---
##2011-11-10 Dan Bode <dan@puppetlabs.com> - 0.0.4
* Add Amazon Linux as a supported platform
* Add unit tests


##2011-06-16 Jeff McCune <jeff@puppetlabs.com> - 0.0.3
* Initial release under puppetlabs
