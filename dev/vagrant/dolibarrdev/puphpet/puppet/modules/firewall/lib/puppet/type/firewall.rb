# See: #10295 for more details.
#
# This is a workaround for bug: #4248 whereby ruby files outside of the normal
# provider/type path do not load until pluginsync has occured on the puppetmaster
#
# In this case I'm trying the relative path first, then falling back to normal
# mechanisms. This should be fixed in future versions of puppet but it looks
# like we'll need to maintain this for some time perhaps.
$LOAD_PATH.unshift(File.join(File.dirname(__FILE__),"..",".."))
require 'puppet/util/firewall'

Puppet::Type.newtype(:firewall) do
  include Puppet::Util::Firewall

  @doc = <<-EOS
    This type provides the capability to manage firewall rules within
    puppet.

    **Autorequires:**

    If Puppet is managing the iptables or ip6tables chains specified in the
    `chain` or `jump` parameters, the firewall resource will autorequire
    those firewallchain resources.

    If Puppet is managing the iptables or iptables-persistent packages, and
    the provider is iptables or ip6tables, the firewall resource will
    autorequire those packages to ensure that any required binaries are
    installed.
  EOS

  feature :connection_limiting, "Connection limiting features."
  feature :hop_limiting, "Hop limiting features."
  feature :rate_limiting, "Rate limiting features."
  feature :recent_limiting, "The netfilter recent module"
  feature :snat, "Source NATing"
  feature :dnat, "Destination NATing"
  feature :interface_match, "Interface matching"
  feature :icmp_match, "Matching ICMP types"
  feature :owner, "Matching owners"
  feature :state_match, "Matching stateful firewall states"
  feature :reject_type, "The ability to control reject messages"
  feature :log_level, "The ability to control the log level"
  feature :log_prefix, "The ability to add prefixes to log messages"
  feature :mark, "Match or Set the netfilter mark value associated with the packet"
  feature :tcp_flags, "The ability to match on particular TCP flag settings"
  feature :pkttype, "Match a packet type"
  feature :socket, "Match open sockets"
  feature :isfragment, "Match fragments"
  feature :address_type, "The ability match on source or destination address type"
  feature :iprange, "The ability match on source or destination IP range "
  feature :ishasmorefrags, "Match a non-last fragment of a fragmented ipv6 packet - might be first"
  feature :islastfrag, "Match the last fragment of an ipv6 packet"
  feature :isfirstfrag, "Match the first fragment of a fragmented ipv6 packet"
  feature :ipsec_policy, "Match IPsec policy"
  feature :ipsec_dir, "Match IPsec policy direction"

  # provider specific features
  feature :iptables, "The provider provides iptables features."

  ensurable do
    desc <<-EOS
      Manage the state of this rule. The default action is *present*.
    EOS

    newvalue(:present) do
      provider.insert
    end

    newvalue(:absent) do
      provider.delete
    end

    defaultto :present
  end

  newparam(:name) do
    desc <<-EOS
      The canonical name of the rule. This name is also used for ordering
      so make sure you prefix the rule with a number:

          000 this runs first
          999 this runs last

      Depending on the provider, the name of the rule can be stored using
      the comment feature of the underlying firewall subsystem.
    EOS
    isnamevar

    # Keep rule names simple - they must start with a number
    newvalues(/^\d+[[:alpha:][:digit:][:punct:][:space:]]+$/)
  end

  newproperty(:action) do
    desc <<-EOS
      This is the action to perform on a match. Can be one of:

      * accept - the packet is accepted
      * reject - the packet is rejected with a suitable ICMP response
      * drop - the packet is dropped

      If you specify no value it will simply match the rule but perform no
      action unless you provide a provider specific parameter (such as *jump*).
    EOS
    newvalues(:accept, :reject, :drop)
  end

  # Generic matching properties
  newproperty(:source) do
    desc <<-EOS
      The source address. For example:

          source => '192.168.2.0/24'

      You can also negate a mask by putting ! in front. For example:

          source => '! 192.168.2.0/24'

      The source can also be an IPv6 address if your provider supports it.
    EOS

    munge do |value|
      begin
        @resource.host_to_mask(value)
      rescue Exception => e
        self.fail("host_to_ip failed for #{value}, exception #{e}")
      end
    end
  end

  # Source IP range
  newproperty(:src_range, :required_features => :iprange) do
    desc <<-EOS
      The source IP range. For example:

          src_range => '192.168.1.1-192.168.1.10'

      The source IP range is must in 'IP1-IP2' format.
    EOS

    newvalues(/^((25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)-((25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)/)
  end

  newproperty(:destination) do
    desc <<-EOS
      The destination address to match. For example:

          destination => '192.168.1.0/24'

      You can also negate a mask by putting ! in front. For example:

          destination  => '! 192.168.2.0/24'

      The destination can also be an IPv6 address if your provider supports it.
    EOS

    munge do |value|
      begin
        @resource.host_to_mask(value)
      rescue Exception => e
        self.fail("host_to_ip failed for #{value}, exception #{e}")
      end
    end
  end

  # Destination IP range
  newproperty(:dst_range, :required_features => :iprange) do
    desc <<-EOS
      The destination IP range. For example:

          dst_range => '192.168.1.1-192.168.1.10'

      The destination IP range is must in 'IP1-IP2' format.
    EOS

    newvalues(/^((25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)-((25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)\.){3}(25[0-5]|2[0-4]\d|1\d\d|[1-9]\d|\d)/)
  end

  newproperty(:sport, :array_matching => :all) do
    desc <<-EOS
      The source port to match for this filter (if the protocol supports
      ports). Will accept a single element or an array.

      For some firewall providers you can pass a range of ports in the format:

          <start_number>-<ending_number>

      For example:

          1-1024

      This would cover ports 1 to 1024.
    EOS

    munge do |value|
      @resource.string_to_port(value, :proto)
    end

    def is_to_s(value)
      should_to_s(value)
    end

    def should_to_s(value)
      value = [value] unless value.is_a?(Array)
      value.join(',')
    end
  end

  newproperty(:dport, :array_matching => :all) do
    desc <<-EOS
      The destination port to match for this filter (if the protocol supports
      ports). Will accept a single element or an array.

      For some firewall providers you can pass a range of ports in the format:

          <start_number>-<ending_number>

      For example:

          1-1024

      This would cover ports 1 to 1024.
    EOS

    munge do |value|
      @resource.string_to_port(value, :proto)
    end

    def is_to_s(value)
      should_to_s(value)
    end

    def should_to_s(value)
      value = [value] unless value.is_a?(Array)
      value.join(',')
    end
  end

  newproperty(:port, :array_matching => :all) do
    desc <<-EOS
      The destination or source port to match for this filter (if the protocol
      supports ports). Will accept a single element or an array.

      For some firewall providers you can pass a range of ports in the format:

          <start_number>-<ending_number>

      For example:

          1-1024

      This would cover ports 1 to 1024.
    EOS

    munge do |value|
      @resource.string_to_port(value, :proto)
    end

    def is_to_s(value)
      should_to_s(value)
    end

    def should_to_s(value)
      value = [value] unless value.is_a?(Array)
      value.join(',')
    end
  end

  newproperty(:dst_type, :required_features => :address_type) do
    desc <<-EOS
      The destination address type. For example:

          dst_type => 'LOCAL'

      Can be one of:

      * UNSPEC - an unspecified address
      * UNICAST - a unicast address
      * LOCAL - a local address
      * BROADCAST - a broadcast address
      * ANYCAST - an anycast packet
      * MULTICAST - a multicast address
      * BLACKHOLE - a blackhole address
      * UNREACHABLE - an unreachable address
      * PROHIBIT - a prohibited address
      * THROW - undocumented
      * NAT - undocumented
      * XRESOLVE - undocumented
    EOS

    newvalues(:UNSPEC, :UNICAST, :LOCAL, :BROADCAST, :ANYCAST, :MULTICAST,
              :BLACKHOLE, :UNREACHABLE, :PROHIBIT, :THROW, :NAT, :XRESOLVE)
  end

  newproperty(:src_type, :required_features => :address_type) do
    desc <<-EOS
      The source address type. For example:

          src_type => 'LOCAL'

      Can be one of:

      * UNSPEC - an unspecified address
      * UNICAST - a unicast address
      * LOCAL - a local address
      * BROADCAST - a broadcast address
      * ANYCAST - an anycast packet
      * MULTICAST - a multicast address
      * BLACKHOLE - a blackhole address
      * UNREACHABLE - an unreachable address
      * PROHIBIT - a prohibited address
      * THROW - undocumented
      * NAT - undocumented
      * XRESOLVE - undocumented
    EOS

    newvalues(:UNSPEC, :UNICAST, :LOCAL, :BROADCAST, :ANYCAST, :MULTICAST,
              :BLACKHOLE, :UNREACHABLE, :PROHIBIT, :THROW, :NAT, :XRESOLVE)
  end

  newproperty(:proto) do
    desc <<-EOS
      The specific protocol to match for this rule. By default this is
      *tcp*.
    EOS

    newvalues(:tcp, :udp, :icmp, :"ipv6-icmp", :esp, :ah, :vrrp, :igmp, :ipencap, :ospf, :gre, :all)
    defaultto "tcp"
  end

  # tcp-specific
  newproperty(:tcp_flags, :required_features => :tcp_flags) do
    desc <<-EOS
      Match when the TCP flags are as specified.
      Is a string with a list of comma-separated flag names for the mask,
      then a space, then a comma-separated list of flags that should be set.
      The flags are: SYN ACK FIN RST URG PSH ALL NONE
      Note that you specify them in the order that iptables --list-rules
      would list them to avoid having puppet think you changed the flags.
      Example: FIN,SYN,RST,ACK SYN matches packets with the SYN bit set and the
	       ACK,RST and FIN bits cleared.  Such packets are used to request
               TCP  connection initiation.
    EOS
  end


  # Iptables specific
  newproperty(:chain, :required_features => :iptables) do
    desc <<-EOS
      Name of the chain to use. Can be one of the built-ins:

      * INPUT
      * FORWARD
      * OUTPUT
      * PREROUTING
      * POSTROUTING

      Or you can provide a user-based chain.

      The default value is 'INPUT'.
    EOS

    defaultto "INPUT"
    newvalue(/^[a-zA-Z0-9\-_]+$/)
  end

  newproperty(:table, :required_features => :iptables) do
    desc <<-EOS
      Table to use. Can be one of:

      * nat
      * mangle
      * filter
      * raw
      * rawpost

      By default the setting is 'filter'.
    EOS

    newvalues(:nat, :mangle, :filter, :raw, :rawpost)
    defaultto "filter"
  end

  newproperty(:jump, :required_features => :iptables) do
    desc <<-EOS
      The value for the iptables --jump parameter. Normal values are:

      * QUEUE
      * RETURN
      * DNAT
      * SNAT
      * LOG
      * MASQUERADE
      * REDIRECT
      * MARK

      But any valid chain name is allowed.

      For the values ACCEPT, DROP and REJECT you must use the generic
      'action' parameter. This is to enfore the use of generic parameters where
      possible for maximum cross-platform modelling.

      If you set both 'accept' and 'jump' parameters, you will get an error as
      only one of the options should be set.
    EOS

    validate do |value|
      unless value =~ /^[a-zA-Z0-9\-_]+$/
        raise ArgumentError, <<-EOS
          Jump destination must consist of alphanumeric characters, an
          underscore or a yphen.
        EOS
      end

      if ["accept","reject","drop"].include?(value.downcase)
        raise ArgumentError, <<-EOS
          Jump destination should not be one of ACCEPT, REJECT or DROP. Use
          the action property instead.
        EOS
      end

    end
  end

  # Interface specific matching properties
  newproperty(:iniface, :required_features => :interface_match) do
    desc <<-EOS
      Input interface to filter on.
    EOS
    newvalues(/^[a-zA-Z0-9\-\._\+]+$/)
  end

  newproperty(:outiface, :required_features => :interface_match) do
    desc <<-EOS
      Output interface to filter on.
    EOS
    newvalues(/^[a-zA-Z0-9\-\._\+]+$/)
  end

  # NAT specific properties
  newproperty(:tosource, :required_features => :snat) do
    desc <<-EOS
      When using jump => "SNAT" you can specify the new source address using
      this parameter.
    EOS
  end

  newproperty(:todest, :required_features => :dnat) do
    desc <<-EOS
      When using jump => "DNAT" you can specify the new destination address
      using this paramter.
    EOS
  end

  newproperty(:toports, :required_features => :dnat) do
    desc <<-EOS
      For DNAT this is the port that will replace the destination port.
    EOS
  end

  newproperty(:random, :required_features => :dnat) do
    desc <<-EOS
      When using a jump value of "MASQUERADE", "DNAT", "REDIRECT", or "SNAT"
      this boolean will enable randomized port mapping.
    EOS

    newvalues(:true, :false)
  end

  # Reject ICMP type
  newproperty(:reject, :required_features => :reject_type) do
    desc <<-EOS
      When combined with jump => "REJECT" you can specify a different icmp
      response to be sent back to the packet sender.
    EOS
  end

  # Logging properties
  newproperty(:log_level, :required_features => :log_level) do
    desc <<-EOS
      When combined with jump => "LOG" specifies the system log level to log
      to.
    EOS

    munge do |value|
      if value.kind_of?(String)
        value = @resource.log_level_name_to_number(value)
      else
        value
      end

      if value == nil && value != ""
        self.fail("Unable to determine log level")
      end
      value
    end
  end

  newproperty(:log_prefix, :required_features => :log_prefix) do
    desc <<-EOS
      When combined with jump => "LOG" specifies the log prefix to use when
      logging.
    EOS
  end

  # ICMP matching property
  newproperty(:icmp, :required_features => :icmp_match) do
    desc <<-EOS
      When matching ICMP packets, this is the type of ICMP packet to match.

      A value of "any" is not supported. To achieve this behaviour the
      parameter should simply be omitted or undefined.
    EOS

    validate do |value|
      if value == "any"
        raise ArgumentError,
          "Value 'any' is not valid. This behaviour should be achieved " \
          "by omitting or undefining the ICMP parameter."
      end
    end

    munge do |value|
      if value.kind_of?(String)
        # ICMP codes differ between IPv4 and IPv6.
        case @resource[:provider]
        when :iptables
          protocol = 'inet'
        when :ip6tables
          protocol = 'inet6'
        else
          self.fail("cannot work out protocol family")
        end

        value = @resource.icmp_name_to_number(value, protocol)
      else
        value
      end

      if value == nil && value != ""
        self.fail("cannot work out icmp type")
      end
      value
    end
  end

  newproperty(:state, :array_matching => :all, :required_features =>
    :state_match) do

    desc <<-EOS
      Matches a packet based on its state in the firewall stateful inspection
      table. Values can be:

      * INVALID
      * ESTABLISHED
      * NEW
      * RELATED
    EOS

    newvalues(:INVALID,:ESTABLISHED,:NEW,:RELATED)

    # States should always be sorted. This normalizes the resource states to
    # keep it consistent with the sorted result from iptables-save.
    def should=(values)
      @should = super(values).sort_by {|sym| sym.to_s}
    end

    def is_to_s(value)
      should_to_s(value)
    end

    def should_to_s(value)
      value = [value] unless value.is_a?(Array)
      value.join(',')
    end
  end

  newproperty(:ctstate, :array_matching => :all, :required_features =>
    :state_match) do

    desc <<-EOS
      Matches a packet based on its state in the firewall stateful inspection
      table, using the conntrack module. Values can be:

      * INVALID
      * ESTABLISHED
      * NEW
      * RELATED
    EOS

    newvalues(:INVALID,:ESTABLISHED,:NEW,:RELATED)

    # States should always be sorted. This normalizes the resource states to
    # keep it consistent with the sorted result from iptables-save.
    def should=(values)
      @should = super(values).sort_by {|sym| sym.to_s}
    end

    def is_to_s(value)
      should_to_s(value)
    end

    def should_to_s(value)
      value = [value] unless value.is_a?(Array)
      value.join(',')
    end
  end


  # Connection mark
  newproperty(:connmark, :required_features => :mark) do
    desc <<-EOS
      Match the Netfilter mark value associated with the packet.  Accepts either of:
      mark/mask or mark.  These will be converted to hex if they are not already.
    EOS
    munge do |value|
      int_or_hex = '[a-fA-F0-9x]'
      match = value.to_s.match("(#{int_or_hex}+)(/)?(#{int_or_hex}+)?")
      mark = @resource.to_hex32(match[1])

      # Values that can't be converted to hex.
      # Or contain a trailing slash with no mask.
      if mark.nil? or (mark and match[2] and match[3].nil?)
        raise ArgumentError, "MARK value must be integer or hex between 0 and 0xffffffff"
      end

      # There should not be a mask on connmark
      unless match[3].nil?
        raise ArgumentError, "iptables does not support masks on MARK match rules"
      end
      value = mark

      value
    end
  end

  # Connection limiting properties
  newproperty(:connlimit_above, :required_features => :connection_limiting) do
    desc <<-EOS
      Connection limiting value for matched connections above n.
    EOS
    newvalue(/^\d+$/)
  end

  newproperty(:connlimit_mask, :required_features => :connection_limiting) do
    desc <<-EOS
      Connection limiting by subnet mask for matched connections.
      IPv4: 0-32
      IPv6: 0-128
    EOS
    newvalue(/^\d+$/)
  end

  # Hop limiting properties
  newproperty(:hop_limit, :required_features => :hop_limiting) do
    desc <<-EOS
      Hop limiting value for matched packets.
    EOS
    newvalue(/^\d+$/)
  end

  # Rate limiting properties
  newproperty(:limit, :required_features => :rate_limiting) do
    desc <<-EOS
      Rate limiting value for matched packets. The format is:
      rate/[/second/|/minute|/hour|/day].

      Example values are: '50/sec', '40/min', '30/hour', '10/day'."
    EOS
  end

  newproperty(:burst, :required_features => :rate_limiting) do
    desc <<-EOS
      Rate limiting burst value (per second) before limit checks apply.
    EOS
    newvalue(/^\d+$/)
  end

  newproperty(:uid, :required_features => :owner) do
    desc <<-EOS
      UID or Username owner matching rule.  Accepts a string argument
      only, as iptables does not accept multiple uid in a single
      statement.
    EOS
  end

  newproperty(:gid, :required_features => :owner) do
    desc <<-EOS
      GID or Group owner matching rule.  Accepts a string argument
      only, as iptables does not accept multiple gid in a single
      statement.
    EOS
  end

  newproperty(:set_mark, :required_features => :mark) do
    desc <<-EOS
      Set the Netfilter mark value associated with the packet.  Accepts either of:
      mark/mask or mark.  These will be converted to hex if they are not already.
    EOS

    munge do |value|
      int_or_hex = '[a-fA-F0-9x]'
      match = value.to_s.match("(#{int_or_hex}+)(/)?(#{int_or_hex}+)?")
      mark = @resource.to_hex32(match[1])

      # Values that can't be converted to hex.
      # Or contain a trailing slash with no mask.
      if mark.nil? or (mark and match[2] and match[3].nil?)
        raise ArgumentError, "MARK value must be integer or hex between 0 and 0xffffffff"
      end

      # Old iptables does not support a mask. New iptables will expect one.
      iptables_version = Facter.fact('iptables_version').value
      mask_required = (iptables_version and Puppet::Util::Package.versioncmp(iptables_version, '1.4.1') >= 0)

      if mask_required
        if match[3].nil?
          value = "#{mark}/0xffffffff"
        else
          mask = @resource.to_hex32(match[3])
          if mask.nil?
            raise ArgumentError, "MARK mask must be integer or hex between 0 and 0xffffffff"
          end
          value = "#{mark}/#{mask}"
        end
      else
        unless match[3].nil?
          raise ArgumentError, "iptables version #{iptables_version} does not support masks on MARK rules"
        end
        value = mark
      end

      value
    end
  end

  newproperty(:pkttype, :required_features => :pkttype) do
    desc <<-EOS
      Sets the packet type to match.
    EOS

    newvalues(:unicast, :broadcast, :multicast)
  end

  newproperty(:isfragment, :required_features => :isfragment) do
    desc <<-EOS
      Set to true to match tcp fragments (requires type to be set to tcp)
    EOS

    newvalues(:true, :false)
  end

  newproperty(:recent, :required_features => :recent_limiting) do
    desc <<-EOS
      Enable the recent module. Takes as an argument one of set, update,
      rcheck or remove. For example:

        # If anyone's appeared on the 'badguy' blacklist within
        # the last 60 seconds, drop their traffic, and update the timestamp.
        firewall { '100 Drop badguy traffic':
          recent   => 'update',
          rseconds => 60,
          rsource  => true,
          rname    => 'badguy',
          action   => 'DROP',
          chain    => 'FORWARD',
        }
        # No-one should be sending us traffic on eth0 from localhost
        # Blacklist them
        firewall { '101 blacklist strange traffic':
          recent      => 'set',
          rsource     => true,
          rname       => 'badguy',
          destination => '127.0.0.0/8',
          iniface     => 'eth0',
          action      => 'DROP',
          chain       => 'FORWARD',
        }
    EOS

    newvalues(:set, :update, :rcheck, :remove)
    munge do |value|
       value = "--" + value
    end
  end

  newproperty(:rdest, :required_features => :recent_limiting) do
    desc <<-EOS
      Recent module; add the destination IP address to the list.
      Must be boolean true.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:rsource, :required_features => :recent_limiting) do
    desc <<-EOS
      Recent module; add the source IP address to the list.
      Must be boolean true.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:rname, :required_features => :recent_limiting) do
    desc <<-EOS
      Recent module; The name of the list. Takes a string argument.
    EOS
  end

  newproperty(:rseconds, :required_features => :recent_limiting) do
    desc <<-EOS
      Recent module; used in conjunction with one of `recent => 'rcheck'` or
      `recent => 'update'`. When used, this will narrow the match to only
      happen when the address is in the list and was seen within the last given
      number of seconds.
    EOS
  end

  newproperty(:reap, :required_features => :recent_limiting) do
    desc <<-EOS
      Recent module; can only be used in conjunction with the `rseconds`
      attribute. When used, this will cause entries older than 'seconds' to be
      purged.  Must be boolean true.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:rhitcount, :required_features => :recent_limiting) do
    desc <<-EOS
      Recent module; used in conjunction with `recent => 'update'` or `recent
      => 'rcheck'. When used, this will narrow the match to only happen when
      the address is in the list and packets had been received greater than or
      equal to the given value.
    EOS
  end

  newproperty(:rttl, :required_features => :recent_limiting) do
    desc <<-EOS
      Recent module; may only be used in conjunction with one of `recent =>
      'rcheck'` or `recent => 'update'`. When used, this will narrow the match
      to only happen when the address is in the list and the TTL of the current
      packet matches that of the packet which hit the `recent => 'set'` rule.
      This may be useful if you have problems with people faking their source
      address in order to DoS you via this module by disallowing others access
      to your site by sending bogus packets to you.  Must be boolean true.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:socket, :required_features => :socket) do
    desc <<-EOS
      If true, matches if an open socket can be found by doing a coket lookup
      on the packet.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:ishasmorefrags, :required_features => :ishasmorefrags) do
    desc <<-EOS
      If true, matches if the packet has it's 'more fragments' bit set. ipv6.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:islastfrag, :required_features => :islastfrag) do
    desc <<-EOS
      If true, matches if the packet is the last fragment. ipv6.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:isfirstfrag, :required_features => :isfirstfrag) do
    desc <<-EOS
      If true, matches if the packet is the first fragment. 
      Sadly cannot be negated. ipv6.
    EOS

    newvalues(:true, :false)
  end

  newproperty(:ipsec_policy, :required_features => :ipsec_policy) do
	  desc <<-EOS
	  	 Sets the ipsec policy type
	  EOS

	  newvalues(:none, :ipsec)
  end

  newproperty(:ipsec_dir, :required_features => :ipsec_dir) do
	  desc <<-EOS
	  	 Sets the ipsec policy direction
	  EOS

	  newvalues(:in, :out)
  end

  newproperty(:mask, :required_features => :mask) do
    desc <<-EOS
      Sets the mask to use when `recent` is enabled.
    EOS
  end

  newparam(:line) do
    desc <<-EOS
      Read-only property for caching the rule line.
    EOS
  end

  autorequire(:firewallchain) do
    reqs = []
    protocol = nil

    case value(:provider)
    when :iptables
      protocol = "IPv4"
    when :ip6tables
      protocol = "IPv6"
    end

    unless protocol.nil?
      table = value(:table)
      [value(:chain), value(:jump)].each do |chain|
        reqs << "#{chain}:#{table}:#{protocol}" unless ( chain.nil? || (['INPUT', 'OUTPUT', 'FORWARD'].include?(chain) && table == :filter) )
      end
    end

    reqs
  end

  # Classes would be a better abstraction, pending:
  # http://projects.puppetlabs.com/issues/19001
  autorequire(:package) do
    case value(:provider)
    when :iptables, :ip6tables
      %w{iptables iptables-persistent}
    else
      []
    end
  end

  validate do
    debug("[validate]")

    # TODO: this is put here to skip validation if ensure is not set. This
    # is because there is a revalidation stage called later where the values
    # are not set correctly. I tried tracing it - but have put in this
    # workaround instead to skip. Must get to the bottom of this.
    if ! value(:ensure)
      return
    end

    # First we make sure the chains and tables are valid combinations
    if value(:table).to_s == "filter" &&
      value(:chain) =~ /PREROUTING|POSTROUTING/

      self.fail "PREROUTING and POSTROUTING cannot be used in table 'filter'"
    end

    if value(:table).to_s == "nat" && value(:chain) =~ /INPUT|FORWARD/
      self.fail "INPUT and FORWARD cannot be used in table 'nat'"
    end

    if value(:table).to_s == "raw" &&
      value(:chain) =~ /INPUT|FORWARD|POSTROUTING/

      self.fail "INPUT, FORWARD and POSTROUTING cannot be used in table raw"
    end

    # Now we analyse the individual properties to make sure they apply to
    # the correct combinations.
    if value(:iniface)
      unless value(:chain).to_s =~ /INPUT|FORWARD|PREROUTING/
        self.fail "Parameter iniface only applies to chains " \
          "INPUT,FORWARD,PREROUTING"
      end
    end

    if value(:outiface)
      unless value(:chain).to_s =~ /OUTPUT|FORWARD|POSTROUTING/
        self.fail "Parameter outiface only applies to chains " \
          "OUTPUT,FORWARD,POSTROUTING"
      end
    end

    if value(:uid)
      unless value(:chain).to_s =~ /OUTPUT|POSTROUTING/
        self.fail "Parameter uid only applies to chains " \
          "OUTPUT,POSTROUTING"
      end
    end

    if value(:gid)
      unless value(:chain).to_s =~ /OUTPUT|POSTROUTING/
        self.fail "Parameter gid only applies to chains " \
          "OUTPUT,POSTROUTING"
      end
    end

    if value(:set_mark)
      unless value(:jump).to_s  =~ /MARK/ &&
             value(:chain).to_s =~ /PREROUTING|OUTPUT/ &&
             value(:table).to_s =~ /mangle/
        self.fail "Parameter set_mark only applies to " \
          "the PREROUTING or OUTPUT chain of the mangle table and when jump => MARK"
      end
    end

    if value(:dport)
      unless value(:proto).to_s =~ /tcp|udp|sctp/
        self.fail "[%s] Parameter dport only applies to sctp, tcp and udp " \
          "protocols. Current protocol is [%s] and dport is [%s]" %
          [value(:name), should(:proto), should(:dport)]
      end
    end

    if value(:jump).to_s == "DNAT"
      unless value(:table).to_s =~ /nat/
        self.fail "Parameter jump => DNAT only applies to table => nat"
      end

      unless value(:todest)
        self.fail "Parameter jump => DNAT must have todest parameter"
      end
    end

    if value(:jump).to_s == "SNAT"
      unless value(:table).to_s =~ /nat/
        self.fail "Parameter jump => SNAT only applies to table => nat"
      end

      unless value(:tosource)
        self.fail "Parameter jump => SNAT must have tosource parameter"
      end
    end

    if value(:jump).to_s == "REDIRECT"
      unless value(:toports)
        self.fail "Parameter jump => REDIRECT missing mandatory toports " \
          "parameter"
      end
    end

    if value(:jump).to_s == "MASQUERADE"
      unless value(:table).to_s =~ /nat/
        self.fail "Parameter jump => MASQUERADE only applies to table => nat"
      end
    end

    if value(:log_prefix) || value(:log_level)
      unless value(:jump).to_s == "LOG"
        self.fail "Parameter log_prefix and log_level require jump => LOG"
      end
    end

    if value(:burst) && ! value(:limit)
      self.fail "burst makes no sense without limit"
    end

    if value(:action) && value(:jump)
      self.fail "Only one of the parameters 'action' and 'jump' can be set"
    end

    if value(:connlimit_mask) && ! value(:connlimit_above)
      self.fail "Parameter 'connlimit_mask' requires 'connlimit_above'"
    end

    if value(:mask) && ! value(:recent)
      self.fail "Mask can only be set if recent is enabled."
    end

  end
end
