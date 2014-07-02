#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'member function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'members arrays' do
      pp = <<-EOS
      $a = ['aaa','bbb','ccc']
      $b = 'ccc'
      $c = true
      $o = member($a,$b)
      if $o == $c {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'members arrays without members'
  end
  describe 'failure' do
    it 'handles improper argument counts'
  end
end
