require 'spec_helper'

describe 'elasticsearch::template', :type => 'define' do

  let(:title) { 'foo' }
  let(:facts) { {:operatingsystem => 'CentOS' }}
  let(:pre_condition) { 'class {"elasticsearch": config => { "node" => {"name" => "test" }}}'}

  context "Add a template" do

    let :params do {
      :ensure => 'present',
      :file   => 'puppet:///path/to/foo.json',
    } end

    it { should contain_file('/etc/elasticsearch/templates_import/elasticsearch-template-foo.json').with(:source => 'puppet:///path/to/foo.json', :notify => "Exec[delete_template_foo]", :require => "Exec[mkdir_templates]") }
    it { should contain_exec('insert_template_foo').with(:command => 'curl -s -XPUT http://localhost:9200/_template/foo -d @/etc/elasticsearch/templates_import/elasticsearch-template-foo.json', :unless => 'test $(curl -s \'http://localhost:9200/_template/foo?pretty=true\' | wc -l) -gt 1') }
  end

  context "Delete a template" do

    let :params do {
      :ensure => 'absent'
    } end

    it { should_not contain_file('/etc/elasticsearch/templates_import/elasticsearch-template-foo.json').with(:source => 'puppet:///path/to/foo.json') }
    it { should_not contain_exec('insert_template_foo') }
    it { should contain_exec('delete_template_foo').with(:command => 'curl -s -XDELETE http://localhost:9200/_template/foo', :notify => nil, :onlyif => 'test $(curl -s \'http://localhost:9200/_template/foo?pretty=true\' | wc -l) -gt 1' ) }
  end

  context "Add template with alternative host and port" do

    let :params do {
      :file => 'puppet:///path/to/foo.json',
      :host => 'otherhost',
      :port => '9201'
    } end

    it { should contain_file('/etc/elasticsearch/templates_import/elasticsearch-template-foo.json').with(:source => 'puppet:///path/to/foo.json') }
    it { should contain_exec('insert_template_foo').with(:command => 'curl -s -XPUT http://otherhost:9201/_template/foo -d @/etc/elasticsearch/templates_import/elasticsearch-template-foo.json', :unless => 'test $(curl -s \'http://otherhost:9201/_template/foo?pretty=true\' | wc -l) -gt 1') }
  end

end
