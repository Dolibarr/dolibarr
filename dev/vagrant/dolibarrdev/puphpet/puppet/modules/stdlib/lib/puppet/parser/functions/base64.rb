module Puppet::Parser::Functions

  newfunction(:base64, :type => :rvalue, :doc => <<-'ENDHEREDOC') do |args|

    Base64 encode or decode a string based on the command and the string submitted

    Usage:

      $encodestring = base64('encode','thestring')
      $decodestring = base64('decode','dGhlc3RyaW5n')

    ENDHEREDOC

    require 'base64'

    raise Puppet::ParseError, ("base64(): Wrong number of arguments (#{args.length}; must be = 2)") unless args.length == 2

    actions = ['encode','decode']

    unless actions.include?(args[0])
      raise Puppet::ParseError, ("base64(): the first argument must be one of 'encode' or 'decode'")
    end

    unless args[1].is_a?(String)
      raise Puppet::ParseError, ("base64(): the second argument must be a string to base64")
    end

    case args[0]
      when 'encode'
        result = Base64.encode64(args[1])
      when 'decode'
        result = Base64.decode64(args[1])
    end

    return result
  end
end
