#! /usr/bin/env ruby -S rspec

require "spec_helper"

describe Puppet::Parser::Functions.function(:validate_ipv4_address) do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  describe "when calling validate_ipv4_address from puppet" do
    describe "when given IPv4 address strings" do
      it "should compile with one argument" do
        Puppet[:code] = "validate_ipv4_address('1.2.3.4')"
        scope.compiler.compile
      end

      it "should compile with multiple arguments" do
        Puppet[:code] = "validate_ipv4_address('1.2.3.4', '5.6.7.8')"
        scope.compiler.compile
      end
    end

    describe "when given an IPv6 address" do
      it "should not compile" do
        Puppet[:code] = "validate_ipv4_address('3ffe:505')"
        expect {
          scope.compiler.compile
        }.to raise_error(Puppet::ParseError, /not a valid IPv4 address/)
      end
    end

    describe "when given other strings" do
      it "should not compile" do
        Puppet[:code] = "validate_ipv4_address('hello', 'world')"
        expect {
          scope.compiler.compile
        }.to raise_error(Puppet::ParseError, /not a valid IPv4 address/)
      end
    end

    describe "when given numbers" do
      it "should not compile" do
        Puppet[:code] = "validate_ipv4_address(1, 2)"
        expect {
          scope.compiler.compile
        }.to raise_error(Puppet::ParseError, /is not a valid IPv4 address/)
      end
    end

    describe "when given booleans" do
      it "should not compile" do
        Puppet[:code] = "validate_ipv4_address(true, false)"
        expect {
          scope.compiler.compile
        }.to raise_error(Puppet::ParseError, /is not a string/)
      end
    end

    it "should not compile when no arguments are passed" do
      Puppet[:code] = "validate_ipv4_address()"
      expect {
        scope.compiler.compile
      }.to raise_error(Puppet::ParseError, /wrong number of arguments/)
    end
  end
end
