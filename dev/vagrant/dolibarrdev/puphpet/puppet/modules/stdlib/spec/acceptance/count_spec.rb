#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'count function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should count elements in an array' do
      pp = <<-EOS
      $input = [1,2,3,4]
      $output = count($input)
      notify { $output: }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: 4/)
      end
    end

    it 'should count elements in an array that match a second argument' do
      pp = <<-EOS
      $input = [1,1,1,2]
      $output = count($input, 1)
      notify { $output: }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: 3/)
      end
    end
  end
end
