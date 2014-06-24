#! /usr/bin/env ruby -S rspec
require 'spec_helper'

TESTEXE = File.exists?('/usr/bin/test') ? '/usr/bin/test' : '/bin/test'
TOUCHEXE = File.exists?('/usr/bin/touch') ? '/usr/bin/touch' : '/bin/touch'

describe Puppet::Parser::Functions.function(:validate_cmd) do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  subject do
    function_name = Puppet::Parser::Functions.function(:validate_cmd)
    scope.method(function_name)
  end

  describe "with an explicit failure message" do
    it "prints the failure message on error" do
      expect {
        subject.call ['', '/bin/false', 'failure message!']
      }.to raise_error Puppet::ParseError, /failure message!/
    end
  end

  describe "on validation failure" do
    it "includes the command error output" do
      expect {
        subject.call ['', "#{TOUCHEXE} /cant/touch/this"]
      }.to raise_error Puppet::ParseError, /(cannot touch|o such file or)/
    end

    it "includes the command return value" do
      expect {
        subject.call ['', '/cant/run/this']
      }.to raise_error Puppet::ParseError, /returned 1\b/
    end
  end

  describe "when performing actual validation" do
    it "can positively validate file content" do
      expect { subject.call ["non-empty", "#{TESTEXE} -s"] }.to_not raise_error
    end

    it "can negatively validate file content" do
      expect {
        subject.call ["", "#{TESTEXE} -s"]
      }.to raise_error Puppet::ParseError, /failed to validate.*test -s/
    end
  end
end
