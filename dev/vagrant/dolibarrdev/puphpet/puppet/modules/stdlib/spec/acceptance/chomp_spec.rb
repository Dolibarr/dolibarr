#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'chomp function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should eat the newline' do
      pp = <<-EOS
      $input = "test\n"
      if size($input) != 5 {
        fail("Size of ${input} is not 5.")
      }
      $output = chomp($input)
      if size($output) != 4 {
        fail("Size of ${input} is not 4.")
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end
end
