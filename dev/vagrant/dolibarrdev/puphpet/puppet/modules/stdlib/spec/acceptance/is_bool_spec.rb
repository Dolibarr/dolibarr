#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_bool function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_bools arrays' do
      pp = <<-EOS
      $a = ['aaa','bbb','ccc']
      $b = false
      $o = is_bool($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_bools true' do
      pp = <<-EOS
      $a = true
      $b = true
      $o = is_bool($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_bools false' do
      pp = <<-EOS
      $a = false
      $b = true
      $o = is_bool($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_bools strings' do
      pp = <<-EOS
      $a = "true"
      $b = false
      $o = is_bool($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_bools hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'bbb'}
      $b = false
      $o = is_bool($a)
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
