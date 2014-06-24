#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'pick function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'picks a default value' do
      pp = <<-EOS
      $a = undef
      $o = pick($a, 'default')
      notice(inline_template('picked is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/picked is "default"/)
      end
    end
    it 'picks the first set value' do
      pp = <<-EOS
      $a = "something"
      $b = "long"
      $o = pick($a, $b, 'default')
      notice(inline_template('picked is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/picked is "something"/)
      end
    end
  end
  describe 'failure' do
    it 'raises error with all undef values' do
      pp = <<-EOS
      $a = undef
      $b = undef
      $o = pick($a, $b)
      notice(inline_template('picked is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :expect_failures => true) do |r|
        expect(r.stderr).to match(/must receive at least one non empty value/)
      end
    end
  end
end
