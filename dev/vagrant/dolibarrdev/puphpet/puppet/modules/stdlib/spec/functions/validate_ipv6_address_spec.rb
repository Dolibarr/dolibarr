#! /usr/bin/env ruby -S rspec

require "spec_helper"

describe Puppet::Parser::Functions.function(:validate_ipv6_address) do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  describe "when calling validate_ipv6_address from puppet" do
    describe "when given IPv6 address strings" do
      it "should compile with one argument" do
        Puppet[:code] = "validate_ipv6_address('3ffe:0505:0002::')"
        scope.compiler.compile
      end

      it "should compile with multiple arguments" do
        Puppet[:code] = "validate_ipv6_address('3ffe:0505:0002::', '3ffe:0505:0001::')"
        scope.compiler.compile
      end
    end

    describe "when given an ipv4 address" do
      it "should not compile" do
        Puppet[:code] = "validate_ipv6_address('1.2.3.4')"
        expect {
          scope.compiler.compile
        }.to raise_error(Puppet::ParseError, /not a valid IPv6 address/)
      end
    end

    describe "when given other strings" do
      it "should not compile" do
        Puppet[:code] = "validate_ipv6_address('hello', 'world')"
        expect {
          scope.compiler.compile
        }.to raise_error(Puppet::ParseError, /not a valid IPv6 address/)
      end
    end

    # 1.8.7 is EOL'd and also absolutely insane about ipv6
    unless RUBY_VERSION == '1.8.7'
      describe "when given numbers" do
        it "should not compile" do
          Puppet[:code] = "validate_ipv6_address(1, 2)"
          expect {
            scope.compiler.compile
          }.to raise_error(Puppet::ParseError, /not a valid IPv6 address/)
        end
      end
    end

    describe "when given booleans" do
      it "should not compile" do
        Puppet[:code] = "validate_ipv6_address(true, false)"
        expect {
          scope.compiler.compile
        }.to raise_error(Puppet::ParseError, /is not a string/)
      end
    end

    it "should not compile when no arguments are passed" do
      Puppet[:code] = "validate_ipv6_address()"
      expect {
        scope.compiler.compile
      }.to raise_error(Puppet::ParseError, /wrong number of arguments/)
    end
  end
end
