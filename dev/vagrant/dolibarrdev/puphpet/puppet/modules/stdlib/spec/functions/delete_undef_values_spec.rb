#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the delete_undef_values function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("delete_undef_values").should == "function_delete_undef_values"
  end

  it "should raise a ParseError if there is less than 1 argument" do
    lambda { scope.function_delete_undef_values([]) }.should( raise_error(Puppet::ParseError))
  end

  it "should raise a ParseError if the argument is not Array nor Hash" do
    lambda { scope.function_delete_undef_values(['']) }.should( raise_error(Puppet::ParseError))
    lambda { scope.function_delete_undef_values([nil]) }.should( raise_error(Puppet::ParseError))
  end

  it "should delete all undef items from Array and only these" do
    result = scope.function_delete_undef_values([['a',:undef,'c','undef']])
    result.should(eq(['a','c','undef']))
  end

  it "should delete all undef items from Hash and only these" do
    result = scope.function_delete_undef_values([{'a'=>'A','b'=>:undef,'c'=>'C','d'=>'undef'}])
    result.should(eq({'a'=>'A','c'=>'C','d'=>'undef'}))
  end

  it "should not change origin array passed as argument" do
    origin_array = ['a',:undef,'c','undef']
    result = scope.function_delete_undef_values([origin_array])
    origin_array.should(eq(['a',:undef,'c','undef']))
  end

  it "should not change origin hash passed as argument" do
    origin_hash = { 'a' => 1, 'b' => :undef, 'c' => 'undef' }
    result = scope.function_delete_undef_values([origin_hash])
    origin_hash.should(eq({ 'a' => 1, 'b' => :undef, 'c' => 'undef' }))
  end
end
