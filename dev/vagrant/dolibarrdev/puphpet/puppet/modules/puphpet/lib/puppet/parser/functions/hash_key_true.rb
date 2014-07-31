#
# hash_key_true.rb
#

module Puppet::Parser::Functions

  newfunction(:hash_key_true, :type => :rvalue, :doc => <<-'ENDHEREDOC') do |args|

    Returns true if the key within hash is truthy
    ENDHEREDOC

    unless args.length == 2
      raise Puppet::ParseError, ("hash_key_true(): wrong number of arguments (#{args.length}; must be 2)")
    end

    arr = args[0]
    key = args[1]

    unless arr.is_a?(Hash)
      return false
    end

    unless arr.has_key?(key)
      return false
    end

    if arr[key].nil?
      return false
    end

    if arr[key] == false
      return false
    end

    if arr[key] == 'false'
      return false
    end

    if arr[key].empty?
      return false
    end

    return true

  end
end
