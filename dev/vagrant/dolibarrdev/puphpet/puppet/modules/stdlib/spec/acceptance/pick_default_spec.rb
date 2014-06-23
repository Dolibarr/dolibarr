#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'pick_default function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'pick_defaults a default value' do
      pp = <<-EOS
      $a = undef
      $o = pick_default($a, 'default')
      notice(inline_template('picked is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/picked is "default"/)
      end
    end
    it 'pick_defaults with no value' do
      pp = <<-EOS
      $a = undef
      $b = undef
      $o = pick_default($a,$b)
      notice(inline_template('picked is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/picked is ""/)
      end
    end
    it 'pick_defaults the first set value' do
      pp = <<-EOS
      $a = "something"
      $b = "long"
      $o = pick_default($a, $b, 'default')
      notice(inline_template('picked is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/picked is "something"/)
      end
    end
  end
  describe 'failure' do
    it 'raises error with no values' do
      pp = <<-EOS
      $o = pick_default()
      notice(inline_template('picked is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :expect_failures => true) do |r|
        expect(r.stderr).to match(/Must receive at least one argument/)
      end
    end
  end
end
