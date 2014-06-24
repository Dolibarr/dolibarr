#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_array function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a single argument' do
      pp = <<-EOS
      $one = ['a', 'b']
      validate_array($one)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates an multiple arguments' do
      pp = <<-EOS
      $one = ['a', 'b']
      $two = [['c'], 'd']
      validate_array($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates a non-array' do
      {
        %{validate_array({'a' => 'hash' })} => "Hash",
        %{validate_array('string')}         => "String",
        %{validate_array(false)}            => "FalseClass",
        %{validate_array(undef)}            => "String"
      }.each do |pp,type|
        expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/a #{type}/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
  end
end
