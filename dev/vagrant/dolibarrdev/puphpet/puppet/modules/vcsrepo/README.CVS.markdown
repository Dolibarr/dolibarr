Using vcsrepo with CVS
======================

To create a blank repository
----------------------------

Define a `vcsrepo` without a `source` or `revision`:

    vcsrepo { "/path/to/repo":
      ensure => present,
      provider => cvs
    }

To checkout/update from a repository
------------------------------------

To get the current mainline:

    vcsrepo { "/path/to/workspace":
        ensure => present,
        provider => cvs,
        source => ":pserver:anonymous@example.com:/sources/myproj"
    }
    
To get a specific module on the current mainline:

    vcsrepo {"/vagrant/lockss-daemon-source":
        ensure   => present,
        provider => cvs,
        source   => ":pserver:anonymous@lockss.cvs.sourceforge.net:/cvsroot/lockss",
        module   => "lockss-daemon",
    }


You can use the `compression` parameter (it works like CVS `-z`):

    vcsrepo { "/path/to/workspace":
        ensure => present,
        provider => cvs,
        compression => 3,
        source => ":pserver:anonymous@example.com:/sources/myproj"
    }

For a specific tag, use `revision`:

    vcsrepo { "/path/to/workspace":
        ensure => present,
        provider => cvs,
        compression => 3,
        source => ":pserver:anonymous@example.com:/sources/myproj",
        revision => "SOMETAG"
    }

For sources that use SSH
------------------------

Manage your SSH keys with Puppet and use `require` in your `vcsrepo`
to ensure they are present.  For more information, see the `require`
metaparameter documentation[1].

More Examples
-------------

For examples you can run, see `examples/cvs/`

[1]: http://docs.puppetlabs.com/references/stable/metaparameter.html#require
