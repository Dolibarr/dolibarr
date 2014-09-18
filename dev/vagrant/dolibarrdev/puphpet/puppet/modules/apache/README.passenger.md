# Passenger

Just enabling the Passenger module is insufficient for the use of Passenger in
production. Passenger should be tunable to better fit the environment in which
it is run while being aware of the resources it required.

To this end the Apache passenger module has been modified to apply system wide
Passenger tuning declarations to `passenger.conf`. Declarations specific to a
virtual host should be passed through when defining a `vhost` (e.g.
`rack_base_uris` parameter on the `apache::vhost` type, check `README.md`).

Also, general apache module loading parameters can be supplied to enable using
a customized passenger module in place of a default-package-based version of
the module.

# Operating system support and Passenger versions

The most important configuration directive for the Apache Passenger module is
`PassengerRoot`. Its value depends on the Passenger version used (2.x, 3.x or
4.x) and on the operating system package from which the Apache Passenger module
is installed.

The following table summarises the current *default versions* and
`PassengerRoot` settings for the operating systems supported by
puppetlabs-apache:

OS               | Passenger version  | `PassengerRoot` 
---------------- | ------------------ | ----------------
Debian 7         | 3.0.13             | /usr
Ubuntu 12.04     | 2.2.11             | /usr
Ubuntu 14.04     | 4.0.37             | /usr/lib/ruby/vendor_ruby/phusion_passenger/locations.ini 
RHEL with EPEL6  | 3.0.21             | /usr/lib/ruby/gems/1.8/gems/passenger-3.0.21 

As mentioned in `README.md` there are no compatible packages available for
RHEL/CentOS 5 or RHEL/CentOS 7.

## Configuration files and locations on RHEL/CentOS

Notice two important points:

1. The Passenger version packaged in the EPEL repositories may change over time.
2. The value of `PassengerRoot` depends on the Passenger version installed.

To prevent the puppetlabs-apache module from having to keep up with these
package versions the Passenger configuration files installed by the
packages are left untouched by this module. All configuration is placed in an
extra configuration file managed by puppetlabs-apache.

This means '/etc/httpd/conf.d/passenger.conf' is installed by the
`mod_passenger` package and contains correct values for `PassengerRoot` and
`PassengerRuby`. Puppet will ignore this file. Additional configuration
directives as described in the remainder of this document are placed in
'/etc/httpd/conf.d/passenger_extra.conf', managed by Puppet.

This pertains *only* to RHEL/CentOS, *not* Debian and Ubuntu.

## Third-party and custom Passenger packages and versions

The Passenger version distributed by the default OS packages may be too old to
be useful. Newer versions may be installed via Gems, from source or from
third-party OS packages.

Most notably the Passenger developers officially provide Debian packages for a
variety of Debian and Ubuntu releases in the [Passenger APT
repository](https://oss-binaries.phusionpassenger.com/apt/passenger). Read more
about [installing these packages in the offical user
guide](http://www.modrails.com/documentation/Users%20guide%20Apache.html#install_on_debian_ubuntu).

If you install custom Passenger packages and newer version make sure to set the
directives `PassengerRoot`, `PassengerRuby` and/or `PassengerDefaultRuby`
correctly, or Passenger and Apache will fail to function properly.

For Passenger 4.x packages on Debian and Ubuntu the `PassengerRoot` directive
should almost universally be set to
`/usr/lib/ruby/vendor_ruby/phusion_passenger/locations.ini`.

# Parameters for `apache::mod::passenger`

The following class parameters configure Passenger in a global, server-wide
context.

Example:

```puppet
class { 'apache::mod::passenger':
  passenger_root             => '/usr/lib/ruby/vendor_ruby/phusion_passenger/locations.ini',
  passenger_default_ruby     => '/usr/bin/ruby1.9.3',
  passenger_high_performance => 'on',
  rails_autodetect           => 'off',
  mod_lib_path               => '/usr/lib/apache2/custom_modules',
}
```

The general form is using the all lower-case version of the configuration
directive, with underscores instead of CamelCase.

## Parameters used with passenger.conf

If you pass a default value to `apache::mod::passenger` it will be ignored and
not passed through to the configuration file. 

### passenger_root

The location to the Phusion Passenger root directory. This configuration option
is essential to Phusion Passenger, and allows Phusion Passenger to locate its
own data files. 

The default depends on the Passenger version and the means of installation. See
the above section on operating system support, versions and packages for more
information.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#_passengerroot_lt_directory_gt

### passenger_default_ruby

This option specifies the default Ruby interpreter to use for web apps as well
as for all sorts of internal Phusion Passenger helper scripts, e.g. the one
used by PassengerPreStart.

This directive was introduced in Passenger 4.0.0 and will not work in versions
< 4.x. Do not set this parameter if your Passenger version is older than 4.0.0.

Defaults to `undef` for all operating systems except Ubuntu 14.04, where it is
set to '/usr/bin/ruby'.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#PassengerDefaultRuby

### passenger_ruby

This directive is the same as `passenger_default_ruby` for Passenger versions
< 4.x and must be used instead of `passenger_default_ruby` for such versions.

It makes no sense to set `PassengerRuby` for Passenger >= 4.x. That
directive should only be used to override the value of `PassengerDefaultRuby`
on a non-global context, i.e. in `<VirtualHost>`, `<Directory>`, `<Location>`
and so on.

Defaults to `/usr/bin/ruby` for all supported operating systems except Ubuntu
14.04, where it is set to `undef`.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#PassengerRuby

### passenger_high_performance

Default is `off`. When turned `on` Passenger runs in a higher performance mode
that can be less compatible with other Apache modules.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#PassengerHighPerformance

### passenger_max_pool_size

Sets the maximum number of Passenger application processes that may
simultaneously run. The default value is 6.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#_passengermaxpoolsize_lt_integer_gt

### passenger_pool_idle_time

The maximum number of seconds a Passenger Application process will be allowed
to remain idle before being shut down. The default value is 300.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#PassengerPoolIdleTime

### passenger_max_requests

The maximum number of request a Passenger application will process before being
restarted. The default value is 0, which indicates that a process will only
shut down if the Pool Idle Time (see above) expires.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#PassengerMaxRequests

### passenger_stat_throttle_rate

Sets how often Passenger performs file system checks, at most once every _x_
seconds. Default is 0, which means the checks are performed with every request.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#_passengerstatthrottlerate_lt_integer_gt

### rack_autodetect

Should Passenger automatically detect if the document root of a virtual host is
a Rack application. Not set by default (`undef`). Note that this directive has
been removed in Passenger 4.0.0 and `PassengerEnabled` should be used instead.
Use this directive only on Passenger < 4.x.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#_rackautodetect_lt_on_off_gt

### rails_autodetect

Should Passenger automatically detect if the document root of a virtual host is
a Rails application.  Not set by default (`undef`). Note that this directive
has been removed in Passenger 4.0.0 and `PassengerEnabled` should be used
instead. Use this directive only on Passenger < 4.x.

http://www.modrails.com/documentation/Users%20guide%20Apache.html#_railsautodetect_lt_on_off_gt

### passenger_use_global_queue

Allows toggling of PassengerUseGlobalQueue.  NOTE: PassengerUseGlobalQueue is
the default in Passenger 4.x and the versions >= 4.x have disabled this
configuration option altogether.  Use with caution.

## Parameters used to load the module

Unlike the tuning parameters specified above, the following parameters are only
used when loading customized passenger modules.

### mod_package

Allows overriding the default package name used for the passenger module
package.

### mod_package_ensure

Allows overriding the package installation setting used by puppet when
installing the passenger module. The default is 'present'.

### mod_id

Allows overriding the value used by apache to identify the passenger module.
The default is 'passenger_module'.

### mod_lib_path

Allows overriding the directory path used by apache when loading the passenger
module. The default is the value of `$apache::params::lib_path`.

### mod_lib

Allows overriding the library file name used by apache when loading the
passenger module. The default is 'mod_passenger.so'.

### mod_path

Allows overriding the full path to the library file used by apache when loading
the passenger module. The default is the concatenation of the `mod_lib_path`
and `mod_lib` parameters.

# Dependencies

RedHat-based systems will need to configure additional package repositories in
order to install Passenger, specifically:

* [Extra Packages for Enterprise Linux](https://fedoraproject.org/wiki/EPEL)
* [Phusion Passenger](http://passenger.stealthymonkeys.com)

Configuration of these repositories is beyond the scope of this module and is
left to the user.

# Attribution

The Passenger tuning parameters for the `apache::mod::passenger` Puppet class
was modified by Aaron Hicks (hicksa@landcareresearch.co.nz) for work on the
NeSI Project and the Tuakiri New Zealand Access Federation as a fork from the
PuppetLabs Apache module on GitHub.

* https://github.com/puppetlabs/puppetlabs-apache
* https://github.com/nesi/puppetlabs-apache
* http://www.nesi.org.nz//
* https://tuakiri.ac.nz/confluence/display/Tuakiri/Home

# Copyright and License

Copyright (C) 2012 [Puppet Labs](https://www.puppetlabs.com/) Inc

Puppet Labs can be contacted at: info@puppetlabs.com

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

  http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.
