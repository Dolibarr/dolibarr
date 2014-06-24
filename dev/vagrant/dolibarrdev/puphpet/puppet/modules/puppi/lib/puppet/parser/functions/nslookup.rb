#
# nslookup.rb
#
# This fuction looks up the ip address of a hostname.
#
# Params:
#  * Hostname: (string) The hostname to lookup
#  * Type: (string) The DNS type to lookup. Optional. Default: 'AAAA'
#
# Returns: an array with the ip addresses that belong to this hostname
#
# Dolf Schimmel - Freeaqingme <dolf@dolfschimmel.nl>
# 
module Puppet::Parser::Functions
  newfunction(:nslookup, :type => :rvalue, :doc => <<-EOS
Lookup a hostname and return its ip addresses
    EOS
  ) do |vals|
    hostname, type = vals
    raise(ArgumentError, 'Must specify a hostname') unless hostname
    type = 'AAAA' unless type
    
    require 'ipaddr'
    
    if (ip = IPAddr.new(hostname) rescue nil)
      if (ip.ipv6? and type == 'AAAA') or (ip.ipv4? and type != 'AAAA')
        return hostname
      else
        return []
      end
    end

    typeConst = Resolv::DNS::Resource::IN.const_get "#{type.upcase}"
    out = []
    
    Resolv::DNS.open do |dns|
      dns.getresources(hostname, typeConst).collect {|r| 
        out << IPAddr::new_ntoh(r.address.address).to_s
      }
    end

    return out
  end
end
