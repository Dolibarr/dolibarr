#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'is_function_available function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'is_function_availables arrays' do
      pp = <<-EOS
      $a = ['fail','include','require']
      $o = is_function_available($a)
      notice(inline_template('is_function_available is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_function_available is false/)
      end
    end
    it 'is_function_availables true' do
      pp = <<-EOS
      $a = true
      $o = is_function_available($a)
      notice(inline_template('is_function_available is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_function_available is false/)
      end
    end
    it 'is_function_availables strings' do
      pp = <<-EOS
      $a = "fail"
      $b = true
      $o = is_function_available($a)
      if $o == $b {
        notify { 'output correct': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: output correct/)
      end
    end
    it 'is_function_availables function_availables' do
      pp = <<-EOS
      $a = "is_function_available"
      $o = is_function_available($a)
      notice(inline_template('is_function_available is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/is_function_available is true/)
      end
    end
  end
  describe 'failure' do
    it 'handles improper argument counts'
    it 'handles non-arrays'
  end
end
