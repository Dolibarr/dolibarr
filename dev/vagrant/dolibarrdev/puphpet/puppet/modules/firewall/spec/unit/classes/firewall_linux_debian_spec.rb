require 'spec_helper'

describe 'firewall::linux::debian', :type => :class do
  it { should contain_package('iptables-persistent').with(
    :ensure => 'present'
  )}
  it { should contain_service('iptables-persistent').with(
    :ensure   => nil,
    :enable   => 'true',
    :require  => 'Package[iptables-persistent]'
  )}

  context 'enable => false' do
    let(:params) {{ :enable => 'false' }}
    it { should contain_service('iptables-persistent').with(
      :enable   => 'false'
    )}
  end
end
