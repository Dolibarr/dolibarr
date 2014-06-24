#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the is_numeric function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("is_numeric").should == "function_is_numeric"
  end

  it "should raise a ParseError if there is less than 1 argument" do
    lambda { scope.function_is_numeric([]) }.should( raise_error(Puppet::ParseError))
  end

  it "should return true if an integer" do
    result = scope.function_is_numeric(["3"])
    result.should(eq(true))
  end

  it "should return true if a float" do
    result = scope.function_is_numeric(["3.2"])
    result.should(eq(true))
  end

  it "should return true if an integer is created from an arithmetical operation" do
    result = scope.function_is_numeric([3*2])
    result.should(eq(true))
  end

  it "should return true if a float is created from an arithmetical operation" do
    result = scope.function_is_numeric([3.2*2])
    result.should(eq(true))
  end

  it "should return false if a string" do
    result = scope.function_is_numeric(["asdf"])
    result.should(eq(false))
  end

  it "should return false if an array" do
    result = scope.function_is_numeric([["asdf"]])
    result.should(eq(false))
  end

  it "should return false if an array of integers" do
    result = scope.function_is_numeric([[1,2,3,4]])
    result.should(eq(false))
  end

  it "should return false if a hash" do
    result = scope.function_is_numeric([{"asdf" => false}])
    result.should(eq(false))
  end

  it "should return false if a hash with numbers in it" do
    result = scope.function_is_numeric([{1 => 2}])
    result.should(eq(false))
  end

  it "should return false if a boolean" do
    result = scope.function_is_numeric([true])
    result.should(eq(false))
  end

  it "should return true if a negative float with exponent" do
    result = scope.function_is_numeric(["-342.2315e-12"])
    result.should(eq(true))
  end

  it "should return false if a negative integer with whitespaces before/after the dash" do
    result = scope.function_is_numeric([" -  751"])
    result.should(eq(false))
  end

#  it "should return true if a hexadecimal" do
#    result = scope.function_is_numeric(["0x52F8c"])
#    result.should(eq(true))
#  end
#
#  it "should return true if a hexadecimal with uppercase 0X prefix" do
#    result = scope.function_is_numeric(["0X52F8c"])
#    result.should(eq(true))
#  end
#
#  it "should return false if a hexadecimal without a prefix" do
#    result = scope.function_is_numeric(["52F8c"])
#    result.should(eq(false))
#  end
#
#  it "should return true if a octal" do
#    result = scope.function_is_numeric(["0751"])
#    result.should(eq(true))
#  end
#
#  it "should return true if a negative hexadecimal" do
#    result = scope.function_is_numeric(["-0x52F8c"])
#    result.should(eq(true))
#  end
#
#  it "should return true if a negative octal" do
#    result = scope.function_is_numeric(["-0751"])
#    result.should(eq(true))
#  end
#
#  it "should return false if a negative octal with whitespaces before/after the dash" do
#    result = scope.function_is_numeric([" -  0751"])
#    result.should(eq(false))
#  end
#
#  it "should return false if a bad hexadecimal" do
#    result = scope.function_is_numeric(["0x23d7g"])
#    result.should(eq(false))
#  end
#
#  it "should return false if a bad octal" do
#    result = scope.function_is_numeric(["0287"])
#    result.should(eq(false))
#  end
end
