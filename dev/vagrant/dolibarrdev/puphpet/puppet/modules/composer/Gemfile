#ruby=1.9.3@puppet-composer

if ENV.key?('PUPPET_VERSION')
  puppetversion = "= #{ENV['PUPPET_VERSION']}"
else
  puppetversion = ['>= 2.7']
end

source 'https://rubygems.org'

ruby '1.9.3'

gem 'puppet', puppetversion
gem 'puppetlabs_spec_helper'
gem 'rspec-puppet', :github => 'rodjek/rspec-puppet', :ref => '03e94422fb9bbdd950d5a0bec6ead5d76e06616b'
gem 'mocha'
gem 'puppet-lint'
