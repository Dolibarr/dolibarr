#! /usr/bin/env ruby -S rspec
require 'spec_helper_acceptance'

describe 'validate_augeas function', :unless => UNSUPPORTED_PLATFORMS.include?(fact('operatingsystem')) do
  describe 'prep' do
    it 'installs augeas for tests'
  end
  describe 'success' do
    context 'valid inputs with no 3rd argument' do
      {
        'root:x:0:0:root:/root:/bin/bash\n'                        => 'Passwd.lns',
        'proc /proc   proc    nodev,noexec,nosuid     0       0\n' => 'Fstab.lns'
      }.each do |line,lens|
        it "validates a single argument for #{lens}" do
          pp = <<-EOS
          $line = "#{line}"
          $lens = "#{lens}"
          validate_augeas($line, $lens)
          EOS

          apply_manifest(pp, :catch_failures => true)
        end
      end
    end
    context 'valid inputs with 3rd and 4th arguments' do
      it "validates a restricted value" do
        line        = 'root:x:0:0:root:/root:/bin/barsh\n'
        lens        = 'Passwd.lns'
        restriction = '$file/*[shell="/bin/barsh"]'
        pp = <<-EOS
        $line        = "#{line}"
        $lens        = "#{lens}"
        $restriction = ['#{restriction}']
        validate_augeas($line, $lens, $restriction, "my custom failure message")
        EOS

        expect(apply_manifest(pp, :expect_failures => true).stderr).to match(/my custom failure message/)
      end
    end
    context 'invalid inputs' do
      {
        'root:x:0:0:root' => 'Passwd.lns',
        '127.0.1.1'       => 'Hosts.lns'
      }.each do |line,lens|
        it "validates a single argument for #{lens}" do
          pp = <<-EOS
          $line = "#{line}"
          $lens = "#{lens}"
          validate_augeas($line, $lens)
          EOS

          apply_manifest(pp, :expect_failures => true)
        end
      end
    end
    context 'garbage inputs' do
      it 'raises an error on invalid inputs'
    end
  end
  describe 'failure' do
    it 'handles improper number of arguments'
  end
end
