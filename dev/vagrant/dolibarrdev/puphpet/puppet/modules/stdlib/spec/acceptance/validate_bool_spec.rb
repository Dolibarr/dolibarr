#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_bool function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a single argument' do
      pp = <<-EOS
      $one = true
      validate_bool($one)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates an multiple arguments' do
      pp = <<-EOS
      $one = true
      $two = false
      validate_bool($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates a non-bool' do
      {
        %{validate_bool('true')}  => "String",
        %{validate_bool('false')} => "String",
        %{validate_bool([true])}  => "Array",
        %{validate_bool(undef)}   => "String",
      }.each do |pp,type|
        expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/a #{type}/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
  end
end
