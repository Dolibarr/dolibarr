#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'size function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'single string size' do
      pp = <<-EOS
      $a = 'discombobulate'
      $o = size($a)
      notice(inline_template('size is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/size is 14/)
      end
    end
    it 'with empty string' do
      pp = <<-EOS
      $a = ''
      $o = size($a)
      notice(inline_template('size is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/size is 0/)
      end
    end
    it 'with undef' do
      pp = <<-EOS
      $a = undef
      $o = size($a)
      notice(inline_template('size is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/size is 0/)
      end
    end
    it 'strings in array' do
      pp = <<-EOS
      $a = ['discombobulate', 'moo']
      $o = size($a)
      notice(inline_template('size is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/size is 2/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings or arrays'
  end
end
