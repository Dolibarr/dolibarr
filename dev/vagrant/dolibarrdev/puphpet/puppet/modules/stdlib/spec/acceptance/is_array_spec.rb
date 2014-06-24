#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_array function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_arrays arrays' do
      pp = <<-EOS
      $a = ['aaa','bbb','ccc']
      $b = true
      $o = is_array($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_arrays empty arrays' do
      pp = <<-EOS
      $a = []
      $b = true
      $o = is_array($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_arrays strings' do
      pp = <<-EOS
      $a = "aoeu"
      $b = false
      $o = is_array($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_arrays hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'bbb'}
      $b = false
      $o = is_array($a)
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
    it 'handles non-arrays'
  end
end
