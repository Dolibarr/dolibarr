#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_string function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a single argument' do
      pp = <<-EOS
      $one = 'string'
      validate_string($one)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates an multiple arguments' do
      pp = <<-EOS
      $one = 'string'
      $two = 'also string'
      validate_string($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates a non-string' do
      {
        %{validate_string({ 'a' => 'hash' })} => "Hash",
        %{validate_string(['array'])}         => "Array",
        %{validate_string(false)}             => "FalseClass",
      }.each do |pp,type|
        expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/a #{type}/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
  end
end
