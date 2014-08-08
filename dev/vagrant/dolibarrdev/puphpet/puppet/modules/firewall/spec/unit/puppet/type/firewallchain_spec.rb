#!/usr/bin/env rspec

require 'spec_helper'

firewallchain = Puppet::Type.type(:firewallchain)

describe firewallchain do
  before(:each) do
    # Stub confine facts
    allow(Facter.fact(:kernel)).to receive(:value).and_return('Linux')
    allow(Facter.fact(:operatingsystem)).to receive(:value).and_return('Debian')
  end
  let(:klass) { firewallchain }
  let(:provider) {
    prov = double 'provider'
    allow(prov).to receive(:name).and_return(:iptables_chain)
    prov
  }
  let(:resource) {
    allow(Puppet::Type::Firewallchain).to receive(:defaultprovider).and_return provider
    klass.new({:name => 'INPUT:filter:IPv4', :policy => :accept })
  }

  it 'should have :name be its namevar' do
    klass.key_attributes.should == [:name]
  end

  describe ':name' do
    {'nat' => ['PREROUTING', 'POSTROUTING', 'INPUT', 'OUTPUT'],
     'mangle' => [ 'PREROUTING', 'POSTROUTING', 'INPUT', 'FORWARD', 'OUTPUT' ],
     'filter' => ['INPUT','OUTPUT','FORWARD'],
     'raw' => [ 'PREROUTING', 'OUTPUT'],
     'broute' => ['BROUTING']
    }.each_pair do |table, allowedinternalchains|
      ['IPv4', 'IPv6', 'ethernet'].each do |protocol|
        [ 'test', '$5()*&%\'"^$09):' ].each do |chainname|
          name = "#{chainname}:#{table}:#{protocol}"
          if table == 'nat' && protocol == 'IPv6'
            it "should fail #{name}" do
              expect { resource[:name] = name }.to raise_error(Puppet::Error)
            end
          elsif protocol != 'ethernet' && table == 'broute'
            it "should fail #{name}" do
              expect { resource[:name] = name }.to raise_error(Puppet::Error)
            end
          else
            it "should accept name #{name}" do
              resource[:name] = name
              resource[:name].should == name
            end
          end
        end # chainname
      end # protocol

      [ 'PREROUTING', 'POSTROUTING', 'BROUTING', 'INPUT', 'FORWARD', 'OUTPUT' ].each do |internalchain|
        name = internalchain + ':' + table + ':'
        if internalchain == 'BROUTING'
          name += 'ethernet'
        elsif table == 'nat'
          name += 'IPv4'
        else
          name += 'IPv4'
        end
        if allowedinternalchains.include? internalchain
          it "should allow #{name}" do
            resource[:name] = name
            resource[:name].should == name
          end
        else
          it "should fail #{name}" do
            expect { resource[:name] = name }.to raise_error(Puppet::Error)
          end
        end
      end # internalchain

    end # table, allowedinternalchainnames

    it 'should fail with invalid table names' do
      expect { resource[:name] = 'wrongtablename:test:IPv4' }.to raise_error(Puppet::Error)
    end

    it 'should fail with invalid protocols names' do
      expect { resource[:name] = 'test:filter:IPv5' }.to raise_error(Puppet::Error)
    end

  end

  describe ':policy' do

    [:accept, :drop, :queue, :return].each do |policy|
      it "should accept policy #{policy}" do
        resource[:policy] = policy
        resource[:policy].should == policy
      end
    end

    it 'should fail when value is not recognized' do
      expect { resource[:policy] = 'not valid' }.to raise_error(Puppet::Error)
    end

    [:accept, :drop, :queue, :return].each do |policy|
      it "non-inbuilt chains should not accept policy #{policy}" do
        expect { klass.new({:name => 'testchain:filter:IPv4', :policy => policy }) }.to raise_error(Puppet::Error)
      end
      it "non-inbuilt chains can accept policies on protocol = ethernet (policy #{policy})" do
        klass.new({:name => 'testchain:filter:ethernet', :policy => policy })
      end
    end

  end

  describe 'autorequire packages' do
    it "provider iptables_chain should autorequire package iptables" do
      resource[:provider].should == :iptables_chain
      package = Puppet::Type.type(:package).new(:name => 'iptables')
      catalog = Puppet::Resource::Catalog.new
      catalog.add_resource resource
      catalog.add_resource package
      rel = resource.autorequire[0]
      rel.source.ref.should == package.ref
      rel.target.ref.should == resource.ref
    end

    it "provider iptables_chain should autorequire packages iptables and iptables-persistent" do
      resource[:provider].should == :iptables_chain
      packages = [
        Puppet::Type.type(:package).new(:name => 'iptables'),
        Puppet::Type.type(:package).new(:name => 'iptables-persistent')
      ]
      catalog = Puppet::Resource::Catalog.new
      catalog.add_resource resource
      packages.each do |package|
        catalog.add_resource package
      end
      packages.zip(resource.autorequire) do |package, rel|
        rel.source.ref.should == package.ref
        rel.target.ref.should == resource.ref
      end
    end
  end

  describe 'purge iptables rules' do
    before(:each) do
      allow(Puppet::Type.type(:firewall).provider(:iptables)).to receive(:iptables_save).and_return(<<EOS
# Completed on Sun Jan  5 19:30:21 2014
# Generated by iptables-save v1.4.12 on Sun Jan  5 19:30:21 2014
*filter
:INPUT DROP [0:0]
:FORWARD DROP [0:0]
:OUTPUT ACCEPT [0:0]
:LOCAL_FORWARD - [0:0]
:LOCAL_FORWARD_PRE - [0:0]
:LOCAL_INPUT - [0:0]
:LOCAL_INPUT_PRE - [0:0]
:fail2ban-ssh - [0:0]
-A INPUT -p tcp -m multiport --dports 22 -j fail2ban-ssh
-A INPUT -i lo -m comment --comment "012 accept loopback" -j ACCEPT
-A INPUT -p tcp -m multiport --dports 22 -m comment --comment "020 ssh" -j ACCEPT
-A OUTPUT -d 1.2.1.2 -j DROP
-A fail2ban-ssh -j RETURN
COMMIT
# Completed on Sun Jan  5 19:30:21 2014
EOS
)
    end

    it 'should generate iptables resources' do
      resource = Puppet::Type::Firewallchain.new(:name => 'INPUT:filter:IPv4', :purge => true)

      expect(resource.generate.size).to eq(3)
    end

    it 'should not generate ignored iptables rules' do
      resource = Puppet::Type::Firewallchain.new(:name => 'INPUT:filter:IPv4', :purge => true, :ignore => ['-j fail2ban-ssh'])

      expect(resource.generate.size).to eq(2)
    end

    it 'should not generate iptables resources when not enabled' do
      resource = Puppet::Type::Firewallchain.new(:name => 'INPUT:filter:IPv4')

      expect(resource.generate.size).to eq(0)
    end
  end
end
