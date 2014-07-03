#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_ip_address function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_ip_addresss ipv4' do
      pp = <<-EOS
      $a = '1.2.3.4'
      $b = true
      $o = is_ip_address($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_ip_addresss ipv6' do
      pp = <<-EOS
      $a = "fe80:0000:cd12:d123:e2f8:47ff:fe09:dd74"
      $b = true
      $o = is_ip_address($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_ip_addresss ipv6 compressed' do
      pp = <<-EOS
      $a = "fe00::1"
      $b = true
      $o = is_ip_address($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_ip_addresss strings' do
      pp = <<-EOS
      $a = "aoeu"
      $b = false
      $o = is_ip_address($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_ip_addresss ipv4 out of range' do
      pp = <<-EOS
      $a = '1.2.3.400'
      $b = false
      $o = is_ip_address($a)
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
