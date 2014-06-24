require 'spec_helper'

describe 'Puppet::Util::IPCidr' do
  describe 'ipv4 address' do
    before { @ipaddr = Puppet::Util::IPCidr.new('96.126.112.51') }
    subject { @ipaddr }
    specify { subject.cidr.should == '96.126.112.51/32' }
    specify { subject.prefixlen.should == 32 }
    specify { subject.netmask.should == '255.255.255.255' }
  end

  describe 'single ipv4 address with cidr' do
    before { @ipcidr = Puppet::Util::IPCidr.new('96.126.112.51/32') }
    subject { @ipcidr }
    specify { subject.cidr.should == '96.126.112.51/32' }
    specify { subject.prefixlen.should == 32 }
    specify { subject.netmask.should == '255.255.255.255' }
  end

  describe 'ipv4 address range with cidr' do
    before { @ipcidr = Puppet::Util::IPCidr.new('96.126.112.0/24') }
    subject { @ipcidr }
    specify { subject.cidr.should == '96.126.112.0/24' }
    specify { subject.prefixlen.should == 24 }
    specify { subject.netmask.should == '255.255.255.0' }
  end

  describe 'ipv4 open range with cidr' do
    before { @ipcidr = Puppet::Util::IPCidr.new('0.0.0.0/0') }
    subject { @ipcidr }
    specify { subject.cidr.should == '0.0.0.0/0' }
    specify { subject.prefixlen.should == 0 }
    specify { subject.netmask.should == '0.0.0.0' }
  end

  describe 'ipv6 address' do
    before { @ipaddr = Puppet::Util::IPCidr.new('2001:db8:85a3:0:0:8a2e:370:7334') }
    subject { @ipaddr }
    specify { subject.cidr.should == '2001:db8:85a3::8a2e:370:7334/128' }
    specify { subject.prefixlen.should == 128 }
    specify { subject.netmask.should == 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff' }
  end

  describe 'single ipv6 addr with cidr' do
    before { @ipaddr = Puppet::Util::IPCidr.new('2001:db8:85a3:0:0:8a2e:370:7334/128') }
    subject { @ipaddr }
    specify { subject.cidr.should == '2001:db8:85a3::8a2e:370:7334/128' }
    specify { subject.prefixlen.should == 128 }
    specify { subject.netmask.should == 'ffff:ffff:ffff:ffff:ffff:ffff:ffff:ffff' }
  end

  describe 'ipv6 addr range with cidr' do
    before { @ipaddr = Puppet::Util::IPCidr.new('2001:db8:1234::/48') }
    subject { @ipaddr }
    specify { subject.cidr.should == '2001:db8:1234::/48' }
    specify { subject.prefixlen.should == 48 }
    specify { subject.netmask.should == 'ffff:ffff:ffff:0000:0000:0000:0000:0000' }
  end

  describe 'ipv6 open range with cidr' do
    before { @ipaddr = Puppet::Util::IPCidr.new('::/0') }
    subject { @ipaddr }
    specify { subject.cidr.should == '::/0' }
    specify { subject.prefixlen.should == 0 }
    specify { subject.netmask.should == '0000:0000:0000:0000:0000:0000:0000:0000' }
  end
end
