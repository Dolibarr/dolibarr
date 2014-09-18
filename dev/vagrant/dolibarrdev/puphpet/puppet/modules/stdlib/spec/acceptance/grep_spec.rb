#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'grep function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'greps arrays' do
      pp = <<-EOS
      $a = ['aaabbb','bbbccc','dddeee']
      $b = 'bbb'
      $c = ['aaabbb','bbbccc']
      $o = grep($a,$b)
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
    it 'handles improper argument counts'
    it 'handles non-arrays'
  end
end
