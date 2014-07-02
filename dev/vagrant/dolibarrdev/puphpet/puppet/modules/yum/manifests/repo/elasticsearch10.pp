# = Class: yum::repo::elasticsearch10
#
# This class installs the elasticsearch10 repo
#
class yum::repo::elasticsearch10 {

  yum::managed_yumrepo { 'elasticsearch-1.0':
    descr          => 'Elasticsearch repository for 1.0.x packages',
    baseurl        => 'http://packages.elasticsearch.org/elasticsearch/1.0/centos',
    enabled        => 1,
    gpgcheck       => 1,
    gpgkey         => 'http://packages.elasticsearch.org/GPG-KEY-elasticsearch',
  }

}
