vcsrepo
=======

[![Build Status](https://travis-ci.org/puppetlabs/puppetlabs-vcsrepo.png?branch=master)](https://travis-ci.org/puppetlabs/puppetlabs-vcsrepo)

Purpose
-------

This provides a single type, `vcsrepo`.

This type can be used to describe:

* A working copy checked out from a (remote or local) source, at an
  arbitrary revision
* A "blank" working copy not associated with a source (when it makes
  sense for the VCS being used)
* A "blank" central repository (when the distinction makes sense for the VCS
  being used)

Supported Version Control Systems
---------------------------------

This module supports a wide range of VCS types, each represented by a
separate provider.

For information on how to use this module with a specific VCS, see
`README.<VCS>.markdown`.

License
-------

See LICENSE.
