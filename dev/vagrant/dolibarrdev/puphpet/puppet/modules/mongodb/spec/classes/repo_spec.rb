require 'spec_helper'

describe 'mongodb::repo', :type => :class do

  context 'when deploying on Debian' do
    let :facts do
      {
        :osfamily        => 'Debian',
        :operatingsystem => 'Debian',
        :lsbdistid       => 'Debian',
      }
    end

    it {
      should contain_class('mongodb::repo::apt')
    }
  end

  context 'when deploying on CentOS' do
    let :facts do
      {
        :osfamily        => 'RedHat',
        :operatingsystem => 'CentOS',
      }
    end

    it {
      should contain_class('mongodb::repo::yum')
    }
  end

end
