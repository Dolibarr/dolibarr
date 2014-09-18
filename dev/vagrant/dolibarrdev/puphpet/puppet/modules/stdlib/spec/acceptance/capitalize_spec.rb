#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'capitalize function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should capitalize the first letter of a string' do
      pp = <<-EOS
      $input = 'this is a string'
      $output = capitalize($input)
      notify { $output: }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: This is a string/)
      end
    end

    it 'should capitalize the first letter of an array of strings' do
      pp = <<-EOS
      $input = ['this', 'is', 'a', 'string']
      $output = capitalize($input)
      notify { $output: }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: This/)
        expect(r.stdout).to match(/Notice: Is/)
        expect(r.stdout).to match(/Notice: A/)
        expect(r.stdout).to match(/Notice: String/)
      end
    end
  end
end
