#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_cmd function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a true command' do
      pp = <<-EOS
      $one = 'foo'
      if $::osfamily == 'windows' {
        $two = 'echo' #shell built-in
      } else {
        $two = '/bin/echo'
      }
      validate_cmd($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates a fail command' do
      pp = <<-EOS
      $one = 'foo'
      if $::osfamily == 'windows' {
        $two = 'C:/aoeu'
      } else {
        $two = '/bin/aoeu'
      }
      validate_cmd($one,$two)
      EOS

      apply_manifest(pp, :expect_failures => true)
    end
    it 'validates a fail command with a custom error message' do
      pp = <<-EOS
      $one = 'foo'
      if $::osfamily == 'windows' {
        $two = 'C:/aoeu'
      } else {
        $two = '/bin/aoeu'
      }
      validate_cmd($one,$two,"aoeu is dvorak)
      EOS

      expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/aoeu is dvorak/)
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
    it 'handles improper argument types'
  end
end
