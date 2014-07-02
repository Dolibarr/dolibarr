#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'delete_at function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should delete elements of the array' do
      pp = <<-EOS
      $output = delete_at(['a','b','c','b'], 1)
      if $output == ['a','c','b'] {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
  end
end
