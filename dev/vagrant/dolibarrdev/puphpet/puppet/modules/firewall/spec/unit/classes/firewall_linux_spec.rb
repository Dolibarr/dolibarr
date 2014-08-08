require 'spec_helper'

describe 'firewall::linux', :type => :class do
  let(:facts_default) {{ :kernel => 'Linux' }}
  it { should contain_package('iptables').with_ensure('present') }

  context 'RedHat like' do
    %w{RedHat CentOS Fedora}.each do |os|
      context "operatingsystem => #{os}" do
        releases = (os == 'Fedora' ? [14,15,'Rawhide'] : [6,7])
        releases.each do |osrel|
          context "operatingsystemrelease => #{osrel}" do
            let(:facts) { facts_default.merge({ :operatingsystem => os,
                                                :operatingsystemrelease => osrel}) }
            it { should contain_class('firewall::linux::redhat').with_require('Package[iptables]') }
          end
        end
      end
    end
  end

  context 'Debian like' do
    %w{Debian Ubuntu}.each do |os|
      context "operatingsystem => #{os}" do
        let(:facts) { facts_default.merge({ :operatingsystem => os }) }
        it { should contain_class('firewall::linux::debian').with_require('Package[iptables]') }
      end
    end
  end
end
