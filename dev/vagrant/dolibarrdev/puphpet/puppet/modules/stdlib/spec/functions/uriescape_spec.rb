#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the uriescape function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("uriescape").should == "function_uriescape"
  end

  it "should raise a ParseError if there is less than 1 arguments" do
    lambda { scope.function_uriescape([]) }.should( raise_error(Puppet::ParseError))
  end

  it "should uriescape a string" do
    result = scope.function_uriescape([":/?#[]@!$&'()*+,;= \"{}"])
    result.should(eq(':/?%23[]@!$&\'()*+,;=%20%22%7B%7D'))
  end

  it "should do nothing if a string is already safe" do
    result = scope.function_uriescape(["ABCdef"])
    result.should(eq('ABCdef'))
  end
end
