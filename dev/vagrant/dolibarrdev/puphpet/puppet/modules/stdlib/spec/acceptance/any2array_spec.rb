#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'any2array function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should create an empty array' do
      pp = <<-EOS
      $input = ''
      $output = any2array($input)
      validate_array($output)
      notify { "Output: ${output}": }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: Output: /)
      end
    end

    it 'should leave arrays modified' do
      pp = <<-EOS
      $input = ['test', 'array']
      $output = any2array($input)
      validate_array($output)
      notify { "Output: ${output}": }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: Output: testarray/)
      end
    end

    it 'should turn a hash into an array' do
      pp = <<-EOS
      $input = {'test' => 'array'}
      $output = any2array($input)

      validate_array($output)
      # Check each element of the array is a plain string.
      validate_string($output[0])
      validate_string($output[1])
      notify { "Output: ${output}": }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: Output: testarray/)
      end
    end
  end
end
