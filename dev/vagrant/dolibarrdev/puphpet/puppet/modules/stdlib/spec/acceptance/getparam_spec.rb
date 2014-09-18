#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'getparam function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'getparam a package' do
      pp = <<-EOS
      user { "rspec":
        ensure     => present,
        managehome => true,
      }
      $o = getparam(User['rspec'], 'managehome')
      notice(inline_template('getparam is <%= @o.inspect %>'))
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/getparam is true/)
      end
    end
  end
  describe 'failure' do
    it 'handles no arguments'
    it 'handles non strings'
  end
end
