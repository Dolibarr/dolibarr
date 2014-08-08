#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_numeric function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_numerics arrays' do
      pp = <<-EOS
      $a = ['aaa.com','bbb','ccc']
      $b = false
      $o = is_numeric($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_numerics true' do
      pp = <<-EOS
      $a = true
      $b = false
      $o = is_numeric($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_numerics strings' do
      pp = <<-EOS
      $a = "3"
      $b = true
      $o = is_numeric($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_numerics floats' do
      pp = <<-EOS
      $a = 3.5
      $b = true
      $o = is_numeric($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_numerics integers' do
      pp = <<-EOS
      $a = 3
      $b = true
      $o = is_numeric($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_numerics hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'www.com'}
      $b = false
      $o = is_numeric($a)
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
