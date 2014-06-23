Puppet::Type.type(:firewall).provide :ip6tables, :parent => :iptables, :source => :iptables do
  @doc = "Ip6tables type provider"

  has_feature :iptables
  has_feature :connection_limiting
  has_feature :hop_limiting
  has_feature :rate_limiting
  has_feature :recent_limiting
  has_feature :snat
  has_feature :dnat
  has_feature :interface_match
  has_feature :icmp_match
  has_feature :owner
  has_feature :state_match
  has_feature :reject_type
  has_feature :log_level
  has_feature :log_prefix
  has_feature :mark
  has_feature :tcp_flags
  has_feature :pkttype
  has_feature :ishasmorefrags
  has_feature :islastfrag
  has_feature :isfirstfrag

  optional_commands({
    :ip6tables      => 'ip6tables',
    :ip6tables_save => 'ip6tables-save',
  })

  def initialize(*args)
    if Facter.fact('ip6tables_version').value.match /1\.3\.\d/
      raise ArgumentError, 'The ip6tables provider is not supported on version 1.3 of iptables'
    else
      super
    end
  end

  def self.iptables(*args)
    ip6tables(*args)
  end

  def self.iptables_save(*args)
    ip6tables_save(*args)
  end

  @protocol = "IPv6"

  @resource_map = {
    :burst => "--limit-burst",
    :connlimit_above => "-m connlimit --connlimit-above",
    :connlimit_mask => "--connlimit-mask",
    :connmark => "-m connmark --mark",
    :ctstate => "-m conntrack --ctstate",
    :destination => "-d",
    :dport => "-m multiport --dports",
    :gid => "-m owner --gid-owner",
    :icmp => "-m icmp6 --icmpv6-type",
    :iniface => "-i",
    :jump => "-j",
    :hop_limit => "-m hl --hl-eq",
    :limit => "-m limit --limit",
    :log_level => "--log-level",
    :log_prefix => "--log-prefix",
    :name => "-m comment --comment",
    :outiface => "-o",
    :port => '-m multiport --ports',
    :proto => "-p",
    :rdest => "--rdest",
    :reap => "--reap",
    :recent => "-m recent",
    :reject => "--reject-with",
    :rhitcount => "--hitcount",
    :rname => "--name",
    :rseconds => "--seconds",
    :rsource => "--rsource",
    :rttl => "--rttl",
    :source => "-s",
    :state => "-m state --state",
    :sport => "-m multiport --sports",
    :table => "-t",
    :todest => "--to-destination",
    :toports => "--to-ports",
    :tosource => "--to-source",
    :uid => "-m owner --uid-owner",
    :pkttype => "-m pkttype --pkt-type",
    :ishasmorefrags => "-m frag --fragid 0 --fragmore",
    :islastfrag => "-m frag --fragid 0 --fraglast",
    :isfirstfrag => "-m frag --fragid 0 --fragfirst",
  }

  # These are known booleans that do not take a value, but we want to munge
  # to true if they exist.
  @known_booleans = [:ishasmorefrags, :islastfrag, :isfirstfrag, :rsource, :rdest, :reap, :rttl]

  # Create property methods dynamically
  (@resource_map.keys << :chain << :table << :action).each do |property|
    if @known_booleans.include?(property) then
      # The boolean properties default to '' which should be read as false
      define_method "#{property}" do
        @property_hash[property] = :false if @property_hash[property] == nil
        @property_hash[property.to_sym]
      end
    else
      define_method "#{property}" do
        @property_hash[property.to_sym]
      end
    end

    if property == :chain
      define_method "#{property}=" do |value|
        if @property_hash[:chain] != value
          raise ArgumentError, "Modifying the chain for existing rules is not supported."
        end
      end
    else
      define_method "#{property}=" do |value|
        @property_hash[:needs_change] = true
      end
    end
  end

  # This is the order of resources as they appear in iptables-save output,
  # we need it to properly parse and apply rules, if the order of resource
  # changes between puppet runs, the changed rules will be re-applied again.
  # This order can be determined by going through iptables source code or just tweaking and trying manually
  # (Note: on my CentOS 6.4 ip6tables-save returns -m frag on the place
  # I put it when calling the command. So compability with manual changes
  # not provided with current parser [georg.koester])
  @resource_list = [:table, :source, :destination, :iniface, :outiface,
    :proto, :ishasmorefrags, :islastfrag, :isfirstfrag, :gid, :uid, :sport, :dport,
    :port, :pkttype, :name, :state, :ctstate, :icmp, :hop_limit, :limit, :burst,
    :recent, :rseconds, :reap, :rhitcount, :rttl, :rname, :rsource, :rdest,
    :jump, :todest, :tosource, :toports, :log_level, :log_prefix, :reject,
    :connlimit_above, :connlimit_mask, :connmark]

end
