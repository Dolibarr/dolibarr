Using vcsrepo with Bazaar
=========================

To create a blank repository
----------------------------

Define a `vcsrepo` without a `source` or `revision`:

    vcsrepo { "/path/to/repo":
      ensure   => present,
      provider => bzr
    }

To branch from an existing repository
-------------------------------------

Provide the `source` location:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => bzr,
        source   => 'lp:myproj'
    }

For a specific revision, use `revision` with a valid revisionspec
(see `bzr help revisionspec` for more information on formatting a revision):

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => bzr,
        source   => 'lp:myproj',
        revision => 'menesis@pov.lt-20100309191856-4wmfqzc803fj300x'
    }

For sources that use SSH (eg, `bzr+ssh://...`, `sftp://...`)
------------------------------------------------------------

Manage your SSH keys with Puppet and use `require` in your `vcsrepo`
to ensure they are present.  For more information, see the `require`
metaparameter documentation[1].

More Examples
-------------

For examples you can run, see `examples/bzr/`

[1]: http://docs.puppetlabs.com/references/stable/metaparameter.html#require
