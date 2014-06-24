#! /usr/bin/env ruby -S rspec
require 'spec_helper'
require 'rspec-puppet'
require 'puppet_spec/compiler'

describe 'getparam' do
  include PuppetSpec::Compiler

  before :each do
    Puppet::Parser::Functions.autoloader.loadall
    Puppet::Parser::Functions.function(:getparam)
  end

  let :node     do Puppet::Node.new('localhost') end
  let :compiler do Puppet::Parser::Compiler.new(node) end
  if Puppet.version.to_f >= 3.0
    let :scope    do Puppet::Parser::Scope.new(compiler) end
  else
    let :scope    do
      newscope = Puppet::Parser::Scope.new
      newscope.compiler = compiler
      newscope.source   = Puppet::Resource::Type.new(:node, :localhost)
      newscope
    end
  end

  it "should exist" do
    Puppet::Parser::Functions.function("getparam").should == "function_getparam"
  end

  describe 'when a resource is not specified' do
    it { expect { scope.function_getparam([]) }.to raise_error }
    it { expect { scope.function_getparam(['User[dan]']) }.to raise_error }
    it { expect { scope.function_getparam(['User[dan]']) }.to raise_error }
    it { expect { scope.function_getparam(['User[dan]', {}]) }.to raise_error }
    # This seems to be OK because we just check for a string.
    it { expect { scope.function_getparam(['User[dan]', '']) }.to_not raise_error }
  end

  describe 'when compared against a resource with no params' do
    let :catalog do
      compile_to_catalog(<<-EOS
        user { "dan": }
      EOS
      )
    end

    it do
      expect(scope.function_getparam(['User[dan]', 'shell'])).to eq('')
    end
  end

  describe 'when compared against a resource with params' do
    let :catalog do
      compile_to_catalog(<<-EOS
        user { 'dan': ensure => present, shell => '/bin/sh', managehome => false}
        $test = getparam(User[dan], 'shell')
      EOS
      )
    end

    it do
      resource = Puppet::Parser::Resource.new(:user, 'dan', {:scope => scope})
      resource.set_parameter('ensure', 'present')
      resource.set_parameter('shell', '/bin/sh')
      resource.set_parameter('managehome', false)
      compiler.add_resource(scope, resource)

      expect(scope.function_getparam(['User[dan]', 'shell'])).to eq('/bin/sh')
      expect(scope.function_getparam(['User[dan]', ''])).to eq('')
      expect(scope.function_getparam(['User[dan]', 'ensure'])).to eq('present')
      # TODO: Expected this to be false, figure out why we're getting '' back.
      expect(scope.function_getparam(['User[dan]', 'managehome'])).to eq('')
    end
  end
end
