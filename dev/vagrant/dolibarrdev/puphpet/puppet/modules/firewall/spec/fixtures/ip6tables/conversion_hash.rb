# These hashes allow us to iterate across a series of test data
# creating rspec examples for each parameter to ensure the input :line
# extrapolates to the desired value for the parameter in question. And
# vice-versa

# This hash is for testing a line conversion to a hash of parameters
# which will be used to create a resource.
ARGS_TO_HASH6 = {
  'source_destination_ipv6_no_cidr' => {
    :line => '-A INPUT -s 2001:db8:85a3::8a2e:370:7334 -d 2001:db8:85a3::8a2e:370:7334 -m comment --comment "000 source destination ipv6 no cidr"',
    :table => 'filter',
    :provider => 'ip6tables',
    :params => {
      :source => '2001:db8:85a3::8a2e:370:7334/128',
      :destination => '2001:db8:85a3::8a2e:370:7334/128',
    },
  },
  'source_destination_ipv6_netmask' => {
    :line => '-A INPUT -s 2001:db8:1234::/ffff:ffff:ffff:0000:0000:0000:0000:0000 -d 2001:db8:4321::/ffff:ffff:ffff:0000:0000:0000:0000:0000 -m comment --comment "000 source destination ipv6 netmask"',
    :table => 'filter',
    :provider => 'ip6tables',
    :params => {
      :source => '2001:db8:1234::/48',
      :destination => '2001:db8:4321::/48',
    },
  },
}

# This hash is for testing converting a hash to an argument line.
HASH_TO_ARGS6 = {
  'zero_prefixlen_ipv6' => {
    :params => {
      :name => '100 zero prefix length ipv6',
      :table => 'filter',
      :provider => 'ip6tables',
      :source => '::/0',
      :destination => '::/0',
    },
    :args => ['-t', :filter, '-p', :tcp, '-m', 'comment', '--comment', '100 zero prefix length ipv6'],
  },
  'source_destination_ipv4_no_cidr' => {
    :params => {
      :name => '000 source destination ipv4 no cidr',
      :table => 'filter',
      :provider => 'ip6tables',
      :source => '1.1.1.1',
      :destination => '2.2.2.2',
    },
    :args => ['-t', :filter, '-s', '1.1.1.1/32', '-d', '2.2.2.2/32', '-p', :tcp, '-m', 'comment', '--comment', '000 source destination ipv4 no cidr'],
  },
 'source_destination_ipv6_no_cidr' => {
    :params => {
      :name => '000 source destination ipv6 no cidr',
      :table => 'filter',
      :provider => 'ip6tables',
      :source => '2001:db8:1234::',
      :destination => '2001:db8:4321::',
    },
    :args => ['-t', :filter, '-s', '2001:db8:1234::/128', '-d', '2001:db8:4321::/128', '-p', :tcp, '-m', 'comment', '--comment', '000 source destination ipv6 no cidr'],
  },
 'source_destination_ipv6_netmask' => {
    :params => {
      :name => '000 source destination ipv6 netmask',
      :table => 'filter',
      :provider => 'ip6tables',
      :source => '2001:db8:1234::/ffff:ffff:ffff:0000:0000:0000:0000:0000',
      :destination => '2001:db8:4321::/ffff:ffff:ffff:0000:0000:0000:0000:0000',
    },
    :args => ['-t', :filter, '-s', '2001:db8:1234::/48', '-d', '2001:db8:4321::/48', '-p', :tcp, '-m', 'comment', '--comment', '000 source destination ipv6 netmask'],
  },
  'frag_ishasmorefrags' => {
    :params => {
      :name => "100 has more fragments",
      :ishasmorefrags => true,
      :provider => 'ip6tables',
      :table => "filter",
    },
    :args => ["-t", :filter, "-p", :tcp, "-m", "frag", "--fragid", "0", "--fragmore", "-m", "comment", "--comment", "100 has more fragments"],
  },
  'frag_islastfrag' => {
    :params => {
      :name => "100 last fragment",
      :islastfrag => true,
      :provider => 'ip6tables',
      :table => "filter",
    },
    :args => ["-t", :filter, "-p", :tcp, "-m", "frag", "--fragid", "0", "--fraglast", "-m", "comment", "--comment", "100 last fragment"],
  },
  'frag_isfirstfrags' => {
    :params => {
      :name => "100 first fragment",
      :isfirstfrag => true,
      :provider => 'ip6tables',
      :table => "filter",
    },
    :args => ["-t", :filter, "-p", :tcp, "-m", "frag", "--fragid", "0", "--fragfirst", "-m", "comment", "--comment", "100 first fragment"],
  },
  'hop_limit' => {
    :params => {
      :name => "100 hop limit",
      :hop_limit => 255,
      :provider => 'ip6tables',
      :table => "filter",
    },
    :args => ["-t", :filter, "-p", :tcp, "-m", "comment", "--comment", "100 hop limit", "-m", "hl", "--hl-eq", 255],
  },
}
