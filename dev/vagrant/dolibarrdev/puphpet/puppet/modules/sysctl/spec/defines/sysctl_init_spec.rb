require 'spec_helper'

describe 'sysctl', :type => :define do
  let(:title) { 'net.ipv4.ip_forward'}

  context 'present' do
    let(:params) { { :value => '1' } }

    it { should contain_file('/etc/sysctl.d/net.ipv4.ip_forward.conf').with(
      :content  => "net.ipv4.ip_forward = 1\n",
      :ensure   => nil
    ) }

    it { should contain_exec('sysctl-net.ipv4.ip_forward') }
    it { should contain_exec('update-sysctl.conf-net.ipv4.ip_forward')}
  end

  context 'absent' do
    let(:params) { { :ensure  => 'absent' } }

    it { should contain_file('/etc/sysctl.d/net.ipv4.ip_forward.conf').with_ensure('absent') }
  end

end

