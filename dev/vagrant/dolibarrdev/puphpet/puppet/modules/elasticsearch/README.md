# puppet-elasticsearch

A puppet module for managing elasticsearch nodes

http://www.elasticsearch.org/

[![Build Status](https://travis-ci.org/elasticsearch/puppet-elasticsearch.png?branch=master)](https://travis-ci.org/elasticsearch/puppet-elasticsearch)

## Usage

Installation, make sure service is running and will be started at boot time:

     class { 'elasticsearch': }

Install a certain version:

     class { 'elasticsearch':
       version => '0.90.3'
     }

This assumes an elasticsearch package is already available to your distribution's package manager. To install it in a different way:

To download from http/https/ftp source:

     class { 'elasticsearch':
       package_url => 'https://download.elasticsearch.org/elasticsearch/elasticsearch/elasticsearch-0.90.7.deb'
     }

To download from a puppet:// source:

     class { 'elasticsearch':
       package_url => 'puppet:///path/to/elasticsearch-0.90.7.deb'
     }

Or use a local file source:

     class { 'elasticsearch':
       package_url => 'file:/path/to/elasticsearch-0.90.7.deb'
     }

Automatic upgrade of the software ( default set to false ):

     class { 'elasticsearch':
       autoupgrade => true
     }

Removal/decommissioning:

     class { 'elasticsearch':
       ensure => 'absent'
     }

Install everything but disable service(s) afterwards:

     class { 'elasticsearch':
       status => 'disabled'
     }

Disable automated restart of Elasticsearch on config file change:

     class { 'elasticsearch':
       restart_on_change => false
     }

For the config variable a hash needs to be passed:

     class { 'elasticsearch':
       config                   => {
         'node'                 => {
           'name'               => 'elasticsearch001'
         },
         'index'                => {
           'number_of_replicas' => '0',
           'number_of_shards'   => '5'
         },
         'network'              => {
           'host'               => $::ipaddress
         }
       }
     }

Short write up of the config hash is also possible.

Instead of writing the full hash representation:

     class { 'elasticsearch':
       config                 => {
         'cluster'            => {
           'name'             => 'ClusterName',
           'routing'          => {
             'allocation'     => {
               'awareness'    => {
                 'attributes' => 'rack'
               }
             }
           }
         }
       }
     }

You can write the dotted key naming:

     class { 'elasticsearch':
       config => {
         'cluster' => {
           'name' => 'ClusterName',
           'routing.allocation.awareness.attributes' => 'rack'
         }
       }
     }


## Manage templates

### Add a new template

This will install and/or replace the template in Elasticearch

     elasticsearch::template { 'templatename':
       file => 'puppet:///path/to/template.json'
     }

### Delete a template

     elasticsearch::template { 'templatename':
       ensure => 'absent'
     }

### Host

  Default it uses localhost:9200 as host. you can change this with the 'host' and 'port' variables

     elasticsearch::template { 'templatename':
       host => $::ipaddress,
       port => 9200
     }

## Bindings / clients

Install a variety of [clients/bindings](http://www.elasticsearch.org/guide/clients/):

### Python

     elasticsearch::python { 'rawes': }

### Ruby

     elasticsearch::ruby { 'elasticsearch': }

## Plugins

Install [a variety of plugins](http://www.elasticsearch.org/guide/clients/):

### From official repository:

     elasticsearch::plugin{'mobz/elasticsearch-head':
       module_dir => 'head'
     }

### From custom url:

     elasticsearch::plugin{ 'elasticsearch-jetty':
       module_dir => 'jetty',
       url        => 'https://oss-es-plugins.s3.amazonaws.com/elasticsearch-jetty/elasticsearch-jetty-0.90.0.zip'
     }

## Java install

For those that have no separate module for installation of java:

     class { 'elasticsearch':
       java_install => true
     }

If you want a specific java package/version:

     class { 'elasticsearch':
       java_install => true,
       java_package => 'packagename'
     }

## Service providers

Currently only the 'init' service provider is supported but others can be implemented quite easy.

### init

#### Defaults file

You can populate the defaults file ( /etc/defaults/elasticsearch or /etc/sysconfig/elasticsearch )

##### hash representation

     class { 'elasticsearch':
       init_defaults => { 'ES_USER' => 'elasticsearch', 'ES_GROUP' => 'elasticsearch' }
     }

##### file source

     class { 'elasticsearch':
       init_defaults_file => 'puppet:///path/to/defaults'
     }

