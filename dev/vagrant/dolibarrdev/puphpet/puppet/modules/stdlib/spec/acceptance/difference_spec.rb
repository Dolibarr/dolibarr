#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'difference function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'returns non-duplicates in the first array' do
      pp = <<-EOS
      $a = ['a','b','c']
      $b = ['b','c','d']
      $c = ['a']
      $o = difference($a, $b)
      if $o == $c {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
  end
  describe 'failure' do
    it 'handles non-array arguments'
    it 'handles improper argument counts'
  end
end
