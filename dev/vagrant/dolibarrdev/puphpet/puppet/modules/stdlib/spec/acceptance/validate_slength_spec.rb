#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_slength function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a single string max' do
      pp = <<-EOS
      $one = 'discombobulate'
      $two = 17
      validate_slength($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates multiple string maxes' do
      pp = <<-EOS
      $one = ['discombobulate', 'moo']
      $two = 17
      validate_slength($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates min/max of  strings in array' do
      pp = <<-EOS
      $one = ['discombobulate', 'moo']
      $two = 17
      $three = 3
      validate_slength($one,$two,$three)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates a single string max of incorrect length' do
      pp = <<-EOS
      $one = 'discombobulate'
      $two = 1
      validate_slength($one,$two)
      EOS

      apply_manifest(pp, :expect_failures => true)
    end
    it 'validates multiple string maxes of incorrect length' do
      pp = <<-EOS
      $one = ['discombobulate', 'moo']
      $two = 3
      validate_slength($one,$two)
      EOS

      apply_manifest(pp, :expect_failures => true)
    end
    it 'validates multiple strings min/maxes of incorrect length' do
      pp = <<-EOS
      $one = ['discombobulate', 'moo']
      $two = 17
      $three = 10
      validate_slength($one,$two,$three)
      EOS

      apply_manifest(pp, :expect_failures => true)
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
    it 'handles improper first argument type'
    it 'handles non-strings in array of first argument'
    it 'handles improper second argument type'
    it 'handles improper third argument type'
    it 'handles negative ranges'
    it 'handles improper ranges'
  end
end
