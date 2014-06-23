require 'spec_helper'
describe 'apt::debian::unstable', :type => :class do
  let(:facts) { { :lsbdistid => 'Debian' } }
  it {
    should contain_apt__source("debian_unstable").with({
      "location"          => "http://debian.mirror.iweb.ca/debian/",
      "release"           => "unstable",
      "repos"             => "main contrib non-free",
      "required_packages" => "debian-keyring debian-archive-keyring",
      "key"               => "46925553",
      "key_server"        => "subkeys.pgp.net",
      "pin"               => "-10"
    })
  }
end
