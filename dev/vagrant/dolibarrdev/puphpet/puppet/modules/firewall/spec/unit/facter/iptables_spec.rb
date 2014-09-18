require 'spec_helper'

describe "Facter::Util::Fact" do
  before {
    Facter.clear
    allow(Facter.fact(:kernel)).to receive(:value).and_return('Linux')
    allow(Facter.fact(:kernelrelease)).to receive(:value).and_return('2.6')
  }

  describe 'iptables_version' do
    it {
      allow(Facter::Util::Resolution).to receive(:exec).with('iptables --version').
      and_return('iptables v1.4.7')
      Facter.fact(:iptables_version).value.should == '1.4.7'
    }
  end

  describe 'ip6tables_version' do
    before { allow(Facter::Util::Resolution).to receive(:exec).
             with('ip6tables --version').and_return('ip6tables v1.4.7') }
    it { Facter.fact(:ip6tables_version).value.should == '1.4.7' }
  end
end
