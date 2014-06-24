require 'spec_helper'

describe 'firewall::linux::redhat', :type => :class do
  it { should contain_service('iptables').with(
    :ensure => 'running',
    :enable => 'true'
  )}

  context 'ensure => stopped' do
    let(:params) {{ :ensure => 'stopped' }}
    it { should contain_service('iptables').with(
      :ensure => 'stopped'
    )}
  end

  context 'enable => false' do
    let(:params) {{ :enable => 'false' }}
    it { should contain_service('iptables').with(
      :enable => 'false'
    )}
  end
end
