require 'spec_helper'


describe 'beanstalkd::config' do
  let (:title) {'a title is required'}

  #basic OS support testing
  context "on Debian" do
    let (:facts) { { :operatingsystem => 'debian' } }
    it { should contain_package('beanstalkd').with_ensure('latest') }
    it { should contain_service('beanstalkd').with_ensure('running') }
  end  
  context "on redhat" do
    let (:facts) { { :operatingsystem => 'debian' } }
    it { should contain_package('beanstalkd').with_ensure('latest') }
    it { should contain_service('beanstalkd').with_ensure('running') }
  end  
  context "on ubuntu" do
    let (:facts) { { :operatingsystem => 'ubuntu' } }
    it { should contain_package('beanstalkd').with_ensure('latest') }
    it { should contain_service('beanstalkd').with_ensure('running') }
  end  
  context "on centos" do
    let (:facts) { { :operatingsystem => 'centos' } }
    it { should contain_package('beanstalkd').with_ensure('latest') }
    it { should contain_service('beanstalkd').with_ensure('running') }
  end  
  context "on unsupported OS" do
    let (:facts) { { :operatingsystem => 'unsupported' } }
    it { expect { raise_error(Puppet::Error) } }
  end  

  #now lets test our various parameters - for the most part this shouldn't care what OS it is
  #if your OS support needs more specific testing, do it!

  #ensure testing - remember this does both service and packages, so test both
  context "on redhat, ensure absent" do
    let (:facts) { { :operatingsystem => 'redhat' } }
    let(:params) { { :ensure => 'absent' } }
    it { should contain_package('beanstalkd').with_ensure('absent') }
    it { should contain_service('beanstalkd').with_ensure('stopped') }
  end  
  context "on redhat, ensure running" do
    let (:facts) { { :operatingsystem => 'redhat' } }
    let(:params) { { :ensure => 'running' } }
    it { should contain_package('beanstalkd').with_ensure('latest') }
    it { should contain_service('beanstalkd').with_ensure('running') }
  end  
  context "on redhat, ensure stopped" do
    let (:facts) { { :operatingsystem => 'redhat' } }
    let(:params) { { :ensure => 'stopped' } }
    it { should contain_package('beanstalkd').with_ensure('latest') }
    it { should contain_service('beanstalkd').with_ensure('stopped') }
  end  
  context "on redhat, ensure broken" do
    let (:facts) { { :operatingsystem => 'redhat' } }
    let(:params) { { :ensure => 'broken' } }
    it { expect { raise_error(Puppet::Error) } }
  end  

  #custom package/service names
  context "on redhat, servicename testbeans" do
    let (:facts) { { :operatingsystem => 'redhat' } }
    let(:params) { { :servicename => 'testbeans' } }
    it { should contain_service('testbeans').with_ensure('running') }
  end  
  context "on redhat, packagename testbeans" do
    let (:facts) { { :operatingsystem => 'redhat' } }
    let(:params) { { :packagename => 'testbeans' } }
    it { should contain_package('testbeans').with_ensure('latest') }
  end  
  #and custom version
  context "on redhat, package version" do
    let (:facts) { { :operatingsystem => 'redhat' } }
    let(:params) { { :packageversion => 'testversion' } }
    it { should contain_package('beanstalkd').with_ensure('testversion') }
  end  

   
end
