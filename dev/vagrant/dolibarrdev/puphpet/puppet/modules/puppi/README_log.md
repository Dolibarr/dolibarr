# PUPPI LOG README
Documentation and examples related to the puppi action log

## SYNOPSIS (cli)
        puppi log [topic] [-i]

## EXAMPLES (cli)

Tails (tail -10f) all the known logs.
        puppi log

Tails only the logs related to the given topic
        puppi log apache

Choose interactively which logs to show
        puppi log

Grep the output with the string defined
        puppi log -g <string>

## EXAMPLES (puppet)
The basic define related to a log is:
        puppi::log
it creates a file in /etc/puppi/logs/ with one or more logs paths.

A simple, operating system aware, example might be:
        puppi::log { 'auth':
          description => 'Users and authentication' ,
          log => $::operatingsystem ? { 
            redhat => '/var/log/secure',
            darwin => '/var/log/secure.log',
            ubuntu => ['/var/log/user.log','/var/log/auth.log'],
          }
        }

but also something that uses variables Puppet already knows
        puppi::log { "tomcat-${instance_name}":
          log => "${tomcat::params::storedir}/${instance_name}/logs/catalina.out"
        }

EXAMPLES (with example42 puppet modules)
If you use the old Example42 modules set you get automatically many service related logs out of the box to be used with Puppi One.
NextGen modules are supposed to provide puppi log intergration on Puppi Two (TO DO)

