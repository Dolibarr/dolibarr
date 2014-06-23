#!/usr/bin/env rspec
require 'spec_helper'

describe "the scope_defaults function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("scope_defaults").should == "function_scope_defaults"
  end

  it "should raise a ParseError if there is less than 2 arguments" do
    expect{ scope.function_scope_defaults([]) }.
      to raise_error(Puppet::ParseError)
  end

  it "should raise a ParseError if there is more than 2 arguments" do
    expect{ scope.function_scope_defaults(['exec', 'path', 'error']) }.
      to raise_error(Puppet::ParseError)
  end

  it "should return false for invalid resource" do
    result = scope.function_scope_defaults(['foo', 'path'])
    result.should(eq(false))
  end

  it "should return false for resource without default attributes" do
    if scope.respond_to? :define_settings
      scope.define_settings('Exec', Puppet::Parser::Resource::Param.new(:name => :path, :value => "/bin"))
    else
      scope.setdefaults('Exec', Puppet::Parser::Resource::Param.new(:name => :path, :value => "/bin"))
    end
    result = scope.function_scope_defaults(['Exec', 'foo'])
    result.should(eq(false))
  end

  it "should return true for resource with default attributes" do
    if scope.respond_to? :define_settings
      scope.define_settings('Exec', Puppet::Parser::Resource::Param.new(:name => :path, :value => "/bin"))
    else
      scope.setdefaults('Exec', Puppet::Parser::Resource::Param.new(:name => :path, :value => "/bin"))
    end
    result = scope.function_scope_defaults(['Exec', 'path'])
    result.should(eq(true))
  end
end
