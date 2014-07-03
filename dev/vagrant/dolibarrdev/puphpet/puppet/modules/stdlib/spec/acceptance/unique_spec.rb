#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'unique function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'uniques arrays' do
      pp = <<-EOS
      $a = ["wallless", "wallless", "brrr", "goddessship"]
      $o = unique($a)
      notice(inline_template('unique is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/unique is \["wallless", "brrr", "goddessship"\]/)
      end
    end
    it 'uniques strings' do
      pp = <<-EOS
      $a = "wallless laparohysterosalpingooophorectomy brrr goddessship"
      $o = unique($a)
      notice(inline_template('unique is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/unique is "wales prohytingcmbd"/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings or arrays'
  end
end
