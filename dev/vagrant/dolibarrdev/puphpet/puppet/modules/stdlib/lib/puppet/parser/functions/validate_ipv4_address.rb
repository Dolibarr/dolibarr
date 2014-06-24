module Puppet::Parser::Functions

  newfunction(:validate_ipv4_address, :doc => <<-ENDHEREDOC
    Validate that all values passed are valid IPv4 addresses.
    Fail compilation if any value fails this check.

    The following values will pass:

    $my_ip = "1.2.3.4"
    validate_ipv4_address($my_ip)
    validate_bool("8.8.8.8", "172.16.0.1", $my_ip)

    The following values will fail, causing compilation to abort:

    $some_array = [ 1, true, false, "garbage string", "3ffe:505:2" ]
    validate_ipv4_address($some_array)

    ENDHEREDOC
  ) do |args|

    require "ipaddr"
    rescuable_exceptions = [ ArgumentError ]

    if defined?(IPAddr::InvalidAddressError)
      rescuable_exceptions << IPAddr::InvalidAddressError
    end

    unless args.length > 0 then
      raise Puppet::ParseError, ("validate_ipv4_address(): wrong number of arguments (#{args.length}; must be > 0)")
    end

    args.each do |arg|
      unless arg.is_a?(String)
        raise Puppet::ParseError, "#{arg.inspect} is not a string."
      end

      begin
        unless IPAddr.new(arg).ipv4?
          raise Puppet::ParseError, "#{arg.inspect} is not a valid IPv4 address."
        end
      rescue *rescuable_exceptions
        raise Puppet::ParseError, "#{arg.inspect} is not a valid IPv4 address."
      end
    end

  end

end
