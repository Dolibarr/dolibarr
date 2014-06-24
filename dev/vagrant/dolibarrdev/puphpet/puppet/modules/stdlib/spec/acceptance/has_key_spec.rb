#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'has_key function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'has_keys in hashes' do
      pp = <<-EOS
      $a = { 'aaa' => 'bbb','bbb' => 'ccc','ddd' => 'eee' }
      $b = 'bbb'
      $c = true
      $o = has_key($a,$b)
      if $o == $c {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'has_keys not in hashes' do
      pp = <<-EOS
      $a = { 'aaa' => 'bbb','bbb' => 'ccc','ddd' => 'eee' }
      $b = 'ccc'
      $c = false
      $o = has_key($a,$b)
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
    it 'handles non-hashes'
  end
end
