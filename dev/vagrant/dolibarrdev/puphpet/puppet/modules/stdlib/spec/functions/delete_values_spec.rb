#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the delete_values function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("delete_values").should == "function_delete_values"
  end

  it "should raise a ParseError if there are fewer than 2 arguments" do
    lambda { scope.function_delete_values([]) }.should( raise_error(Puppet::ParseError))
  end

  it "should raise a ParseError if there are greater than 2 arguments" do
    lambda { scope.function_delete_values([[], 'foo', 'bar']) }.should( raise_error(Puppet::ParseError))
  end

  it "should raise a TypeError if the argument is not a hash" do
    lambda { scope.function_delete_values([1,'bar']) }.should( raise_error(TypeError))
    lambda { scope.function_delete_values(['foo','bar']) }.should( raise_error(TypeError))
    lambda { scope.function_delete_values([[],'bar']) }.should( raise_error(TypeError))
  end

  it "should delete all instances of a value from a hash" do
    result = scope.function_delete_values([{ 'a'=>'A', 'b'=>'B', 'B'=>'C', 'd'=>'B' },'B'])
    result.should(eq({ 'a'=>'A', 'B'=>'C' }))
  end

  it "should not change origin hash passed as argument" do
    origin_hash = { 'a' => 1, 'b' => 2, 'c' => 3 }
    result = scope.function_delete_values([origin_hash, 2])
    origin_hash.should(eq({ 'a' => 1, 'b' => 2, 'c' => 3 }))
  end

end
