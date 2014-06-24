require 'spec_helper'
describe 'apt::debian::testing', :type => :class do
  let(:facts) { { :lsbdistid => 'Debian' } }
  it {
    should contain_apt__source("debian_testing").with({
      "location"            => "http://debian.mirror.iweb.ca/debian/",
      "release"             => "testing",
      "repos"               => "main contrib non-free",
      "required_packages"   => "debian-keyring debian-archive-keyring",
      "key"                 => "46925553",
      "key_server"          => "subkeys.pgp.net",
      "pin"                 => "-10"
    })
  }
end
