# This directory contains script example to use ansible to deploy or maitains dolibarr instances

This is a quick tutorial:

* Install ansible:
> apt-get install 

* Add ip of server to manage into
/etc/ansible/hosts

* Deploy public key to managed servers
- authorized_key: user=charlie key="{{ lookup('file', '/home/charlie/.ssh/id_rsa.pub') }}"


