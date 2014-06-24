Using vcsrepo with Mercurial
============================

To create a blank repository
----------------------------

Define a `vcsrepo` without a `source` or `revision`:

    vcsrepo { "/path/to/repo":
      ensure   => present,
      provider => hg
    }

To clone/pull & update a repository
-----------------------------------

To get the default branch tip:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => hg,
        source   => "http://hg.example.com/myrepo"
    }

For a specific changeset, use `revision`:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => hg,
        source   => "http://hg.example.com/myrepo",
        revision => '21ea4598c962'
    }

You can also set `revision` to a tag:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => hg,
        source   => "http://hg.example.com/myrepo",
        revision => '1.1.2'
    }

Check out as a user:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => hg,
        source   => "http://hg.example.com/myrepo",
        user     => 'user'
    }

Specify an SSH identity key:

    vcsrepo { "/path/to/repo":
        ensure   => present,
        provider => hg,
        source   => "ssh://hg@hg.example.com/myrepo",
        identity => "/home/user/.ssh/id_dsa,
    }

For sources that use SSH (eg, `ssh://...`)
------------------------------------------

Manage your SSH keys with Puppet and use `require` in your `vcsrepo`
to ensure they are present.  For more information, see the `require`
metaparameter documentation[1].

More Examples
-------------

For examples you can run, see `examples/hg/`

[1]: http://docs.puppetlabs.com/references/stable/metaparameter.html#require
