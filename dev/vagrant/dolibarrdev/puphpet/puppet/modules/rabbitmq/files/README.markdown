Files
=====

Puppet comes with both a client and server for copying files around. The file
serving function is provided as part of the central Puppet daemon,
puppetmasterd, and the client function is used through the source attribute of
file objects. Learn more at
http://projects.puppetlabs.com/projects/puppet/wiki/File_Serving_Configuration

You can use managed files like this:

    class myclass {
      package { mypackage: ensure => latest }
      service { myservice: ensure => running }
      file { "/etc/myfile":
        source => "puppet://$servername/modules/mymodule/myfile"
      }
    }

The files are searched for in:

    $modulepath/mymodule/files/myfile
