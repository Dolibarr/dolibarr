#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_hash function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'validates a single argument' do
      pp = <<-EOS
      $one = { 'a' => 1 }
      validate_hash($one)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates an multiple arguments' do
      pp = <<-EOS
      $one = { 'a' => 1 }
      $two = { 'b' => 2 }
      validate_hash($one,$two)
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
    it 'validates a non-hash' do
      {
        %{validate_hash('{ "not" => "hash" }')} => "String",
        %{validate_hash('string')}              => "String",
        %{validate_hash(["array"])}             => "Array",
        %{validate_hash(undef)}                 => "String",
      }.each do |pp,type|
        expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/a #{type}/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
  end
end
