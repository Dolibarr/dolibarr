#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'has_ip_address function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'has_ip_address existing ipaddress' do
      pp = <<-EOS
      $a = '127.0.0.1'
      $o = has_ip_address($a)
      notice(inline_template('has_ip_address is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/has_ip_address is true/)
      end
    end
    it 'has_ip_address absent ipaddress' do
      pp = <<-EOS
      $a = '128.0.0.1'
      $o = has_ip_address($a)
      notice(inline_template('has_ip_address is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/has_ip_address is false/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings'
  end
end
