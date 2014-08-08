#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'defined_with_params function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    it 'should successfully notify' do
      pp = <<-EOS
      user { 'dan':
        ensure => present,
      }

      if defined_with_params(User[dan], {'ensure' => 'present' }) {
        notify { 'User defined with ensure=>present': }
      }
      EOS

      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stdout).to match(/Notice: User defined with ensure=>present/)
      end
    end
  end
end
