require 'spec_helper'

describe 'elasticsearch', :type => 'class' do

  context "install java" do

    let :params do {
      :java_install => true,
      :config => { 'node' => { 'name' => 'test' }  }
    } end

    context "On Debian OS" do

      let :facts do {
        :operatingsystem => 'Debian'
      } end

      it { should contain_package('openjdk-7-jre-headless') }

    end

    context "On Ubuntu OS" do

      let :facts do {
        :operatingsystem => 'Ubuntu'
      } end

      it { should contain_package('openjdk-7-jre-headless') }

    end

    context "On CentOS OS " do

      let :facts do {
        :operatingsystem => 'CentOS'
      } end

      it { should contain_package('java-1.7.0-openjdk') }

    end

    context "On RedHat OS " do

      let :facts do {
        :operatingsystem => 'Redhat'
      } end

      it { should contain_package('java-1.7.0-openjdk') }

    end

    context "On Fedora OS " do

      let :facts do {
        :operatingsystem => 'Fedora'
      } end

      it { should contain_package('java-1.7.0-openjdk') }

    end

    context "On Scientific OS " do

      let :facts do {
        :operatingsystem => 'Scientific'
      } end

      it { should contain_package('java-1.7.0-openjdk') }

    end

    context "On Amazon OS " do

      let :facts do {
        :operatingsystem => 'Amazon'
      } end

      it { should contain_package('java-1.7.0-openjdk') }

    end

    context "On OracleLinux OS " do

      let :facts do {
        :operatingsystem => 'OracleLinux'
      } end

      it { should contain_package('java-1.7.0-openjdk') }

    end

    context "On an unknown OS" do

      let :facts do {
        :operatingsystem => 'Windows'
      } end

      it { expect { should raise_error(Puppet::Error) } }

    end

    context "Custom java package" do

      let :facts do {
        :operatingsystem => 'CentOS'
      } end

      let :params do {
        :java_install => true,
        :java_package => 'java-1.6.0-openjdk',
        :config => { 'node' => { 'name' => 'test' }  }
      } end

      it { should contain_package('java-1.6.0-openjdk') }

    end

  end

end
