#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'flatten function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'flattens arrays' do
      pp = <<-EOS
      $a = ["a","b",["c",["d","e"],"f","g"]]
      $b = ["a","b","c","d","e","f","g"]
      $o = flatten($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'does not affect flat arrays' do
      pp = <<-EOS
      $a = ["a","b","c","d","e","f","g"]
      $b = ["a","b","c","d","e","f","g"]
      $o = flatten($a)
      if $o == $b {
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
    it 'handles non-strings'
  end
end
