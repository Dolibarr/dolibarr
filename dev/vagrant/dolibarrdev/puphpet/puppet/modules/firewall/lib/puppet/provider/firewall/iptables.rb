require 'puppet/provider/firewall'
require 'digest/md5'

Puppet::Type.type(:firewall).provide :iptables, :parent => Puppet::Provider::Firewall do
  include Puppet::Util::Firewall

  @doc = "Iptables type provider"

  has_feature :iptables
  has_feature :connection_limiting
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
  has_feature :isfragment
  has_feature :socket
  has_feature :address_type
  has_feature :iprange
  has_feature :ipsec_dir
  has_feature :ipsec_policy
  has_feature :mask

  optional_commands({
    :iptables => 'iptables',
    :iptables_save => 'iptables-save',
  })

  defaultfor :kernel => :linux

  iptables_version = Facter.fact('iptables_version').value
  if (iptables_version and Puppet::Util::Package.versioncmp(iptables_version, '1.4.1') < 0)
    mark_flag = '--set-mark'
  else
    mark_flag = '--set-xmark'
  end

  @protocol = "IPv4"

  @resource_map = {
    :burst           => "--limit-burst",
    :connlimit_above => "-m connlimit --connlimit-above",
    :connlimit_mask  => "--connlimit-mask",
    :connmark        => "-m connmark --mark",
    :ctstate         => "-m conntrack --ctstate",
    :destination     => "-d",
    :dst_type        => "-m addrtype --dst-type",
    :dst_range       => "-m iprange --dst-range",
    :dport           => ["-m multiport --dports", "--dport"],
    :gid             => "-m owner --gid-owner",
    :icmp            => "-m icmp --icmp-type",
    :iniface         => "-i",
    :jump            => "-j",
    :limit           => "-m limit --limit",
    :log_level       => "--log-level",
    :log_prefix      => "--log-prefix",
    :name            => "-m comment --comment",
    :outiface        => "-o",
    :port            => '-m multiport --ports',
    :proto           => "-p",
    :random          => "--random",
    :rdest           => "--rdest",
    :reap            => "--reap",
    :recent          => "-m recent",
    :reject          => "--reject-with",
    :rhitcount       => "--hitcount",
    :rname           => "--name",
    :rseconds        => "--seconds",
    :rsource         => "--rsource",
    :rttl            => "--rttl",
    :set_mark        => mark_flag,
    :socket          => "-m socket",
    :source          => "-s",
    :src_type        => "-m addrtype --src-type",
    :src_range       => "-m iprange --src-range",
    :sport           => ["-m multiport --sports", "--sport"],
    :state           => "-m state --state",
    :table           => "-t",
    :tcp_flags       => "-m tcp --tcp-flags",
    :todest          => "--to-destination",
    :toports         => "--to-ports",
    :tosource        => "--to-source",
    :uid             => "-m owner --uid-owner",
    :pkttype         => "-m pkttype --pkt-type",
    :isfragment      => "-f",
    :ipsec_dir       => "-m policy --dir",
    :ipsec_policy    => "--pol",
    :mask            => '--mask',
  }

  # These are known booleans that do not take a value, but we want to munge
  # to true if they exist.
  @known_booleans = [
    :isfragment,
    :random,
    :rdest,
    :reap,
    :rsource,
    :rttl,
    :socket
  ]


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
  @resource_list = [
    :table, :source, :destination, :iniface, :outiface, :proto, :isfragment,
    :src_range, :dst_range, :tcp_flags, :gid, :uid, :sport, :dport, :port,
    :dst_type, :src_type, :socket, :pkttype, :name, :ipsec_dir, :ipsec_policy,
    :state, :ctstate, :icmp, :limit, :burst, :recent, :rseconds, :reap,
    :rhitcount, :rttl, :rname, :mask, :rsource, :rdest, :jump, :todest,
    :tosource, :toports, :random, :log_prefix, :log_level, :reject, :set_mark,
    :connlimit_above, :connlimit_mask, :connmark
  ]

  def insert
    debug 'Inserting rule %s' % resource[:name]
    iptables insert_args
  end

  def update
    debug 'Updating rule %s' % resource[:name]
    iptables update_args
  end

  def delete
    debug 'Deleting rule %s' % resource[:name]
    iptables delete_args
  end

  def exists?
    properties[:ensure] != :absent
  end

  # Flush the property hash once done.
  def flush
    debug("[flush]")
    if @property_hash.delete(:needs_change)
      notice("Properties changed - updating rule")
      update
    end
    persist_iptables(self.class.instance_variable_get(:@protocol))
    @property_hash.clear
  end

  def self.instances
    debug "[instances]"
    table = nil
    rules = []
    counter = 1

    # String#lines would be nice, but we need to support Ruby 1.8.5
    iptables_save.split("\n").each do |line|
      unless line =~ /^\#\s+|^\:\S+|^COMMIT|^FATAL/
        if line =~ /^\*/
          table = line.sub(/\*/, "")
        else
          if hash = rule_to_hash(line, table, counter)
            rules << new(hash)
            counter += 1
          end
        end
      end
    end
    rules
  end

  def self.rule_to_hash(line, table, counter)
    hash = {}
    keys = []
    values = line.dup

    ####################
    # PRE-PARSE CLUDGING
    ####################

    # --tcp-flags takes two values; we cheat by adding " around it
    # so it behaves like --comment
    values = values.sub(/--tcp-flags (\S*) (\S*)/, '--tcp-flags "\1 \2"')
    # we do a similar thing for negated address masks (source and destination).
    values = values.sub(/(-\S+) (!)\s?(\S*)/,'\1 "\2 \3"')
    # the actual rule will have the ! mark before the option.
    values = values.sub(/(!)\s*(-\S+)\s*(\S*)/, '\2 "\1 \3"')
    # The match extension for tcp & udp are optional and throws off the @resource_map.
    values = values.sub(/-m (tcp|udp) (--(s|d)port|-m multiport)/, '\2')

    # Trick the system for booleans
    @known_booleans.each do |bool|
      # append "true" because all params are expected to have values
      if bool == :isfragment then
        # -f requires special matching:
        # only replace those -f that are not followed by an l to
        # distinguish between -f and the '-f' inside of --tcp-flags.
        values = values.sub(/-f(?!l)(?=.*--comment)/, '-f true')
      else
        values = values.sub(/#{@resource_map[bool]}/, "#{@resource_map[bool]} true")
      end
    end

    ############
    # Populate parser_list with used value, in the correct order
    ############
    map_index={}
    @resource_map.each_pair do |map_k,map_v|
      [map_v].flatten.each do |v|
        ind=values.index(/\s#{v}/)
        next unless ind
        map_index[map_k]=ind
     end
    end
    # Generate parser_list based on the index of the found option
    parser_list=[]
    map_index.sort_by{|k,v| v}.each{|mapi| parser_list << mapi.first }

    ############
    # MAIN PARSE
    ############

    # Here we iterate across our values to generate an array of keys
    parser_list.reverse.each do |k|
      resource_map_key = @resource_map[k]
      [resource_map_key].flatten.each do |opt|
        if values.slice!(/\s#{opt}/)
          keys << k
          break
        end
      end
    end

    # Manually remove chain
    values.slice!('-A')
    keys << :chain

    # Here we generate the main hash
    keys.zip(values.scan(/"[^"]*"|\S+/).reverse) { |f, v| hash[f] = v.gsub(/"/, '') }

    #####################
    # POST PARSE CLUDGING
    #####################

    # Normalise all rules to CIDR notation.
    [:source, :destination].each do |prop|
      next if hash[prop].nil?
      m = hash[prop].match(/(!?)\s?(.*)/)
      neg = "! " if m[1] == "!"
      hash[prop] = "#{neg}#{Puppet::Util::IPCidr.new(m[2]).cidr}"
    end

    [:dport, :sport, :port, :state, :ctstate].each do |prop|
      hash[prop] = hash[prop].split(',') if ! hash[prop].nil?
    end

    # Convert booleans removing the previous cludge we did
    @known_booleans.each do |bool|
      if hash[bool] != nil then
        if hash[bool] != "true" then
          raise "Parser error: #{bool} was meant to be a boolean but received value: #{hash[bool]}."
        end
      end
    end

    # Our type prefers hyphens over colons for ranges so ...
    # Iterate across all ports replacing colons with hyphens so that ranges match
    # the types expectations.
    [:dport, :sport, :port].each do |prop|
      next unless hash[prop]
      hash[prop] = hash[prop].collect do |elem|
        elem.gsub(/:/,'-')
      end
    end

    # States should always be sorted. This ensures that the output from
    # iptables-save and user supplied resources is consistent.
    hash[:state]   = hash[:state].sort   unless hash[:state].nil?
    hash[:ctstate] = hash[:ctstate].sort unless hash[:ctstate].nil?

    # This forces all existing, commentless rules or rules with invalid comments to be moved 
    # to the bottom of the stack.
    # Puppet-firewall requires that all rules have comments (resource names) and match this 
    # regex and will fail if a rule in iptables does not have a comment. We get around this 
    # by appending a high level
    if ! hash[:name]
      num = 9000 + counter
      hash[:name] = "#{num} #{Digest::MD5.hexdigest(line)}"
    elsif not /^\d+[[:alpha:][:digit:][:punct:][:space:]]+$/ =~ hash[:name]
      num = 9000 + counter
      hash[:name] = "#{num} #{/([[:alpha:][:digit:][:punct:][:space:]]+)/.match(hash[:name])[1]}"
    end

    # Iptables defaults to log_level '4', so it is omitted from the output of iptables-save.
    # If the :jump value is LOG and you don't have a log-level set, we assume it to be '4'.
    if hash[:jump] == 'LOG' && ! hash[:log_level]
      hash[:log_level] = '4'
    end

    # Iptables defaults to burst '5', so it is ommitted from the output of iptables-save.
    # If the :limit value is set and you don't have a burst set, we assume it to be '5'.
    if hash[:limit] && ! hash[:burst]
      hash[:burst] = '5'
    end

    hash[:line] = line
    hash[:provider] = self.name.to_s
    hash[:table] = table
    hash[:ensure] = :present

    # Munge some vars here ...

    # Proto should equal 'all' if undefined
    hash[:proto] = "all" if !hash.include?(:proto)

    # If the jump parameter is set to one of: ACCEPT, REJECT or DROP then
    # we should set the action parameter instead.
    if ['ACCEPT','REJECT','DROP'].include?(hash[:jump]) then
      hash[:action] = hash[:jump].downcase
      hash.delete(:jump)
    end

    hash
  end

  def insert_args
    args = []
    args << ["-I", resource[:chain], insert_order]
    args << general_args
    args
  end

  def update_args
    args = []
    args << ["-R", resource[:chain], insert_order]
    args << general_args
    args
  end

  def delete_args
    # Split into arguments
    line = properties[:line].gsub(/\-A/, '-D').split(/\s(?=(?:[^"]|"[^"]*")*$)/).map{|v| v.gsub(/"/, '')}
    line.unshift("-t", properties[:table])
  end

  # This method takes the resource, and attempts to generate the command line
  # arguments for iptables.
  def general_args
    debug "Current resource: %s" % resource.class

    args = []
    resource_list = self.class.instance_variable_get('@resource_list')
    resource_map = self.class.instance_variable_get('@resource_map')
    known_booleans = self.class.instance_variable_get('@known_booleans')

    resource_list.each do |res|
      resource_value = nil
      if (resource[res]) then
        resource_value = resource[res]
        # If socket is true then do not add the value as -m socket is standalone
        if known_booleans.include?(res) then
          if resource[res] == :true then
            resource_value = nil
          else
            # If the property is not :true then we don't want to add the value
            # to the args list
            next
          end
        end
      elsif res == :jump and resource[:action] then
        # In this case, we are substituting jump for action
        resource_value = resource[:action].to_s.upcase
      else
        next
      end

      args << [resource_map[res]].flatten.first.split(' ')

      # On negations, the '!' has to be before the option (eg: "! -d 1.2.3.4")
      if resource_value.is_a?(String) and resource_value.sub!(/^!\s*/, '') then
        # we do this after adding the 'dash' argument because of ones like "-m multiport --dports", where we want it before the "--dports" but after "-m multiport".
        # so we insert before whatever the last argument is
        args.insert(-2, '!')
      end


      # For sport and dport, convert hyphens to colons since the type
      # expects hyphens for ranges of ports.
      if [:sport, :dport, :port].include?(res) then
        resource_value = resource_value.collect do |elem|
          elem.gsub(/-/, ':')
        end
      end

      # our tcp_flags takes a single string with comma lists separated
      # by space
      # --tcp-flags expects two arguments
      if res == :tcp_flags
        one, two = resource_value.split(' ')
        args << one
        args << two
      elsif resource_value.is_a?(Array)
        args << resource_value.join(',')
      elsif !resource_value.nil?
        args << resource_value
      end
    end

    args
  end

  def insert_order
    debug("[insert_order]")
    rules = []

    # Find list of current rules based on chain and table
    self.class.instances.each do |rule|
      if rule.chain == resource[:chain].to_s and rule.table == resource[:table].to_s
        rules << rule.name
      end
    end

    # No rules at all? Just bail now.
    return 1 if rules.empty?

    # Add our rule to the end of the array of known rules
    my_rule = resource[:name].to_s
    rules << my_rule

    unmanaged_rule_regex = /^9[0-9]{3}\s[a-f0-9]{32}$/
    # Find if this is a new rule or an existing rule, then find how many
    # unmanaged rules preceed it.
    if rules.length == rules.uniq.length
      # This is a new rule so find its ordered location.
      new_rule_location = rules.sort.uniq.index(my_rule)
      if new_rule_location == 0
        # The rule will be the first rule in the chain because nothing came
        # before it.
        offset_rule = rules[0]
      else
        # This rule will come after other managed rules, so find the rule
        # immediately preceeding it.
        offset_rule = rules.sort.uniq[new_rule_location - 1]
      end
    else
      # This is a pre-existing rule, so find the offset from the original
      # ordering.
      offset_rule = my_rule
    end
    # Count how many unmanaged rules are ahead of the target rule so we know
    # how much to add to the insert order
    unnamed_offset = rules[0..rules.index(offset_rule)].inject(0) do |sum,rule|
      # This regex matches the names given to unmanaged rules (a number
      # 9000-9999 followed by an MD5 hash).
      sum + (rule.match(unmanaged_rule_regex) ? 1 : 0)
    end

    # We want our rule to come before unmanaged rules if it's not a 9-rule
    if offset_rule.match(unmanaged_rule_regex) and ! my_rule.match(/^9/)
      unnamed_offset -= 1
    end

    # Insert our new or updated rule in the correct order of named rules, but
    # offset for unnamed rules.
    rules.reject{|r|r.match(unmanaged_rule_regex)}.sort.index(my_rule) + 1 + unnamed_offset
  end
end
