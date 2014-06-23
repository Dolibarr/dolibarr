#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'strftime function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'gives the Century' do
      pp = <<-EOS
      $o = strftime('%C')
      notice(inline_template('strftime is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/strftime is "20"/)
      end
    end
    it 'takes a timezone argument'
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles invalid format strings'
  end
end
