source 'https://rubygems.org'

group :development, :test do
  gem 'rake', :require => false
  gem 'rspec-puppet', :require => false
  gem 'puppetlabs_spec_helper', :require => false
  gem 'puppet-lint', :require => false
  gem 'rspec-system-puppet', '~>2.0.0'
end

if puppetversion = ENV['PUPPET_GEM_VERSION']
  gem 'puppet', puppetversion, :require => false
else
  gem 'puppet', :require => false
end