require 'spec_helper'

describe 'nginx::params' do
  context "On a Debian OS" do
    let :facts do {
      :osfamily        => 'debian',
      :operatingsystem => 'debian',
    } end

    it { should contain_nginx__params }
    it { should have_class_count(1) }    #only nginx::params itself
    it { should have_resource_count(0) } #params class should never declare resources

  end
end
