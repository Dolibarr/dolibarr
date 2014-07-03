require 'spec_helper'

describe 'elasticsearch', :type => 'class' do

  let :facts do {
    :operatingsystem => 'CentOS'
  } end

  context "config file content" do

    context "with nothing set" do

      let :params do {
      } end

      it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:content => "### MANAGED BY PUPPET ###\n") }

    end

    context "set a value" do

      let :params do {
        :config => { 'node' => { 'name' => 'test' }  }
      } end

      it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:content => "### MANAGED BY PUPPET ###\n---\nnode: \n  name: test\n") }

    end

    context "set a value to true" do

      let :params do {
        :config => { 'node' => { 'master' => true }  }
      } end

      it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:content => "### MANAGED BY PUPPET ###\n---\nnode: \n  master: true\n") }

    end

    context "set a value to false" do

      let :params do {
        :config => { 'node' => { 'data' => false }  }
      } end

      it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:content => "### MANAGED BY PUPPET ###\n---\nnode: \n  data: false\n") }

    end

    context "deeper hash and multiple keys" do

      let :params do {
        :config => { 'index' => { 'routing' => { 'allocation' => { 'include' => 'tag1', 'exclude' => [ 'tag2', 'tag3' ] } } }, 'node' => { 'name' => 'somename' } }
      } end

      it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:content => "### MANAGED BY PUPPET ###\n---\nindex: \n  routing: \n    allocation: \n      exclude: \n             - tag2\n             - tag3\n      include: tag1\nnode: \n  name: somename\n") }

    end

    context "Combination of full hash and shorted write up keys" do

      let :params do {
        :config => { 'node' => { 'name' => 'NodeName', 'rack' => 46 }, 'boostrap.mlockall' => true, 'cluster' => { 'name' => 'ClusterName', 'routing.allocation.awareness.attributes' => 'rack' }, 'discovery.zen' => { 'ping.unicast.hosts'=> [ "host1", "host2" ], 'minimum_master_nodes' => 3, 'ping.multicast.enabled' => false }, 'gateway' => { 'expected_nodes' => 4, 'recover_after_nodes' => 3 }, 'network.host' => '123.123.123.123' }
       } end

       it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:content => "### MANAGED BY PUPPET ###\n---\nboostrap: \n  mlockall: true\ncluster: \n  name: ClusterName\n  routing: \n    allocation: \n      awareness: \n        attributes: rack\ndiscovery: \n  zen: \n    minimum_master_nodes: 3\n    ping: \n      multicast: \n        enabled: false\n      unicast: \n        hosts: \n             - host1\n             - host2\ngateway: \n  expected_nodes: 4\n  recover_after_nodes: 3\nnetwork: \n  host: 123.123.123.123\nnode: \n  name: NodeName\n  rack: 46\n") }

     end

  end

  context "service restarts" do

   let :facts do {
     :operatingsystem => 'CentOS'
    } end

    context "does not restart when restart_on_change is false" do
      let :params do {
        :config            => { 'node' => { 'name' => 'test' }  },
        :restart_on_change => false,
      } end

      it { should contain_file('/etc/elasticsearch/elasticsearch.yml').without_notify }
    end

    context "should happen restart_on_change is true (default)" do
      let :params do {
        :config            => { 'node' => { 'name' => 'test' }  },
        :restart_on_change => true,
      } end

      it { should contain_file('/etc/elasticsearch/elasticsearch.yml').with(:notify => "Class[Elasticsearch::Service]") }
    end

  end

  context 'data directory' do
    let(:facts) do {
      :operatingsystem => 'CentOS'
    } end

    context 'should allow creating datadir' do
      let(:params) do {
        :datadir => '/foo'
      } end

      it { should contain_file('/foo').with(:ensure => 'directory') }
    end

  end
end
