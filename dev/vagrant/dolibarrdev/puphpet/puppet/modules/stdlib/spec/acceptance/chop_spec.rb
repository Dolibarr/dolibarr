#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'chop function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should eat the last character' do
      pp = <<-EOS
      $input = "test"
      if size($input) != 4 {
        fail("Size of ${input} is not 4.")
      }
      $output = chop($input)
      if size($output) != 3 {
        fail("Size of ${input} is not 3.")
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    it 'should eat the last two characters of \r\n' do
      pp = <<-EOS
      $input = "test\r\n"
      if size($input) != 6 {
        fail("Size of ${input} is not 6.")
      }
      $output = chop($input)
      if size($output) != 4 {
        fail("Size of ${input} is not 4.")
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    it 'should not fail on empty strings' do
      pp = <<-EOS
      $input = ""
      $output = chop($input)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end
end
