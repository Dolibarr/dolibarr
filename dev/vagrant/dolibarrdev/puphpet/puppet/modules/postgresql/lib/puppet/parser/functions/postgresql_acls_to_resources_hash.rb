module Puppet::Parser::Functions
  newfunction(:postgresql_acls_to_resources_hash, :type => :rvalue, :doc => <<-EOS
    This internal function translates the ipv(4|6)acls format into a resource
    suitable for create_resources. It is not intended to be used outside of the
    postgresql internal classes/defined resources.

    This function accepts an array of strings that are pg_hba.conf rules. It
    will return a hash that can be fed into create_resources to create multiple
    individual pg_hba_rule resources.

    The second parameter is an identifier that will be included in the namevar
    to provide uniqueness. It must be a string.

    The third parameter is an order offset, so you can start the order at an
    arbitrary starting point.
    EOS
  ) do |args|
    func_name = "postgresql_acls_to_resources_hash()"

    raise(Puppet::ParseError, "#{func_name}: Wrong number of arguments " +
      "given (#{args.size} for 3)") if args.size != 3

    acls = args[0]
    raise(Puppet::ParseError, "#{func_name}: first argument must be an array") \
      unless acls.instance_of? Array

    id = args[1]
    raise(Puppet::ParseError, "#{func_name}: second argument must be a string") \
      unless id.instance_of? String

    offset = args[2].to_i
    raise(Puppet::ParseError, "#{func_name}: third argument must be a number") \
      unless offset.instance_of? Fixnum

    resources = {}
    acls.each do |acl|
      index = acls.index(acl)

      parts = acl.split

      raise(Puppet::ParseError, "#{func_name}: acl line #{index} does not " +
        "have enough parts") unless parts.length >= 4

      resource = {
        'type' => parts[0],
        'database' => parts[1],
        'user' => parts[2],
        'order' => format('%03d', offset + index),
      }
      if parts[0] == 'local' then
        resource['auth_method'] = parts[3]
        if parts.length > 4 then
          resource['auth_option'] = parts.last(parts.length - 4).join(" ")
        end
      else
        if parts[4] =~ /^\d/
          resource['address'] = parts[3] + ' ' + parts[4]
          resource['auth_method'] = parts[5]

          if parts.length > 6 then
            resource['auth_option'] = parts.last(parts.length - 6).join(" ")
          end
        else
          resource['address'] = parts[3]
          resource['auth_method'] = parts[4]

          if parts.length > 5 then
            resource['auth_option'] = parts.last(parts.length - 5).join(" ")
          end
        end
      end
      resources["postgresql class generated rule #{id} #{index}"] = resource
    end
    resources
  end
end
