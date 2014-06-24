#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'intersection function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'intersections arrays' do
      pp = <<-EOS
      $a = ['aaa','bbb','ccc']
      $b = ['bbb','ccc','ddd','eee']
      $c = ['bbb','ccc']
      $o = intersection($a,$b)
      if $o == $c {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'intersections empty arrays'
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-arrays'
  end
end
