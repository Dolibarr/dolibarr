Using vcsrepo with Subversion
=============================

To create a blank repository
----------------------------

To create a blank repository suitable for use as a central repository,
define a `vcsrepo` without a `source` or `revision`:

    vcsrepo { "/path/to/repo":
      ensure   => present,
      provider => svn
    }

To checkout from a repository
-----------------------------

Provide a `source` qualified to the branch/tag you want:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => svn,
        source   => "svn://svnrepo/hello/branches/foo"
    }

You can provide a specific `revision`:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => svn,
        source   => "svn://svnrepo/hello/branches/foo",
        revision => '1234'
    }


Using a specified Subversion configuration directory 
-----------------------------

Provide a `configuration` parameter which should be a directory path on the local system where your svn configuration
files are.  Typically, it is /path/to/.subversion:

    vcsrepo { "/path/to/repo":
        ensure        => present,
        provider      => svn,
        source        => "svn://svnrepo/hello/branches/foo",
        configuration => "/path/to/.subversion"
    }


For sources that use SSH (eg, `svn+ssh://...`)
----------------------------------------------

Manage your SSH keys with Puppet and use `require` in your `vcsrepo`
to ensure they are present.  For more information, see the `require`
metaparameter documentation[1].

More Examples
-------------

For examples you can run, see `examples/svn/`

[1]: http://docs.puppetlabs.com/references/stable/metaparameter.html#require
