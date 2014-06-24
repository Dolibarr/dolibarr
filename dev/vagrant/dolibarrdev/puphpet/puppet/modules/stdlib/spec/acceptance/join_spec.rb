#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'join function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'joins arrays' do
      pp = <<-EOS
      $a = ['aaa','bbb','ccc']
      $b = ':'
      $c = 'aaa:bbb:ccc'
      $o = join($a,$b)
      if $o == $c {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'handles non arrays'
  end
  describe 'failure' do
    it 'handles improper argument counts'
  end
end
