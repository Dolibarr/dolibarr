require 'spec_helper'

describe 'postgresql::globals', :type => :class do
  let :facts do
    {
      :osfamily               => 'Debian',
      :operatingsystem        => 'Debian',
      :operatingsystemrelease => '6.0',
      :lsbdistid              => 'Debian',
    }
  end

  describe 'with no parameters' do
    it 'should work' do
      should contain_class("postgresql::globals")
    end
  end

  describe 'manage_package_repo => true' do
    let(:params) do
      {
        :manage_package_repo => true,
      }
    end
    it 'should pull in class postgresql::repo' do
      should contain_class("postgresql::repo")
    end
  end
end
