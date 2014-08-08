#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_absolute_path function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    %w{
      C:/
      C:\\\\
      C:\\\\WINDOWS\\\\System32
      C:/windows/system32
      X:/foo/bar
      X:\\\\foo\\\\bar
      /var/tmp
      /var/lib/puppet
      /var/opt/../lib/puppet
    }.each do |path|
      it "validates a single argument #{path}" do
        pp = <<-EOS
        $one = '#{path}'
        validate_absolute_path($one)
        EOS

        apply_manifest(pp, :catch_failures => true)
      end
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
    it 'handles relative paths'
  end
end
