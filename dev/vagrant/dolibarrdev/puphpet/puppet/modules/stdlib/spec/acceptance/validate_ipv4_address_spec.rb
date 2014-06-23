#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_ipv4_address function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a single argument' do
      pp = <<-EOS
      $one = '1.2.3.4'
      validate_ipv4_address($one)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates an multiple arguments' do
      pp = <<-EOS
      $one = '1.2.3.4'
      $two = '5.6.7.8'
      validate_ipv4_address($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
    it 'handles ipv6 addresses'
    it 'handles non-ipv4 strings'
    it 'handles numbers'
    it 'handles no arguments'
  end
end
