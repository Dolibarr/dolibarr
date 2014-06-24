#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'deep_merge function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should deep merge two hashes' do
      pp = <<-EOS
      $hash1 = {'one' => 1, 'two' => 2, 'three' => { 'four' => 4 } }
      $hash2 = {'two' => 'dos', 'three' => { 'five' => 5 } }
      $merged_hash = deep_merge($hash1, $hash2)

      if $merged_hash != { 'one' => 1, 'two' => 'dos', 'three' => { 'four' => 4, 'five' => 5 } } {
        fail("Hash was incorrectly merged.")
      }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end
end
