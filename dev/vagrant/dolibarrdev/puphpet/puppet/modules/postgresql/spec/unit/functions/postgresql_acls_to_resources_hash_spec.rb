require 'spec_helper'

describe 'postgresql_acls_to_resources_hash', :type => :puppet_function do
  context 'individual transform tests' do
    it do
      input = 'local   all             postgres                                ident'
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"local",
          "database"=>"all",
          "user"=>"postgres",
          "auth_method"=>"ident",
          "order"=>"100",
        },
      }

      should run.with_params([input], 'test', 100).and_return(result)
    end

    it do
      input = 'local   all             root                                ident'
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"local",
          "database"=>"all",
          "user"=>"root",
          "auth_method"=>"ident",
          "order"=>"100",
        },
      }

      should run.with_params([input], 'test', 100).and_return(result)
    end

    it do
      input_array = [
        'local   all             all                                     ident',
      ]
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"local",
          "database"=>"all",
          "user"=>"all",
          "auth_method"=>"ident",
          "order"=>"100",
        },
      }

      should run.with_params(input_array, 'test', 100).and_return(result)
    end

    it do
      input = 'host    all             all             127.0.0.1/32            md5'
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"host",
          "database"=>"all",
          "user"=>"all",
          "address"=>"127.0.0.1/32",
          "auth_method"=>"md5",
          "order"=>"100",
        },
      }

      should run.with_params([input], 'test', 100).and_return(result)
    end

    it do
      input = 'host    all             all             0.0.0.0/0            md5'
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"host",
          "database"=>"all",
          "user"=>"all",
          "address"=>"0.0.0.0/0",
          "auth_method"=>"md5",
          "order"=>"100",
        },
      }

      should run.with_params([input], 'test', 100).and_return(result)
    end

    it do
      input = 'host    all             all             ::1/128                 md5'
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"host",
          "database"=>"all",
          "user"=>"all",
          "address"=>"::1/128",
          "auth_method"=>"md5",
          "order"=>"100",
        },
      }

      should run.with_params([input], 'test', 100).and_return(result)
    end

    it do
      input = 'host    all             all             1.1.1.1 255.255.255.0    md5'
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"host",
          "database"=>"all",
          "user"=>"all",
          "address"=>"1.1.1.1 255.255.255.0",
          "auth_method"=>"md5",
          "order"=>"100",
        },
      }

      should run.with_params([input], 'test', 100).and_return(result)
    end

    it do
      input = 'host    all             all             1.1.1.1 255.255.255.0   ldap ldapserver=ldap.example.net ldapprefix="cn=" ldapsuffix=", dc=example, dc=net"'
      result = {
        "postgresql class generated rule test 0"=>{
          "type"=>"host",
          "database"=>"all",
          "user"=>"all",
          "address"=>"1.1.1.1 255.255.255.0",
          "auth_method"=>"ldap",
          "auth_option"=>"ldapserver=ldap.example.net ldapprefix=\"cn=\" ldapsuffix=\", dc=example, dc=net\"",
          "order"=>"100",
        },
      }

      should run.with_params([input], 'test', 100).and_return(result)
    end
  end

  it 'should return an empty hash when input is empty array' do
    should run.with_params([], 'test', 100).and_return({})
  end
end
