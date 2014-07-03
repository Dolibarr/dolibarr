#!/usr/bin/env rspec
require 'spec_helper'

describe "the staging parser function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("staging_parse").should == "function_staging_parse"
  end

  it "should raise a ParseError if there is less than 1 arguments" do
    lambda { scope.function_staging_parse([]) }.should( raise_error(Puppet::ParseError))
  end

  it "should raise a ParseError if there is more than 3 arguments" do
    lambda { scope.function_staging_parse(['/etc', 'filename', '.zip', 'error']) }.should( raise_error(Puppet::ParseError))
  end

  it "should raise a ParseError if there is an invalid info request" do
    lambda { scope.function_staging_parse(['/etc', 'sheep', '.zip']) }.should( raise_error(Puppet::ParseError))
  end

  it "should raise a ParseError if 'source' doesn't have a URI path component" do
    lambda { scope.function_staging_parse(['uri:without-path']) }.should( raise_error(Puppet::ParseError, /has no URI 'path' component/))
  end

  it "should return the filename by default" do
    result = scope.function_staging_parse(["/etc/puppet/sample.tar.gz"])
    result.should(eq('sample.tar.gz'))
  end

  it "should return the file basename" do
    result = scope.function_staging_parse(["/etc/puppet/sample.tar.gz", "basename"])
    result.should(eq('sample.tar'))
  end

  it "should return the file basename with custom extensions" do
    result = scope.function_staging_parse(["/etc/puppet/sample.tar.gz", "basename", ".tar.gz"])
    result.should(eq('sample'))
  end

  it "should return the file extname" do
    result = scope.function_staging_parse(["/etc/puppet/sample.tar.gz", "extname"])
    result.should(eq('.gz'))
  end

  it "should return the file parent" do
    result = scope.function_staging_parse(["/etc/puppet/sample.tar.gz", "parent"])
    result.should(eq('/etc/puppet'))
  end
end
