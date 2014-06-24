#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_domain_name function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_domain_names arrays' do
      pp = <<-EOS
      $a = ['aaa.com','bbb','ccc']
      $o = is_domain_name($a)
      notice(inline_template('is_domain_name is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_domain_name is false/)
      end
    end
    it 'is_domain_names true' do
      pp = <<-EOS
      $a = true
      $o = is_domain_name($a)
      notice(inline_template('is_domain_name is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_domain_name is false/)
      end
    end
    it 'is_domain_names false' do
      pp = <<-EOS
      $a = false
      $o = is_domain_name($a)
      notice(inline_template('is_domain_name is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_domain_name is false/)
      end
    end
    it 'is_domain_names strings with hyphens' do
      pp = <<-EOS
      $a = "3foo-bar.2bar-fuzz.com"
      $b = true
      $o = is_domain_name($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_domain_names strings beginning with hyphens' do
      pp = <<-EOS
      $a = "-bar.2bar-fuzz.com"
      $b = false
      $o = is_domain_name($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_domain_names hashes' do
      pp = <<-EOS
      $a = {'aaa'=>'www.com'}
      $o = is_domain_name($a)
      notice(inline_template('is_domain_name is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_domain_name is false/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-arrays'
  end
end
