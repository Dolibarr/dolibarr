require 'spec_helper'

describe 'mongodb::client::install', :type => :class do
  describe 'it should create package' do
    let(:pre_condition) { ["class mongodb::client { $ensure = true $package_name = 'mongodb' }", "include mongodb::client"]}
    it {
      should contain_package('mongodb_client').with({
        :ensure => 'present',
        :name   => 'mongodb',
      })
    }
  end
end
