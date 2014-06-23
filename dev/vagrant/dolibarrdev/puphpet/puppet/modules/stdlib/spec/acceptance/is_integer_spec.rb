#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_integer function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_integers arrays' do
      pp = <<-EOS
      $a = ['aaa.com','bbb','ccc']
      $b = false
      $o = is_integer($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_integers true' do
      pp = <<-EOS
      $a = true
      $b = false
      $o = is_integer($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_integers strings' do
      pp = <<-EOS
      $a = "3"
      $b = true
      $o = is_integer($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_integers floats' do
      pp = <<-EOS
      $a = 3.5
      $b = false
      $o = is_integer($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_integers integers' do
      pp = <<-EOS
      $a = 3
      $b = true
      $o = is_integer($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_integers hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'www.com'}
      $b = false
      $o = is_integer($a)
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
