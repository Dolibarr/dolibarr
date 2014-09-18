# Puppet Supervisord

[![Build Status](https://travis-ci.org/ajcrowe/puppet-supervisord.png?branch=master)](https://travis-ci.org/ajcrowe/puppet-supervisord)

Puppet module to manage the [supervisord](http://supervisord.org/) process control system.

Functions available to configure

* [programs](http://supervisord.org/configuration.html#program-x-section-settings)
* [groups](http://supervisord.org/configuration.html#group-x-section-settings)
* [fcgi-programs](http://supervisord.org/configuration.html#fcgi-program-x-section-settings)
* [eventlisteners](http://supervisord.org/configuration.html#eventlistener-x-section-settings)

## Examples

### Configuring supervisord with defaults

Install supervisord with pip and install an init script if available

```ruby
include supervisord
```

### Install supervisord and pip

Install supervisord and install pip if not available.

```ruby
class supervisord {
  $install_pip  => true,
}
```

This will download [setuptool](https://bitbucket.org/pypa/setuptools) and install pip with easy_install.

You can pass a specific url with `$setuptools_url = 'url'`

### Install without pip

If you want to use your system package manager you can specify that with `supervisord::package_provider`.

You'll also likely need to adjust the `supervisord::service_name` to match that installed by the system package. If you're using Debian or Redhat OS families you'll also want to disable the init scripts with `supervisord::install_init = false`.

Note: Only Debian and RedHat families have an init script currently.

### Configure a program

```ruby
supervisord::program { 'myprogram':
  command     => 'command --args',
  priority    => '100',
  environment => {
    'HOME'   => '/home/myuser',
    'PATH'   => '/bin:/sbin:/usr/bin:/usr/sbin',
    'SECRET' => 'mysecret'
  }
}
```

You may also specify a variable for a hiera lookup to retreive your environment hash. This allows you to reuse existing environment variable hashes.

```ruby
supervisord::program { 'myprogram':
  command  => 'command --args',
  priority => '100',
  env_var  => 'my_common_envs'
}
```

Or you can fully define your programs in hiera:

```yaml
supervisord::programs:
  'myprogram':
    command: 'command --args'
    autostart: yes
    autorestart: 'true'
    environment:
      HOME: '/home/myuser'
      PATH: '/bin:/sbin:/usr/bin:/usr/sbin'
      SECRET: 'mysecret'
```

### Configure a group

```ruby
supervisord::group { 'mygroup':
  priority => 100,
  programs  => ['program1', 'program2', 'program3']
}
```

### Configure an eventlistener

```ruby
supervisord::eventlistener { 'mylistener':
  command  => 'command --args',
  events   => ['PROCESS_STATE', 'PROCESS_STATE_START']
  priority => '100',
  env_var  => 'my_common_envs'
}
```

### Run supervisorctl Commands

Should you need to run a sequence of command with `supervisorctl` you can use the define type `supervisord::supervisorctl`

```ruby
supervisord::supervisorctl { 'restart_myapp':
  command => 'restart',
  process => 'myapp'
}
```

You can also issue a command without specifying a process.

### Development

If you have suggestions or improvements please file an issue or pull request, i'll try and sort them as quickly as possble.

If you submit a pull please try and include tests for the new functionality/fix. The module is tested with [Travis-CI](https://travis-ci.org/ajcrowe/puppet-supervisord).


### Credits

* Debian init script sourced from the system package.
* RedHat/Centos init script sourced from https://github.com/Supervisor/initscripts
