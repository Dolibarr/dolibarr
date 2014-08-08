#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'concat function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should concat one array to another' do
      pp = <<-EOS
      $output = concat(['1','2','3'],['4','5','6'])
      validate_array($output)
      if size($output) != 6 {
        fail("${output} should have 6 elements.")
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end
end
