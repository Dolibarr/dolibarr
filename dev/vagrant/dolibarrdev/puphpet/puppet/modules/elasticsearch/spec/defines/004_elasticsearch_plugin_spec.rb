require 'spec_helper'

describe 'elasticsearch::plugin', :type => 'define' do

  let(:title) { 'mobz/elasticsearch-head' }
  let(:facts) { {:operatingsystem => 'CentOS' }}
  let(:pre_condition) { 'class {"elasticsearch": config => { "node" => {"name" => "test" }}}'}

  context "Add a plugin" do

    let :params do {
      :ensure     => 'present',
      :module_dir => 'head',
    } end

    it { should contain_exec('install_plugin_mobz/elasticsearch-head').with(:command => '/usr/share/elasticsearch/bin/plugin -install mobz/elasticsearch-head', :creates => '/usr/share/elasticsearch/plugins/head') }
  end

  context "Remove a plugin" do

    let :params do {
      :ensure     => 'absent',
      :module_dir => 'head'
    } end

    it { should contain_exec('remove_plugin_mobz/elasticsearch-head').with(:command => '/usr/share/elasticsearch/bin/plugin --remove head', :onlyif => 'test -d /usr/share/elasticsearch/plugins/head') }
  end

end
