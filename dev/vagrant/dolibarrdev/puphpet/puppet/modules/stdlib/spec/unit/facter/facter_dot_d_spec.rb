#! /usr/bin/env ruby -S rspec
require 'spec_helper'
require 'facter/facter_dot_d'

describe Facter::Util::DotD do

  context 'returns a simple fact' do
    before :each do
      Facter.stubs(:version).returns('1.6.1')
      subject.stubs(:entries).returns(['/etc/facter/facts.d/fake_fact.txt'])
      File.stubs(:readlines).with('/etc/facter/facts.d/fake_fact.txt').returns(['fake_fact=fake fact'])
      subject.create
    end

    it 'should return successfully' do
      Facter.fact(:fake_fact).value.should == 'fake fact'
    end
  end

  context 'returns a fact with equals signs' do
    before :each do
      Facter.stubs(:version).returns('1.6.1')
      subject.stubs(:entries).returns(['/etc/facter/facts.d/foo.txt'])
      File.stubs(:readlines).with('/etc/facter/facts.d/foo.txt').returns(['foo=1+1=2'])
      subject.create
    end

    it 'should return successfully' do
      Facter.fact(:foo).value.should == '1+1=2'
    end
  end
end
