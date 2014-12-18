source 'https://rubygems.org'

group :test do
  gem 'rake'
  gem 'puppet-lint'
  gem 'puppet-syntax'
  gem 'puppetlabs_spec_helper'
  gem 'rspec-puppet', :git => 'https://github.com/rodjek/rspec-puppet.git' , :ref => 'c44381a240ec420d4ffda7bffc55ee4d9c08d682'
  gem 'rspec', '2.14.1'
end

group :development do
  gem 'travis'
  gem 'travis-lint'
  gem 'beaker'
  gem 'beaker-rspec'
  gem 'pry'
  gem 'guard-rake'
end


if puppetversion = ENV['PUPPET_VERSION']
  gem 'puppet', puppetversion
else
  gem 'puppet', '~> 3.4.0'
end
