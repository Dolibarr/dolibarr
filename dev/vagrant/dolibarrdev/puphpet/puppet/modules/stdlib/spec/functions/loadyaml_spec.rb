#! /usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the loadyaml function" do
  include PuppetlabsSpec::Files

  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("loadyaml").should == "function_loadyaml"
  end

  it "should raise a ParseError if there is less than 1 arguments" do
    expect { scope.function_loadyaml([]) }.to raise_error(Puppet::ParseError)
  end

  it "should convert YAML file to a data structure" do
    yaml_file = tmpfilename ('yamlfile')
    File.open(yaml_file, 'w') do |fh|
      fh.write("---\n aaa: 1\n bbb: 2\n ccc: 3\n ddd: 4\n")
    end
    result = scope.function_loadyaml([yaml_file])
    result.should == {"aaa" => 1, "bbb" => 2, "ccc" => 3, "ddd" => 4 }
  end
end
