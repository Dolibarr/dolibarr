#! /usr/bin/env ruby -S rspec

require 'spec_helper'

describe "the base64 function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("base64").should == "function_base64"
  end

  it "should raise a ParseError if there are other than 2 arguments" do
    expect { scope.function_base64([]) }.to(raise_error(Puppet::ParseError))
    expect { scope.function_base64(["asdf"]) }.to(raise_error(Puppet::ParseError))
    expect { scope.function_base64(["asdf","moo","cow"]) }.to(raise_error(Puppet::ParseError))
  end

  it "should raise a ParseError if argument 1 isn't 'encode' or 'decode'" do
    expect { scope.function_base64(["bees","astring"]) }.to(raise_error(Puppet::ParseError, /first argument must be one of/))
  end

  it "should raise a ParseError if argument 2 isn't a string" do
    expect { scope.function_base64(["encode",["2"]]) }.to(raise_error(Puppet::ParseError, /second argument must be a string/))
  end

  it "should encode a encoded string" do
    result = scope.function_base64(["encode",'thestring'])
    result.should =~ /\AdGhlc3RyaW5n\n\Z/
  end
  it "should decode a base64 encoded string" do
    result = scope.function_base64(["decode",'dGhlc3RyaW5n'])
    result.should == 'thestring'
  end
end
