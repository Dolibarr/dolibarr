# PUPPI CHECK INSTRUCTIONS
Documentation and examples related to the puppi action check

## SYNOPSIS (cli)
        puppi check [project_name] [-r yes|no|fail]

## EXAMPLES (cli)
Run host-wide checks.
        puppi check
Run project "myapp" specific tests AND host-wide checks
        puppi check myapp

Run checks and send reports only if some of them fail
        puppi check -r fail

Run checks and send reports
        puppi check -r yes

Run checks and show only failed ones
        puppi check -s fail

## EXAMPLES (puppet)
The basic define related to a check is:
        puppi::check   - Creates a single command to be placed in the check sequence.

A simple example might be:
        puppi::check { 'Port_Apache':
          command  => "check_tcp -H ${fqdn} -p 80" ,
        }

but also something that uses variables Puppet already knows
        puppi::check { 'apache_process':
          command  => "check_procs  -c 1: -C ${apache::params::processname}" ,
        }

To avoid repetitions you can include the relevant checks in defines you already have
to manage, for example, virtualhosts, and use the data you already provide to configure
their local puppi checks. 
        puppi::check { "Url_$name":
          enable   => $enable,
          command  => "check_http -I '${target}' -p '${port}' -u '${url}' -s '${pattern}'" ,
        }

You can also use custom scripts for your checks. They should behave similarly to Nagios plugins inn their exit codes: 0 for SUCCESS, 1 for WARNINGS, 2 for CRITICAL. In this case you've to specify the directory there the scripts stays:
        puppi::check { 'my_stack':
          command  => 'stack_check.sh',
          bade_dir => '/usr/bin',
        }


## EXAMPLES (with example42 puppet modules)
If you use the whole Example42 modules set you get automatically many service related checks out of the box.
Just set (via an ENC, facts or manifests) these puppet variables:
        $monitor="yes" # To enable automagic monitoring
        $monitor_tool = "puppi"  # As monitoring tool define at least puppi. If you like Nagios, you may use:
        $monitor_tool = ["nagios","puppi"] # This enables the below checks both for Puppi and Nagios
        $puppi=yes # To enable puppi extensions autoloading

To the port and service checks automatically added for the included modules, you can add custom url checks 
with something like:
        monitor::url { "URL_Check_Database_Connection":
          url      => "http://www.example42.com/check/db",
          pattern  => 'SUCCESS',
          port     => '80',
          target   => "${fqdn}",
        }

