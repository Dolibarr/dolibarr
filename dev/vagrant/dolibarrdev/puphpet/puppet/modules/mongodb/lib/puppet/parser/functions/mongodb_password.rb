require 'digest/md5'

module Puppet::Parser::Functions
  newfunction(:mongodb_password, :type => :rvalue, :doc => <<-EOS
    Returns the mongodb password hash from the clear text password.
    EOS
  ) do |args|

    raise(Puppet::ParseError, 'mongodb_password(): Wrong number of arguments ' +
      "given (#{args.size} for 2)") if args.size != 2

    Digest::MD5.hexdigest("#{args[0]}:mongo:#{args[1]}")
  end
end
