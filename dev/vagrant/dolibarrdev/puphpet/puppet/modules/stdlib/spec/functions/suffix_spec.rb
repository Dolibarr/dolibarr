#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the suffix function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "raises a ParseError if there is less than 1 arguments" do
    expect { scope.function_suffix([]) }.to raise_error(Puppet::ParseError, /number of arguments/)
  end

  it "raises an error if the first argument is not an array" do
    expect {
      scope.function_suffix([Object.new])
    }.to raise_error(Puppet::ParseError, /expected first argument to be an Array/)
  end

  it "raises an error if the second argument is not a string" do
    expect {
      scope.function_suffix([['first', 'second'], 42])
    }.to raise_error(Puppet::ParseError, /expected second argument to be a String/)
  end

  it "returns a suffixed array" do
    result = scope.function_suffix([['a','b','c'], 'p'])
    result.should(eq(['ap','bp','cp']))
  end
end
