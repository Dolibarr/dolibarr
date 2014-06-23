#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'abs function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should accept a string' do
      pp = <<-EOS
      $input  = '-34.56'
      $output = abs($input)
      notify { $output: }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: 34.56/)
      end
    end

    it 'should accept a float' do
      pp = <<-EOS
      $input  = -34.56
      $output = abs($input)
      notify { $output: }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: 34.56/)
      end
    end
  end
end
