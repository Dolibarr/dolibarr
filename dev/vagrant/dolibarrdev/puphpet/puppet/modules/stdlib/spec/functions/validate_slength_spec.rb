#! /usr/bin/env ruby -S rspec

require 'spec_helper'

describe "the validate_slength function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("validate_slength").should == "function_validate_slength"
  end

  describe "validating the input argument types" do
    it "raises an error if there are less than two arguments" do
      expect { scope.function_validate_slength([]) }.to raise_error Puppet::ParseError, /Wrong number of arguments/
    end

    it "raises an error if there are more than three arguments" do
      expect { scope.function_validate_slength(['input', 1, 2, 3]) }.to raise_error Puppet::ParseError, /Wrong number of arguments/
    end

    it "raises an error if the first argument is not a string" do
      expect { scope.function_validate_slength([Object.new, 2, 1]) }.to raise_error Puppet::ParseError, /Expected first argument.*got .*Object/
    end

    it "raises an error if the second argument cannot be cast to an Integer" do
      expect { scope.function_validate_slength(['input', Object.new]) }.to raise_error Puppet::ParseError, /Expected second argument.*got .*Object/
    end

    it "raises an error if the third argument cannot be cast to an Integer" do
      expect { scope.function_validate_slength(['input', 1, Object.new]) }.to raise_error Puppet::ParseError, /Expected third argument.*got .*Object/
    end

    it "raises an error if the second argument is smaller than the third argument" do
      expect { scope.function_validate_slength(['input', 1, 2]) }.to raise_error Puppet::ParseError, /Expected second argument to be larger than third argument/
    end
  end

  describe "validating the input string length" do
    describe "when the input is a string" do
      it "fails validation if the string is larger than the max length" do
        expect { scope.function_validate_slength(['input', 1]) }.to raise_error Puppet::ParseError, /Expected length .* between 0 and 1, was 5/
      end

      it "fails validation if the string is less than the min length" do
        expect { scope.function_validate_slength(['input', 10, 6]) }.to raise_error Puppet::ParseError, /Expected length .* between 6 and 10, was 5/
      end

      it "doesn't raise an error if the string is under the max length" do
        scope.function_validate_slength(['input', 10])
      end

      it "doesn't raise an error if the string is equal to the max length" do
        scope.function_validate_slength(['input', 5])
      end

      it "doesn't raise an error if the string is equal to the min length" do
        scope.function_validate_slength(['input', 10, 5])
      end
    end

    describe "when the input is an array" do
      it "fails validation if one of the array elements is not a string" do
        expect { scope.function_validate_slength([["a", "b", Object.new], 2]) }.to raise_error Puppet::ParseError, /Expected element at array position 2 .*String, got .*Object/
      end
    end
  end
end
