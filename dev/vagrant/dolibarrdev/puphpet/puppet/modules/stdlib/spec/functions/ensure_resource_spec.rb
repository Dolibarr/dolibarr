#! /usr/bin/env ruby -S rspec
require 'spec_helper'
require 'rspec-puppet'
require 'puppet_spec/compiler'

describe 'ensure_resource' do
  include PuppetSpec::Compiler

  before :all do
    Puppet::Parser::Functions.autoloader.loadall
    Puppet::Parser::Functions.function(:ensure_packages)
  end

  let :node     do Puppet::Node.new('localhost') end
  let :compiler do Puppet::Parser::Compiler.new(node) end
  let :scope    do Puppet::Parser::Scope.new(compiler) end

  describe 'when a type or title is not specified' do
    it { expect { scope.function_ensure_resource([]) }.to raise_error }
    it { expect { scope.function_ensure_resource(['type']) }.to raise_error }
  end

  describe 'when compared against a resource with no attributes' do
    let :catalog do
      compile_to_catalog(<<-EOS
        user { "dan": }
        ensure_resource('user', 'dan', {})
      EOS
      )
    end

    it 'should contain the the ensured resources' do
      expect(catalog.resource(:user, 'dan').to_s).to eq('User[dan]')
    end
  end

  describe 'works when compared against a resource with non-conflicting attributes' do
    [
      "ensure_resource('User', 'dan', {})",
      "ensure_resource('User', 'dan', '')",
      "ensure_resource('User', 'dan', {'ensure' => 'present'})",
      "ensure_resource('User', 'dan', {'ensure' => 'present', 'managehome' => false})"
    ].each do |ensure_resource|
      pp = <<-EOS
        user { "dan": ensure => present, shell => "/bin/csh", managehome => false}
        #{ensure_resource}
      EOS

      it { expect { compile_to_catalog(pp) }.to_not raise_error }
    end
  end

  describe 'fails when compared against a resource with conflicting attributes' do
    pp = <<-EOS
      user { "dan": ensure => present, shell => "/bin/csh", managehome => false}
      ensure_resource('User', 'dan', {'ensure' => 'absent', 'managehome' => false})
    EOS

    it { expect { compile_to_catalog(pp) }.to raise_error }
  end

  describe 'when an array of new resources are passed in' do
    let :catalog do
      compile_to_catalog("ensure_resource('User', ['dan', 'alex'], {})")
    end

    it 'should contain the ensured resources' do
      expect(catalog.resource('User[dan]').to_s).to eq('User[dan]')
      expect(catalog.resource('User[alex]').to_s).to eq('User[alex]')
    end
  end

  describe 'when an array of existing resources is compared against existing resources' do
    pp = <<-EOS
      user { 'dan': ensure => present; 'alex': ensure => present }
      ensure_resource('User', ['dan', 'alex'], {})
    EOS

    let :catalog do
      compile_to_catalog(pp)
    end

    it 'should return the existing resources' do
      expect(catalog.resource('User[dan]').to_s).to eq('User[dan]')
      expect(catalog.resource('User[alex]').to_s).to eq('User[alex]')
    end
  end

  describe 'works when compared against existing resources with attributes' do
    [
      "ensure_resource('User', ['dan', 'alex'], {})",
      "ensure_resource('User', ['dan', 'alex'], '')",
      "ensure_resource('User', ['dan', 'alex'], {'ensure' => 'present'})",
    ].each do |ensure_resource|
      pp = <<-EOS
        user { 'dan': ensure => present; 'alex': ensure => present }
        #{ensure_resource}
      EOS

      it { expect { compile_to_catalog(pp) }.to_not raise_error }
    end
  end

  describe 'fails when compared against existing resources with conflicting attributes' do
    pp = <<-EOS
      user { 'dan': ensure => present; 'alex': ensure => present }
      ensure_resource('User', ['dan', 'alex'], {'ensure' => 'absent'})
    EOS

    it { expect { compile_to_catalog(pp) }.to raise_error }
  end

end
