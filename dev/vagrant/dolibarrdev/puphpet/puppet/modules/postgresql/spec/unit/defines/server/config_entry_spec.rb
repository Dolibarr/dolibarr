require 'spec_helper'

describe 'postgresql::server::config_entry', :type => :define do
  let :facts do
    {
      :osfamily => 'RedHat',
      :operatingsystem => 'RedHat',
      :operatingsystemrelease => '6.4',
    }
  end

  let(:title) { 'config_entry'}

  let :target do
    tmpfilename('postgresql_conf')
  end

  context "syntax check" do
    let(:params) { { :ensure => 'present'} }
    it { should contain_postgresql__server__config_entry('config_entry') }
  end
end

