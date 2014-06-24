module Puppet::Parser::Functions
  newfunction(:mysql_strip_hash, :type => :rvalue, :arity => 1, :doc => <<-EOS
TEMPORARY FUNCTION: EXPIRES 2014-03-10
When given a hash this function strips out all blank entries.
EOS
  ) do |args|

    hash = args[0]
    unless hash.is_a?(Hash)
      raise(Puppet::ParseError, 'mysql_strip_hash(): Requires hash to work with')
    end

    # Filter out all the top level blanks.
    hash.reject{|k,v| v == ''}.each do |k,v|
      if v.is_a?(Hash)
        v.reject!{|ki,vi| vi == '' }
      end
    end

  end
end
