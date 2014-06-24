# Puppi: Puppet Knowledge to the CLI

Puppi One and Puppi module written by Alessandro Franceschi / al @ lab42.it

Puppi Gem by Celso Fernandez / Zertico

Source: http://www.example42.com

Licence: Apache 2

Puppi is a Puppet module and a CLI command.
It's data is entirely driven by Puppet code.
It can be used to standardize and automate the deployment of web applications
or to provides quick and standard commands to query and check your system's resources

Its structure provides FULL flexibility on the actions required for virtually any kind of
application deployment and information gathering.

The module provides:

* Old-Gen and Next-Gen Puppi implementation

* A set of scripts that can be used in chain to automate any kind of deployment

* Puppet defines that make it easy to prepare a puppi set of commands for a project deployment

* Puppet defines to populate the output of the different actions


## HOW TO INSTALL

Download Puppi from GitHub and place it in your modules directory: 

       git clone https://github.com/example42/puppi.git /etc/puppet/modules/puppi

To use the Puppi "Original, old and widely tested" version, just declare or include the puppi class

       class { 'puppi': }

To test the Next-Gen version you can perform the following command. Please note that this module is
not stable yet:
        class { 'puppi':
          version => '2',
        }

If you have resources conflicts, do not install automatically the Puppi dependencies (commands and packages)

        class { 'puppi':
          install_dependencies => false,
        }


## HOW TO USE

Once Puppi is installed you can use it to:

* Easily define in Puppet manifests Web Applications deploy procedures. For example:

        puppi::project::war { "myapp":
            source           => "http://repo.example42.com/deploy/prod/myapp.war",
            deploy_root      => "/opt/tomcat/myapp/webapps",
        }

* Integrate with your modules for puppi check, info and log 

* Enable Example42 modules integration


## HOW TO USE WITH EXAMPLE42 MODULES

The Example42 modules provide (optional) Puppi integration.
Once enabled for each module you have puppi check, info and log commands.

To eanble Puppi in OldGen Modules, set in the scope these variables:

        $puppi = yes   # Enables puppi integration.
        $monitor = yes # Enables automatic monitoring 
        $monitor_tool = "puppi" # Sets puppi as monitoring tool

For the NextGen modules set the same parameters via Hiera, at Top Scope or as class arguments:

        class { 'openssh':
          puppi        => yes, 
          monitor      => yes,
          monitor_tool => 'puppi', 
        }


## USAGE OF THE PUPPI COMMAND (OLD GEN)

        puppi <action> <project_name> [ -options ]

The puppi command has these possibile actions:

First time initialization of the defined project (if available)
        puppi init <project>

Deploy the specified project
        puppi deploy <project>

Rollback to a previous deploy state
        puppi rollback <project>

Run local checks on system and applications
        puppi check

Tail system or application logs
        puppi log

Show system information (for all or only the specified topic)
        puppi info [topic]

Show things to do (or done) manually on the system (not done via Puppet)
        puppi todo

In the deploy/rollback/init actions, puppi runs the commands in /etc/puppi/projects/$project/$action, logs their status and then run the commands in /etc/puppi/projects/$project/report to provide reporting, in whatever, pluggable, way.

You can also provide some options:

* -f : Force puppi commands execution also on CRITICAL errors

* -i : Interactively ask confirmation for every command

* -t : Test mode. Just show the commands that should be executed without doing anything

* -d <yes|full>: Debug mode. Show debugging info during execution

* -o "parameter=value parameter2=value2" : Set manual options to override defaults. The options must be in parameter=value syntax, separated by spaces and inside double quotes.


Some common puppi commnds when you log for an application deployment:

        puppi check
        puppi log &    # (More readable if done on another window)
        puppi deploy myapp
        puppi check
        puppi info myapp


## THE PUPPI MODULE

The set of commands needed for each of these actions are entirely managed with specific
Puppet "basic defines":

Create the main project structure. One or more different deployment projects can exist on a node.
        puppi::project

Create a single command to be placed in the init sequence. It's not required for every project.
        puppi::initialize

Create a single command to be placed in the deploy sequence. More than one is generally needed for each project.
        puppi::deploy

Create a single command to be placed in the rollback sequence. More than one is generally needed for each project.
        puppi::rollback

Create a single check (based on Nagios plugins) for a project or for the whole host (host wide checks are auto generated by Example42 monitor module)
        puppi::check

Create a reporting command to be placed in the report sequence.
        puppi::report

Create a log filename entry for a project or the whole hosts.
        puppi::log

Create an info entry with the commands used to provide info on a topic
        puppi::info

Read details in the relevant READMEs


## FILE PATHS (all of them are provided, and can be configured, in the puppi module):

A link to the actual version of puppi enabled
        /usr/sbin/puppi

The original puppi bash command.
        /usr/sbin/puppi.one

Puppi (one) main config file. Various puppi wide paths are defined here.
        /etc/puppi/puppi.conf

Directory where by default all the host wide checks can be placed. If you use the Example42 monitor module and have "puppi" as $monitor_tool, this directory is automatically filled with Nagios plugins based checks.
        /etc/puppi/checks/ ($checksdir)

Directory that containts projects subdirs, with the commands to be run for deploy, rollback and check actions. They are completely built (and purged) by the Puppet module.
        /etc/puppi/projects/ ($projectsdir)

The general-use scripts directory, these are used by the above commands and may require one or more arguments.
        /etc/puppi/scripts/ ($scriptsdir)

The general-use directory where files are placed which contain the log paths to be used by puppi log
        /etc/puppi/logs/ ($logssdir)

The general-use directory where files are placed which contain the log paths to be used by puppi log
        /etc/puppi/info/ ($infodir)

Where all data to rollback is placed.
        /var/lib/puppi/archive/ ($archivedir)

Where logs and reports of the different commands are placed.
        /var/log/puppi/ ($logdir)

Temporary, scratchable, directory where Puppi places temporary files.
        /tmp/puppi/ ($workdir)

A runtime configuration file, which is used by all all the the scripts invoked by puppi to read and write dynamic variables at runtime. This is necessary to mantain "state" information that changes on every puppi run (such as the deploy datetime, used for backups).
        /tmp/puppi/$project/config


## HOW TO CUSTOMIZE
It should be clear that with puppi you have full flexibility in the definition of a deployment 
procedure, since the puppi command is basically a wrapper that executes arbitrary scripts with
a given sequence, in pure KISS logic.

The advantanges though, are various:
* You have a common syntax to manage deploys and rollbacks on an host

* In your Puppet manifests, you can set in simple, coherent and still flexible and customizable
  defines all the elements, you need for your application deployments. 
  Think about it: with just a Puppet define you build the whole deploy logic

* Reporting for each deploy/rollback is built-in and extensible 

* Automatic checks can be built in the deploy procedure

* You have a common, growing, set of general-use scripts for typical actions

* You have quick and useful command to see what's happening on the system (puppi check, log, info)

There are different parts where you can customize the behaviour of puppi:

* The set of general-use scripts in /etc/puppi/scripts/ ( this directory is filled with the content
  of puppi/files/scripts/ ) can/should be enhanced. These can be arbitrary scripts in whatever
  language. If you want to follow puppi's logic, though, consider that they should import the
  common and runtime configuration files and have an exit code logic similar to the one of
  Nagios plugins: 0 is OK, 1 is WARNING, 2 is CRITICAL. Note that by default a script that 
  exits with WARNING doesn't block the deploy procedure, on the other hand, if a script exits
  with CRITICAL (exit 2) by default it blocks the procedure.
  Take a second, also, to explore the runtime config file created by the puppi command that
  contains variables that can be set and used by the scripts invoked by puppi.

* The custom project defines that describe deploy templates. These are placed in
  puppi/manifests/project/ and can request all the arguments you want to feed your scripts with.
  Generally is a good idea to design a standard enough template that can be used for all the 
  cases where the deployment procedure involves similar steps. Consider also that you can handle
  exceptions with variables (see the $loadbalancer_ip usage in puppi/manifests/project/maven.pp)


## (NO) DEPENDENCIES AND CONFLICTS
Puppi is self contained. It doesn't require other modules.
(And is required by all Example42 modules).

For correct functionality by default some extra packages are installed.
If you have conflicts with your existing modules, set the argument:
  install_dependencies => false

