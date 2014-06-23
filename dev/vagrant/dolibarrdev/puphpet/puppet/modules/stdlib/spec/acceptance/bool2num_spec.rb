#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'bool2num function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'success' do
    ['false', 'f', '0', 'n', 'no'].each do |bool|
      it 'should convert a given boolean, #{bool}, to 0' do
        pp = <<-EOS
        $input = #{bool}
        $output = bool2num($input)
        notify { $output: }
        EOS

        apply_manifest(pp, :catch_failures => true) do |r|
          expect(r.stdout).to match(/Notice: 0/)
        end
      end
    end

    ['true', 't', '1', 'y', 'yes'].each do |bool|
      it 'should convert a given boolean, #{bool}, to 1' do
        pp = <<-EOS
        $input = #{bool}
        $output = bool2num($input)
        notify { $output: }
        EOS

        apply_manifest(pp, :catch_failures => true) do |r|
          expect(r.stdout).to match(/Notice: 1/)
        end
      end
    end
  end
end
