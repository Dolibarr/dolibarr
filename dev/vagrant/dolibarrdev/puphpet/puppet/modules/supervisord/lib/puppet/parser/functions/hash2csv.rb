#
# Converts the hash to a csv string
#
#
# $hash = {
#   HOME   => '/home/user',
#   ENV1   => 'env1',
#   SECRET => 'secret'	
# }
#
# becomes:
#
# $string = "HOME='/home/user',ENV1='env1',SECRET='secret'"
#

module Puppet::Parser::Functions
  newfunction(:hash2csv, :type => :rvalue, :doc => <<-'EOS'
    Returns a csv formatted string from an hash in the form
    KEY=VALUE,KEY2=VALUE2,KEY3=VALUE3 ordered by key
    EOS
  ) do |args|

    raise(Puppet::ParseError, "hash2csv(): Wrong number of arguments " +
      "given (#{args.size} of 1)") if args.size < 1

    hash = args[0]

    unless hash.is_a?(Hash)
      raise(Puppet::ParseError, 'hash2csv(): Requires an Hash')
    end

    sorted_hash = hash.sort
    result = ''

    sorted_hash.each {|key, value|
      result += "#{key}='#{value}',"
    }

    return result.chop!

  end
end