#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the range function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "exists" do
    Puppet::Parser::Functions.function("range").should == "function_range"
  end

  it "raises a ParseError if there is less than 1 arguments" do
    expect { scope.function_range([]) }.to raise_error Puppet::ParseError, /Wrong number of arguments.*0 for 1/
  end

  describe 'with a letter range' do
    it "returns a letter range" do
      result = scope.function_range(["a","d"])
      result.should eq ['a','b','c','d']
    end

    it "returns a letter range given a step of 1" do
      result = scope.function_range(["a","d","1"])
      result.should eq ['a','b','c','d']
    end

    it "returns a stepped letter range" do
      result = scope.function_range(["a","d","2"])
      result.should eq ['a','c']
    end

    it "returns a stepped letter range given a negative step" do
      result = scope.function_range(["a","d","-2"])
      result.should eq ['a','c']
    end
  end

  describe 'with a number range' do
    it "returns a number range" do
      result = scope.function_range(["1","4"])
      result.should eq [1,2,3,4]
    end

    it "returns a number range given a step of 1" do
      result = scope.function_range(["1","4","1"])
      result.should eq [1,2,3,4]
    end

    it "returns a stepped number range" do
      result = scope.function_range(["1","4","2"])
      result.should eq [1,3]
    end

    it "returns a stepped number range given a negative step" do
      result = scope.function_range(["1","4","-2"])
      result.should eq [1,3]
    end
  end

  describe 'with a numeric-like string range' do
    it "works with padded hostname like strings" do
      expected = ("host01".."host10").to_a
      scope.function_range(["host01","host10"]).should eq expected
    end

    it "coerces zero padded digits to integers" do
      expected = (0..10).to_a
      scope.function_range(["00", "10"]).should eq expected
    end
  end
end
