module Puppet::Parser::Functions
  newfunction(:deep_merge, :type => :rvalue, :doc => <<-'ENDHEREDOC') do |args|
    Recursively merges two or more hashes together and returns the resulting hash.

    For example:

        $hash1 = {'one' => 1, 'two' => 2, 'three' => { 'four' => 4 } }
        $hash2 = {'two' => 'dos', 'three' => { 'five' => 5 } }
        $merged_hash = deep_merge($hash1, $hash2)
        # The resulting hash is equivalent to:
        # $merged_hash = { 'one' => 1, 'two' => 'dos', 'three' => { 'four' => 4, 'five' => 5 } }

    When there is a duplicate key that is a hash, they are recursively merged.
    When there is a duplicate key that is not a hash, the key in the rightmost hash will "win."

    ENDHEREDOC

    if args.length < 2
      raise Puppet::ParseError, ("deep_merge(): wrong number of arguments (#{args.length}; must be at least 2)")
    end

    deep_merge = Proc.new do |hash1,hash2|
      hash1.merge(hash2) do |key,old_value,new_value|
        if old_value.is_a?(Hash) && new_value.is_a?(Hash)
          deep_merge.call(old_value, new_value)
        else
          new_value
        end
      end
    end

    result = Hash.new
    args.each do |arg|
      next if arg.is_a? String and arg.empty? # empty string is synonym for puppet's undef
      # If the argument was not a hash, skip it.
      unless arg.is_a?(Hash)
        raise Puppet::ParseError, "deep_merge: unexpected argument type #{arg.class}, only expects hash arguments"
      end

      result = deep_merge.call(result, arg)
    end
    return( result )
  end
end
