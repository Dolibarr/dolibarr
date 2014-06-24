#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_re function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a string' do
      pp = <<-EOS
      $one = 'one'
      $two = '^one$'
      validate_re($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates an array' do
      pp = <<-EOS
      $one = 'one'
      $two = ['^one$', '^two']
      validate_re($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates a failed array' do
      pp = <<-EOS
      $one = 'one'
      $two = ['^two$', '^three']
      validate_re($one,$two)
      EOS

      apply_manifest(pp, :expect_failures => true)
    end
    it 'validates a failed array with a custom error message' do
      pp = <<-EOS
      $one = '3.4.3'
      $two = '^2.7'
      validate_re($one,$two,"The $puppetversion fact does not match 2.7")
      EOS

      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/does not match/)
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
    it 'handles improper argument types'
  end
end
