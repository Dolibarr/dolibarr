require 'spec_helper_acceptance'

describe 'apache::mod::pagespeed class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  case fact('osfamily')
  when 'Debian'
    vhost_dir    = '/etc/apache2/sites-enabled'
    mod_dir      = '/etc/apache2/mods-available'
    service_name = 'apache2'
  when 'RedHat'
    vhost_dir    = '/etc/httpd/conf.d'
    mod_dir      = '/etc/httpd/conf.d'
    service_name = 'httpd'
  when 'FreeBSD'
    vhost_dir    = '/usr/local/etc/apache22/Vhosts'
    mod_dir      = '/usr/local/etc/apache22/Modules'
    service_name = 'apache22'
  end

  context "default pagespeed config" do
    it 'succeeds in puppeting pagespeed' do
      pp= <<-EOS
        if $::osfamily == 'Debian' {
          class { 'apt': }

          apt::source { 'mod-pagespeed':
            key         => '7FAC5991',
            key_server  => 'pgp.mit.edu',
            location    => 'http://dl.google.com/linux/mod-pagespeed/deb/',
            release     => 'stable',
            repos       => 'main',
            include_src => false,
            before      => Class['apache'],
          } 
        } elsif $::osfamily == 'RedHat' {
         yumrepo { 'mod-pagespeed':
          baseurl  => 'http://dl.google.com/linux/mod-pagespeed/rpm/stable/x86_64',
            enabled  => 1,
            gpgcheck => 1,
            gpgkey   => 'https://dl-ssl.google.com/linux/linux_signing_key.pub',
            before   => Class['apache'],
          }
        }

        class { 'apache':
          mpm_module => 'prefork',
        }
        class { 'apache::mod::pagespeed':
          enable_filters  => ['remove_comments'],
          disable_filters => ['extend_cache'],
          forbid_filters  => ['rewrite_javascript'],
        }
        apache::vhost { 'pagespeed.example.com':
          port    => '80',
          docroot => '/var/www/pagespeed',
        }
        host { 'pagespeed.example.com': ip => '127.0.0.1', }
        file { '/var/www/pagespeed/index.html':
          ensure  => file,
          content => "<html>\n<!-- comment -->\n<body>\n<p>Hello World!</p>\n</body>\n</html>",
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    describe file("#{mod_dir}/pagespeed.conf") do
      it { should contain "AddOutputFilterByType MOD_PAGESPEED_OUTPUT_FILTER text/html" }
      it { should contain "ModPagespeedEnableFilters remove_comments" }
      it { should contain "ModPagespeedDisableFilters extend_cache" }
      it { should contain "ModPagespeedForbidFilters rewrite_javascript" }
    end

    it 'should answer to pagespeed.example.com and include <head/> and be stripped of comments by mod_pagespeed' do
      shell("/usr/bin/curl pagespeed.example.com:80") do |r|
        r.stdout.should =~ /<head\/>/
        r.stdout.should_not =~ /<!-- comment -->/
        r.exit_code.should == 0
      end
    end
  end
end
