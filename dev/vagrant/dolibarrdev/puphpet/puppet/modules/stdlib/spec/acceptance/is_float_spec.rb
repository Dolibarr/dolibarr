#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_float function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_floats arrays' do
      pp = <<-EOS
      $a = ['aaa.com','bbb','ccc']
      $o = is_float($a)
      notice(inline_template('is_float is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_float is false/)
      end
    end
    it 'is_floats true' do
      pp = <<-EOS
      $a = true
      $o = is_float($a)
      notice(inline_template('is_float is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_float is false/)
      end
    end
    it 'is_floats strings' do
      pp = <<-EOS
      $a = "3.5"
      $b = true
      $o = is_float($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_floats floats' do
      pp = <<-EOS
      $a = 3.5
      $b = true
      $o = is_float($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_floats integers' do
      pp = <<-EOS
      $a = 3
      $b = false
      $o = is_float($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_floats hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'www.com'}
      $o = is_float($a)
      notice(inline_template('is_float is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_float is false/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-arrays'
  end
end
