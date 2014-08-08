#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_string function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_strings arrays' do
      pp = <<-EOS
      $a = ['aaa.com','bbb','ccc']
      $b = false
      $o = is_string($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_strings true' do
      pp = <<-EOS
      $a = true
      $b = false
      $o = is_string($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_strings strings' do
      pp = <<-EOS
      $a = "aoeu"
      $o = is_string($a)
      notice(inline_template('is_string is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_string is true/)
      end
    end
    it 'is_strings number strings' do
      pp = <<-EOS
      $a = "3"
      $o = is_string($a)
      notice(inline_template('is_string is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_string is false/)
      end
    end
    it 'is_strings floats' do
      pp = <<-EOS
      $a = 3.5
      $b = false
      $o = is_string($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_strings integers' do
      pp = <<-EOS
      $a = 3
      $b = false
      $o = is_string($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_strings hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'www.com'}
      $b = false
      $o = is_string($a)
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
  end
end
