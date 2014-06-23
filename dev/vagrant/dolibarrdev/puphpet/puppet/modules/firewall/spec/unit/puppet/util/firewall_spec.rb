require 'spec_helper'

describe 'Puppet::Util::Firewall' do
  let(:resource) {
    type = Puppet::Type.type(:firewall)
    provider = double 'provider'
    allow(provider).to receive(:name).and_return(:iptables)
    allow(Puppet::Type::Firewall).to receive(:defaultprovider).and_return(provider)
    type.new({:name => '000 test foo'})
  }

  before(:each) { resource }

  describe '#host_to_ip' do
    subject { resource }
    specify {
      expect(Resolv).to receive(:getaddress).with('puppetlabs.com').and_return('96.126.112.51')
      subject.host_to_ip('puppetlabs.com').should == '96.126.112.51/32'
    }
    specify { subject.host_to_ip('96.126.112.51').should == '96.126.112.51/32' }
    specify { subject.host_to_ip('96.126.112.51/32').should == '96.126.112.51/32' }
    specify { subject.host_to_ip('2001:db8:85a3:0:0:8a2e:370:7334').should == '2001:db8:85a3::8a2e:370:7334/128' }
    specify { subject.host_to_ip('2001:db8:1234::/48').should == '2001:db8:1234::/48' }
    specify { subject.host_to_ip('0.0.0.0/0').should == nil }
    specify { subject.host_to_ip('::/0').should == nil }
  end

  describe '#host_to_mask' do
    subject { resource }
    specify {
      expect(Resolv).to receive(:getaddress).at_least(:once).with('puppetlabs.com').and_return('96.126.112.51')
      subject.host_to_mask('puppetlabs.com').should == '96.126.112.51/32'
      subject.host_to_mask('!puppetlabs.com').should == '! 96.126.112.51/32'
    }
    specify { subject.host_to_mask('96.126.112.51').should == '96.126.112.51/32' }
    specify { subject.host_to_mask('!96.126.112.51').should == '! 96.126.112.51/32' }
    specify { subject.host_to_mask('96.126.112.51/32').should == '96.126.112.51/32' }
    specify { subject.host_to_mask('! 96.126.112.51/32').should == '! 96.126.112.51/32' }
    specify { subject.host_to_mask('2001:db8:85a3:0:0:8a2e:370:7334').should == '2001:db8:85a3::8a2e:370:7334/128' }
    specify { subject.host_to_mask('!2001:db8:85a3:0:0:8a2e:370:7334').should == '! 2001:db8:85a3::8a2e:370:7334/128' }
    specify { subject.host_to_mask('2001:db8:1234::/48').should == '2001:db8:1234::/48' }
    specify { subject.host_to_mask('! 2001:db8:1234::/48').should == '! 2001:db8:1234::/48' }
    specify { subject.host_to_mask('0.0.0.0/0').should == nil }
    specify { subject.host_to_mask('!0.0.0.0/0').should == nil }
    specify { subject.host_to_mask('::/0').should == nil }
    specify { subject.host_to_mask('! ::/0').should == nil }
  end

  describe '#icmp_name_to_number' do
    describe 'proto unsupported' do
      subject { resource }

      %w{inet5 inet8 foo}.each do |proto|
        it "should reject invalid proto #{proto}" do
          expect { subject.icmp_name_to_number('echo-reply', proto) }.
            to raise_error(ArgumentError, "unsupported protocol family '#{proto}'")
        end
      end
    end

    describe 'proto IPv4' do
      proto = 'inet'
      subject { resource }
      specify { subject.icmp_name_to_number('echo-reply', proto).should == '0' }
      specify { subject.icmp_name_to_number('destination-unreachable', proto).should == '3' }
      specify { subject.icmp_name_to_number('source-quench', proto).should == '4' }
      specify { subject.icmp_name_to_number('redirect', proto).should == '6' }
      specify { subject.icmp_name_to_number('echo-request', proto).should == '8' }
      specify { subject.icmp_name_to_number('router-advertisement', proto).should == '9' }
      specify { subject.icmp_name_to_number('router-solicitation', proto).should == '10' }
      specify { subject.icmp_name_to_number('time-exceeded', proto).should == '11' }
      specify { subject.icmp_name_to_number('parameter-problem', proto).should == '12' }
      specify { subject.icmp_name_to_number('timestamp-request', proto).should == '13' }
      specify { subject.icmp_name_to_number('timestamp-reply', proto).should == '14' }
      specify { subject.icmp_name_to_number('address-mask-request', proto).should == '17' }
      specify { subject.icmp_name_to_number('address-mask-reply', proto).should == '18' }
    end

    describe 'proto IPv6' do
      proto = 'inet6'
      subject { resource }
      specify { subject.icmp_name_to_number('destination-unreachable', proto).should == '1' }
      specify { subject.icmp_name_to_number('time-exceeded', proto).should == '3' }
      specify { subject.icmp_name_to_number('parameter-problem', proto).should == '4' }
      specify { subject.icmp_name_to_number('echo-request', proto).should == '128' }
      specify { subject.icmp_name_to_number('echo-reply', proto).should == '129' }
      specify { subject.icmp_name_to_number('router-solicitation', proto).should == '133' }
      specify { subject.icmp_name_to_number('router-advertisement', proto).should == '134' }
      specify { subject.icmp_name_to_number('redirect', proto).should == '137' }
    end
  end

  describe '#string_to_port' do
    subject { resource }
    specify { subject.string_to_port('80','tcp').should == '80' }
    specify { subject.string_to_port(80,'tcp').should == '80' }
    specify { subject.string_to_port('http','tcp').should == '80' }
    specify { subject.string_to_port('domain','udp').should == '53' }
  end

  describe '#to_hex32' do
    subject { resource }
    specify { subject.to_hex32('0').should == '0x0' }
    specify { subject.to_hex32('0x32').should == '0x32' }
    specify { subject.to_hex32('42').should == '0x2a' }
    specify { subject.to_hex32('4294967295').should == '0xffffffff' }
    specify { subject.to_hex32('4294967296').should == nil }
    specify { subject.to_hex32('-1').should == nil }
    specify { subject.to_hex32('bananas').should == nil }
  end

  describe '#persist_iptables' do
    before { Facter.clear }
    subject { resource }

    describe 'when proto is IPv4' do
      let(:proto) { 'IPv4' }

      it 'should exec /sbin/service if running RHEL 6 or earlier' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return('RedHat')
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('RedHat')
        allow(Facter.fact(:operatingsystemrelease)).to receive(:value).and_return('6')

        expect(subject).to receive(:execute).with(%w{/sbin/service iptables save})
        subject.persist_iptables(proto)
      end

      it 'should exec for systemd if running RHEL 7 or greater' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return('RedHat')
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('RedHat')
        allow(Facter.fact(:operatingsystemrelease)).to receive(:value).and_return('7')

        expect(subject).to receive(:execute).with(%w{/usr/libexec/iptables/iptables.init save})
        subject.persist_iptables(proto)
      end

      it 'should exec for systemd if running Fedora 15 or greater' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return('RedHat')
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('Fedora')
        allow(Facter.fact(:operatingsystemrelease)).to receive(:value).and_return('15')

        expect(subject).to receive(:execute).with(%w{/usr/libexec/iptables/iptables.init save})
        subject.persist_iptables(proto)
      end

      it 'should exec for CentOS identified from operatingsystem' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return(nil)
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('CentOS')
        expect(subject).to receive(:execute).with(%w{/sbin/service iptables save})
        subject.persist_iptables(proto)
      end

      it 'should exec for Archlinux identified from osfamily' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return('Archlinux')
        expect(subject).to receive(:execute).with(['/bin/sh', '-c', '/usr/sbin/iptables-save > /etc/iptables/iptables.rules'])
        subject.persist_iptables(proto)
      end

      it 'should raise a warning when exec fails' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return('RedHat')
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('RedHat')
        allow(Facter.fact(:operatingsystemrelease)).to receive(:value).and_return('6')

        expect(subject).to receive(:execute).with(%w{/sbin/service iptables save}).
          and_raise(Puppet::ExecutionFailure, 'some error')
        expect(subject).to receive(:warning).with('Unable to persist firewall rules: some error')
        subject.persist_iptables(proto)
      end
    end

    describe 'when proto is IPv6' do
      let(:proto) { 'IPv6' }

      it 'should exec for newer Ubuntu' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return(nil)
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('Ubuntu')
        allow(Facter.fact(:iptables_persistent_version)).to receive(:value).and_return('0.5.3ubuntu2')
        expect(subject).to receive(:execute).with(%w{/usr/sbin/service iptables-persistent save})
        subject.persist_iptables(proto)
      end

      it 'should not exec for older Ubuntu which does not support IPv6' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return(nil)
        allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('Ubuntu')
        allow(Facter.fact(:iptables_persistent_version)).to receive(:value).and_return('0.0.20090701')
        expect(subject).to receive(:execute).never
        subject.persist_iptables(proto)
      end

      it 'should not exec for Suse which is not supported' do
        allow(Facter.fact(:osfamily)).to receive(:value).and_return('Suse')
        expect(subject).to receive(:execute).never
        subject.persist_iptables(proto)
      end
    end
  end
end
