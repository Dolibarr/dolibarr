#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'merge function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should merge two hashes' do
      pp = <<-EOS
      $a = {'one' => 1, 'two' => 2, 'three' => { 'four' => 4 } }
      $b = {'two' => 'dos', 'three' => { 'five' => 5 } }
      $o = merge($a, $b)
      notice(inline_template('merge[one]   is <%= @o["one"].inspect %>'))
      notice(inline_template('merge[two]   is <%= @o["two"].inspect %>'))
      notice(inline_template('merge[three] is <%= @o["three"].inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/merge\[one\]   is "1"/)
        expect(r.stdout).to match(/merge\[two\]   is "dos"/)
        expect(r.stdout).to match(/merge\[three\] is {"five"=>"5"}/)
      end
    end
  end
end
