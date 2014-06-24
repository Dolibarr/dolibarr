#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the union function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("union").should == "function_union"
  end

  it "should raise a ParseError if there are fewer than 2 arguments" do
    lambda { scope.function_union([]) }.should( raise_error(Puppet::ParseError) )
  end

  it "should join two arrays together" do
    result = scope.function_union([["a","b","c"],["b","c","d"]])
    result.should(eq(["a","b","c","d"]))
  end
end
