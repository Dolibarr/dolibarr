#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'squeeze function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'squeezes arrays' do
      pp = <<-EOS
      # Real words!
      $a = ["wallless", "laparohysterosalpingooophorectomy", "brrr", "goddessship"]
      $o = squeeze($a)
      notice(inline_template('squeeze is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/squeeze is \["wales", "laparohysterosalpingophorectomy", "br", "godeship"\]/)
      end
    end
    it 'squeezez arrays with an argument'
    it 'squeezes strings' do
      pp = <<-EOS
      $a = "wallless laparohysterosalpingooophorectomy brrr goddessship"
      $o = squeeze($a)
      notice(inline_template('squeeze is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/squeeze is "wales laparohysterosalpingophorectomy br godeship"/)
      end
    end

    it 'squeezes strings with an argument' do
      pp = <<-EOS
      $a = "countessship duchessship governessship hostessship"
      $o = squeeze($a, 's')
      notice(inline_template('squeeze is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/squeeze is "counteship ducheship governeship hosteship"/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings or arrays'
  end
end
