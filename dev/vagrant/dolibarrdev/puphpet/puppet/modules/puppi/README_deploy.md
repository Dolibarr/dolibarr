# PUPPI DEPLOY INSTRUCTIONS
Documentation and examples related to the puppi actions: deploy, rollback and init

## SYNOPSIS (COMMAND LINE)
Shell command to launch a deploy:
        puppi deploy <project_name>
        puppi deploy <project_name> [-f] [-i] [-t] [-d yes|full] [-r yes|no|fail] [-p "parameter=value parameter2=value2"]

Shell command to launch a rollback:
        puppi rollback <project_name> 

Shell command to launch the first deploy:
        puppi init <project_name>


## EXAMPLES (cli)

Deploy myapp with the standard logic/parameters defined in Puppet:
        puppi deploy myapp

Deploy myapp and doesn't stop in case of Critical errors:
        puppi deploy myapp -f

Deploy myapp in interactive mode. Confirmation is asked for each step
        puppi deploy myapp -i

Test mode. Just show the commands that would be executed
        puppi deploy myapp -t

Deploy myapp with full debugging output
        puppi deploy myapp -d full

Deploy myapp in interactive mode and sets some custom options that override the standard Puppet params.
Note that these parameters change according to the script you use (and the scripts must honour this override in order to make this option work).
        puppi deploy myapp -i -o "version=1.1 source_url=http://dev.example42.com/code/my_app/"

Make the first deploy of "myapp". Can be optional and may require a subsequent puppi deploy myapp
        puppi init myapp

Rollback myapp to a previous archived state. User is asked to choose which deploy to override. Note that by default you have 5 backups to rollback from. Older backups are automatically removed.
        puppi rollback myapp

Automatically rollback to the latest saved state (unattended).
        puppi rollback myapp latest


## EXAMPLES (puppet)
Here follow some sample defines you can use out of the box in your manifests once you include puppi.
Get a war from $source and deploy it in $deploy_root:
        puppi::project::war { 'myapp':
          source           => 'http://repo.example42.com/deploy/prod/myapp.war',
          deploy_root      => '/store/tomcat/myapp/webapps',
        }

Get a tarball from $source, unpack it in $deploy_root, restart service called apache and send a mail
to $report_mail (you can have a comma separated list of destination addresses):
        puppi::project::tar { 'mysite':
          source           => 'rsync://repo.example42.com/deploy/prod/release.tgz',
          init_script      => 'apache',
          deploy_root      => '/var/www/html/',
          report_email     => 'sysadmins@example42.com',
          enable           => 'true', # Redundant
        }

Get a tarfile with a .sql query file and apply to to you local Mysql with the credentials provided:
        puppi::project::mysql { 'myweb_sql':
          source          => 'http://repo.example42.com/deploy/prod/database.sql.gz',
          mysql_user      => 'myweb',
          mysql_password  => $secret::mysql_myweb_pw,
          mysql_host      => 'localhost',
          mysql_database  => 'myweb',
          report_email    => 'sysadmins@example42.com,dbadmins@example42.com',
          enable          => 'true',
        }

Get a list of files from $source, retrieve the actual files from $source_baseurl and place them
in $deploy_root
        puppi::project::files { 'gfxupdates':
          source           => 'http://deploy.example42.com/prod/website2/list.txt',
          source_baseurl   => 'http://design.example42.com/website2/gfx/',
          deploy_root      => '/var/www/html/gfx',
          report_email     => 'sysadmins@example42.com,designers@example42.com',
          enable           => 'true',
        }

Deploy from a Nexus repository (retrieve maven-metadata.xml from dir specified in $source), get the war 
(version is achieved from the "release" tag in the xml) and deploy it in $deploy_root and then restart tomcat.
        puppi::project::maven { 'supersite':
          source           => 'http://nexus.example42.com/nexus/content/repositories/releases/it/example42/supersite/',
          deploy_root      => '/usr/local/tomcat/supersite/webapps',
          init_script      => 'tomcat',
          report_email     => 'sysadmins@example42.com',
          enable           => 'true',
        }

Get the maven-metadata.xml from a Nexus repository and deploy:
- The release war in $deploy_root
- A configurations tarball tagged with the Maven qualifier $config_suffix in $config_root
- A static files tarball tagged with the Maven qualifier $document_suffix in $document_root
        puppi::project::maven { 'supersite':
          source           => 'http://nexus.example42.com/nexus/content/repositories/releases/it/example42/supersite/',
          deploy_root      => '/usr/local/tomcat/supersite/webapps',
          config_suffix    => 'cfg',
          config_root      => '/srv/htdocs/supersite',
          document_suffix  => 'css',
          document_root    => '/srv/htdocs/supersite',
          init_script      => 'tomcat',
          report_email     => 'sysadmins@example42.com',
          enable           => 'true',
        }

The same deploy Nexus repository with some more options:
- A Source dir to be used to deploy static files when issuing "puppi init supersite"
- A block from a loadbalancer IP (managing different sites addresess)
- Some more elaborate rsync exclusion rules
- A backup retention of 3 archives (instead of the default 5)
        puppi::project::maven { 'supersite':
          source           => 'http://nexus.example42.com/nexus/content/repositories/releases/it/example42/supersite/',
          deploy_root      => '/usr/local/tomcat/supersite/webapps',
          config_suffix    => 'cfg',
          config_root      => '/srv/htdocs/supersite',
          document_suffix  => 'css',
          document_root    => '/srv/htdocs/supersite',
          document_init_source => 'rsync://backup.example42.com/initdir/supersite/',
          firewall_src_ip  => $site ? {
              dr      => '192.168.101.1/30',
              main    => '192.168.1.1/30',
          },
          backup_rsync_options => '--exclude .snapshot --exclude /doc_root/autojs/*** --exclude /doc_root/autocss/*** --exclude /doc_root/xsl',
          backup_retention => '3',
          init_script      => 'tomcat',
          report_email     => 'sysadmins@example42.com',
          enable           => 'true',
        }

An elaborated war deploy:
- get from $source,
- execute /usr/local/bin/mychecks.sh as root before the deploy
(or better with sequence number 39)
- deploy to /data/tomcat/myapp/webapps as user pippo
- stop and start tomcat-myapp but also monit and puppet
- backup passing  $backup_rsync_options to rsync:
        puppi::project::war { 'myapp':
          source                  => 'http://repo.example42.com/deploy/prod/myapp.war',
          deploy_root             => '/store/tomcat/myapp/webapps',
          predeploy_customcommand => '/usr/local/bin/mychecks.sh',
          predeploy_priority      => '39',
          predeploy_user          => 'root',
          backup_rsync_options    => '--exclude logs/',
          user                    => 'pippo',
          init_script             => 'tomcat-myapp',
          deploy_root             => '/data/tomcat/myapp/webapps',
          report_email            => 'sysadmins@example42.com',
          disable_services        => 'monit puppet',
        }
An example of usage of the generic builder define to deploy a zip file, with an example custom
post deploy command executed as root (as all puppi commands, if not specified otherwise)
        puppi::project::builder { 'cms':
          source                   => 'http://repo.example42.com/deploy/cms/cms.zip',
          source_type              => 'zip',
          user                     => 'root',
          deploy_root              => '/var/www',
          postdeploy_customcommand => 'chown -R www-data /var/www/files',
          postdeploy_user          => 'root',
          postdeploy_priority      => '41',
          report_email             => 'sysadmins@example42.com',
          enable                   => 'true',
        }

These are just examples, possibilities are many, refer to the docs in puppi/manifests/project/*.pp
to have a full list of the available options.


## BASIC PUPPI DEFINES
The above puppi::projects defines manage more or less complex deployments procedures for different kind of projects. They are just samples of possible procedures and you may create your one ones, if the ones provided there don't fit your needs.
They will have to contain one or more of these basic puppi defines.

Create the main project structure. One or more different deployment projects can exist on a node.
        puppi::project

Create a single command to be placed in the init sequence. It's not required for every project.
        puppi::initialize

Create a single command to be placed in the deploy sequence. More than one is generally needed for each project.
        puppi::deploy

Create a single command to be placed in the rollback sequence. More than one is generally needed for each project.
         puppi::rollback

These defines have generally a standard structure and similar arguments.
Every one is reversable (enable => false) but you can wipe out the whole /etc/puppi directory
to have it rebuilt from scratch. Here is an example for a single deploy command:
        puppi::deploy { 'Retrieve files':       # The $name of the define is used in the file name
          command  => 'get_curl.sh',          # The name of the general-use script to use
          argument => 'file:///storage/file', # The argument(s) passed to the above script
          priority => '10',                   # Lower priority scripts are executed first
          user     => 'root',                 # As what user we run the script
          project  => 'my-web.app',           # The name of the project to use
        }

This define creates a file named:
        /etc/puppi/projects/${project}/deploy/${priority}-${name}
Its content is, simply:
        su - ${user} -c "export project=${project} && /etc/puppi/scripts/${command} ${arguments}"


## DEPLOY PROCEDURES DEFINES
The puppi module provides some examples of deploy workflows they are in the puppi/manifests/project
directory and contain simple to use defines that contain one puppi::project and several puppi::deploy and
puppi::rollback defines to design a specific workflow using the builtin commands present in
puppi/files/scripts.
Note that if you need to design your own deploy procedure you have different options:
- Verify if you can reuse the existing ones, using optional arguments as pre/postdeploy_commands
- Use the existing ones as a base to make you own custom defines, reusing parts of their logic 
  and the builtin commands (puppi/files/scripts/*) they use
- Write your own commands (in whatever language) and integrate them in your own procedure.

Here follow the main and most reusable deploy workflows defines available in puppi/manifests/project/.
The have generally a set of common arguments that make you manage if to stop and restart specific 
services, if you want to isolate your server from a loadbalancer during the deploy procedure, what to backup,
how many backup copies to mantain, if to send a report mail to specific addresses and if you need
to run custom commands during the standard procedure.
For all of the you have to specify a source from where to get the source files (http/ftp/rsync/file..) 
a directory where to place them and the user that has to own the deploy files.
Full documentation is available in the relevant .pp files

- puppi::project::tar - Use this to retrieve and deploy a simple tar file
- puppi::project::war - Use this to retrieve and deploy a war
- puppi::project::files - Use this to deploy one or more files based on a provided list
- puppi::project::dir - Use this to syncronize a remote directory to a local deploy one
- puppi::project::mysql - Use this to retrive and apply a .sql file for mysql schema updates 
- puppi::project::maven - Use this to deploy war and tar files generated via Maven released on Nexus or similar. A good source of Open Source Java artifacts is http://www.artifact-repository.org/
- puppi::project::builder - This is a general purpose define that incorporates most the of cases provided by the above procedures


## BUILTIN COMMANDS
The puppi/files/scripts directory in the module contains some general usage "native" bash scripts
that can be used in custom deployments. They are generally made to work together according to a
specific logic, which is at the base of the deploy procedures defines in puppi/manifests/project/
but you're free to write your own scripts, in whatever language, according to your needs.

The default scripts are engineered to follow these steps for a deployment:
- Remote files are downloaded in /tmp/puppi/$project/store or directly in the predeploy
  directory: /tmp/puppi/$project/deploy
- If  necessary the downloaded files are expanded in one or more predeploy directories
  (default:/tmp/puppi/$project/deploy).
- Runtime configuration entries might be saved in /tmp/puppi/$project/config
- Files are eventually backed from the deploy directory (Apache' docroot, Tomcat webapps or whatever)
  to the archive directory (/var/lib/puppi/archive/$project). Older backups are deleted (by default
  there's a retention of 5 backups).
- Files are copied from the predeploy directory to the deploy dir.
- Relevant services are eventually stopped and started

The most used common scripts are (they might have different arguments, some of them are quite simple):
- get_file.sh - Retrieves a file via ssh/http/rsync/svn and places it in a temp dir (store or predeploy)
- deploy.sh - Copies the files in the predeploy dir to deploy dir
- archive.sh - Backups and restores files in deploy dir
- service.sh - Stops or starts one or more services
- wait.sh - Waits for the presence or absence of a file, for the presence of a string in a file or a defined number or seconds.
- get_metadata.sh - Extracts metadata from various sources in order to provide info to other scripts. These info are save in the Runtime configuration file (/tmp/puppi/$project/config)
- report_mail.sh - Sends a mail with the report of the operations done

General functions, used by both the puppi command and these scripts, are in puppi/files/scripts/functions
