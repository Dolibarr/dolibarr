#! /usr/bin/env ruby

require 'spec_helper'
require 'rspec-puppet'
require 'puppet_spec/compiler'

describe 'ensure_packages' do
  include PuppetSpec::Compiler

  before :each do
    Puppet::Parser::Functions.autoloader.loadall
    Puppet::Parser::Functions.function(:ensure_packages)
    Puppet::Parser::Functions.function(:ensure_resource)
    Puppet::Parser::Functions.function(:defined_with_params)
    Puppet::Parser::Functions.function(:create_resources)
  end

  let :node     do Puppet::Node.new('localhost') end
  let :compiler do Puppet::Parser::Compiler.new(node) end
  let :scope    do
    if Puppet.version.to_f >= 3.0
      Puppet::Parser::Scope.new(compiler)
    else
      newscope = Puppet::Parser::Scope.new
      newscope.compiler = compiler
      newscope.source   = Puppet::Resource::Type.new(:node, :localhost)
      newscope
    end
  end

  describe 'argument handling' do
    it 'fails with no arguments' do
      expect {
        scope.function_ensure_packages([])
      }.to raise_error(Puppet::ParseError, /0 for 1 or 2/)
    end

    it 'accepts an array of values' do
      scope.function_ensure_packages([['foo']])
    end

    it 'accepts a single package name as a string' do
      scope.function_ensure_packages(['foo'])
    end
  end

  context 'given a catalog with puppet package => absent' do
    let :catalog do
      compile_to_catalog(<<-EOS
        ensure_packages(['facter'])
        package { puppet: ensure => absent }
      EOS
      )
    end

    it 'has no effect on Package[puppet]' do
      expect(catalog.resource(:package, 'puppet')['ensure']).to eq('absent')
    end
  end

  context 'given a clean catalog' do
    let :catalog do
      compile_to_catalog('ensure_packages(["facter"])')
    end

    it 'declares package resources with ensure => present' do
      expect(catalog.resource(:package, 'facter')['ensure']).to eq('present')
    end
  end

  context 'given a clean catalog and specified defaults' do
    let :catalog do
      compile_to_catalog('ensure_packages(["facter"], {"provider" => "gem"})')
    end

    it 'declares package resources with ensure => present' do
      expect(catalog.resource(:package, 'facter')['ensure']).to eq('present')
      expect(catalog.resource(:package, 'facter')['provider']).to eq('gem')
    end
  end
end
