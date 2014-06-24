require 'rspec-system/spec_helper'
require 'rspec-system-puppet/helpers'
require 'rspec-system-serverspec/helpers'
include RSpecSystemPuppet::Helpers

RSpec.configure do |c|
  # Project root
  proj_root = File.expand_path(File.join(File.dirname(__FILE__), '..'))

  # Enable colour
  c.tty = true

  # This is where we 'setup' the nodes before running our tests
  c.before :suite do
    # Install puppet
    puppet_install

    # Install modules and dependencies
    puppet_module_install(:source => proj_root, :module_name => 'nginx')
    shell('puppet module install puppetlabs-apt')
    shell('puppet module install puppetlabs-stdlib')
    shell('puppet module install puppetlabs-concat')

    # Fake keys.
    # Valid self-signed SSL key with 10 year expiry.
    # Required for nginx to start when SSL enabled
    shell('echo "-----BEGIN PRIVATE KEY-----
MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAOPchwRZRF4KmU6E
g7C6Pq9zhdLiQt9owdcLZNiZS+UVRQjeDHSy3titzh5YwSoQonlnSqd0g/PJ6kNA
O3CNOMVuzAddnAaHzW1J4Rt6sZwOuidtJC4t/hFCgz5NqOMgYOOfratQx00A7ZXK
TXMgNG79lDP5L/N06Ox53sOxhy4hAgMBAAECgYEAlfktCKi0fe0d8Hb5slUzMwmn
GCECAMeTZbXDH2jucg4ozOhRbHHaiOUEmCa0pLokJiHdGhBvVQMd5Dufo7nflZzE
mpZY0lCZE7HSeK6Bcbru/8w3vm3iBQTGK+MCaDtH5nQU7m/3cOXaenOX0ZmsTzRs
QE/V84S1fuO8bBPSz20CQQD9d4LxrBByosFxRdHsTb/nnqx/rzLEf4M3MC7uydPv
fDDbSRRSYpNxonQJfU3JrOk1WPWoXY30VQCv395s57X7AkEA5iOBT+ME8/PxuUUC
ZDjg21tAdkaiCQ5kgeVTmkD1k/gTwreOV2AexWGrrcW/MLaIhpDCpQkw37y5vrYw
UyDdkwJAAU+j8sIUF7O10nMtAc7pJjaQ59wtJA0QzbFHHN8YZI285vV60G5IGvdf
KElopJlrX2ZFZwiM2m2yIjbDPMb6DwJAbNoiUbzZHOInVTA0316fzGEu7kKeZZYv
J9lmX7GV9nUCM7lKVD2ckFOQNlMwCURs8ukJh7H/MfQ8Dt5xoQAMjQJBAOWpK6k6
b0fTREZFZRGZBJcSu959YyMzhpSFA+lXkLNTWX8j1/D88H731oMSImoQNWcYx2dH
sCwOCDqu1nZ2LJ8=
-----END PRIVATE KEY-----" > /tmp/blah.key')
    shell('echo "-----BEGIN CERTIFICATE-----
MIIDRjCCAq+gAwIBAgIJAL9m0V4sHW2tMA0GCSqGSIb3DQEBBQUAMIG7MQswCQYD
VQQGEwItLTESMBAGA1UECAwJU29tZVN0YXRlMREwDwYDVQQHDAhTb21lQ2l0eTEZ
MBcGA1UECgwQU29tZU9yZ2FuaXphdGlvbjEfMB0GA1UECwwWU29tZU9yZ2FuaXph
dGlvbmFsVW5pdDEeMBwGA1UEAwwVbG9jYWxob3N0LmxvY2FsZG9tYWluMSkwJwYJ
KoZIhvcNAQkBFhpyb290QGxvY2FsaG9zdC5sb2NhbGRvbWFpbjAeFw0xMzExMzAw
NzA3NDlaFw0yMzExMjgwNzA3NDlaMIG7MQswCQYDVQQGEwItLTESMBAGA1UECAwJ
U29tZVN0YXRlMREwDwYDVQQHDAhTb21lQ2l0eTEZMBcGA1UECgwQU29tZU9yZ2Fu
aXphdGlvbjEfMB0GA1UECwwWU29tZU9yZ2FuaXphdGlvbmFsVW5pdDEeMBwGA1UE
AwwVbG9jYWxob3N0LmxvY2FsZG9tYWluMSkwJwYJKoZIhvcNAQkBFhpyb290QGxv
Y2FsaG9zdC5sb2NhbGRvbWFpbjCBnzANBgkqhkiG9w0BAQEFAAOBjQAwgYkCgYEA
49yHBFlEXgqZToSDsLo+r3OF0uJC32jB1wtk2JlL5RVFCN4MdLLe2K3OHljBKhCi
eWdKp3SD88nqQ0A7cI04xW7MB12cBofNbUnhG3qxnA66J20kLi3+EUKDPk2o4yBg
45+tq1DHTQDtlcpNcyA0bv2UM/kv83To7Hnew7GHLiECAwEAAaNQME4wHQYDVR0O
BBYEFP5Kkot/7pStLaYPtT+vngE0v6N8MB8GA1UdIwQYMBaAFP5Kkot/7pStLaYP
tT+vngE0v6N8MAwGA1UdEwQFMAMBAf8wDQYJKoZIhvcNAQEFBQADgYEAwYYQKVRN
HaHIWGMBuXApE7t4PNdYWZ5Y56tI+HT59yVoDjc1YSnuzkKlWUPibVYoLpX/ROKr
aIZ8kxsBjLvpi9KQTHi7Wl6Sw3ecoYdKy+2P8S5xOIpWjs8XVmOWf7Tq1+9KPv3z
HLw/FDCzntkdq3G4em15CdFlO9BTY4HXiHU=
-----END CERTIFICATE-----" > /tmp/blah.cert')
  end
end
