#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the intersection function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("intersection").should == "function_intersection"
  end

  it "should raise a ParseError if there are fewer than 2 arguments" do
    lambda { scope.function_intersection([]) }.should( raise_error(Puppet::ParseError) )
  end

  it "should return the intersection of two arrays" do
    result = scope.function_intersection([["a","b","c"],["b","c","d"]])
    result.should(eq(["b","c"]))
  end
end
