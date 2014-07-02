require 'spec_helper_acceptance'
require_relative './version.rb'

describe 'apache::vhost define', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  context 'no default vhosts' do
    it 'should create no default vhosts' do
      pp = <<-EOS
        class { 'apache':
          default_vhost => false,
          default_ssl_vhost => false,
          service_ensure => stopped
        }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/15-default.conf") do
      it { should_not be_file }
    end

    describe file("#{$vhost_dir}/15-default-ssl.conf") do
      it { should_not be_file }
    end
  end

  context "default vhost without ssl" do
    it 'should create a default vhost config' do
      pp = <<-EOS
        class { 'apache': }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/15-default.conf") do
      it { should contain '<VirtualHost \*:80>' }
    end

    describe file("#{$vhost_dir}/15-default-ssl.conf") do
      it { should_not be_file }
    end
  end

  context 'default vhost with ssl' do
    it 'should create default vhost configs' do
      pp = <<-EOS
        file { '#{$run_dir}':
          ensure  => 'directory',
          recurse => true,
        }

        class { 'apache':
          default_ssl_vhost => true,
          require => File['#{$run_dir}'],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/15-default.conf") do
      it { should contain '<VirtualHost \*:80>' }
    end

    describe file("#{$vhost_dir}/15-default-ssl.conf") do
      it { should contain '<VirtualHost \*:443>' }
      it { should contain "SSLEngine on" }
    end
  end

  context 'new vhost on port 80' do
    it 'should configure an apache vhost' do
      pp = <<-EOS
        class { 'apache': }
        file { '#{$run_dir}':
          ensure  => 'directory',
          recurse => true,
        }

        apache::vhost { 'first.example.com':
          port    => '80',
          docroot => '/var/www/first',
          require => File['#{$run_dir}'],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-first.example.com.conf") do
      it { should contain '<VirtualHost \*:80>' }
      it { should contain "ServerName first.example.com" }
    end
  end

  context 'new proxy vhost on port 80' do
    it 'should configure an apache proxy vhost' do
      pp = <<-EOS
        class { 'apache': }
        apache::vhost { 'proxy.example.com':
          port    => '80',
          docroot => '/var/www/proxy',
          proxy_pass => [
            { 'path' => '/foo', 'url' => 'http://backend-foo/'},
          ],
    	  proxy_preserve_host   => true, 
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-proxy.example.com.conf") do
      it { should contain '<VirtualHost \*:80>' }
      it { should contain "ServerName proxy.example.com" }
      it { should contain "ProxyPass" }
      it { should contain "ProxyPreserveHost On" }
      it { should_not contain "<Proxy \*>" }
    end
  end

  context 'new vhost on port 80' do
    it 'should configure two apache vhosts' do
      pp = <<-EOS
        class { 'apache': }
        apache::vhost { 'first.example.com':
          port    => '80',
          docroot => '/var/www/first',
        }
        host { 'first.example.com': ip => '127.0.0.1', }
        file { '/var/www/first/index.html':
          ensure  => file,
          content => "Hello from first\\n",
        }
        apache::vhost { 'second.example.com':
          port    => '80',
          docroot => '/var/www/second',
        }
        host { 'second.example.com': ip => '127.0.0.1', }
        file { '/var/www/second/index.html':
          ensure  => file,
          content => "Hello from second\\n",
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service($service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    it 'should answer to first.example.com' do
      shell("/usr/bin/curl first.example.com:80", {:acceptable_exit_codes => 0}) do |r|
        r.stdout.should == "Hello from first\n"
      end
    end

    it 'should answer to second.example.com' do
      shell("/usr/bin/curl second.example.com:80", {:acceptable_exit_codes => 0}) do |r|
        r.stdout.should == "Hello from second\n"
      end
    end
  end

  context 'apache_directories' do
    describe 'readme example, adapted' do
      it 'should configure a vhost with Files' do
        pp = <<-EOS
          class { 'apache': }

          if versioncmp($apache::apache_version, '2.4') >= 0 {
            $_files_match_directory = { 'path' => '(\.swp|\.bak|~)$', 'provider' => 'filesmatch', 'require' => 'all denied', }
          } else {
            $_files_match_directory = { 'path' => '(\.swp|\.bak|~)$', 'provider' => 'filesmatch', 'deny' => 'from all', }
          }

          $_directories = [
            { 'path' => '/var/www/files', },
            $_files_match_directory,
          ]

          apache::vhost { 'files.example.net':
            docroot     => '/var/www/files',
            directories => $_directories,
          }
          file { '/var/www/files/index.html':
            ensure  => file,
            content => "Hello World\\n",
          }
          file { '/var/www/files/index.html.bak':
            ensure  => file,
            content => "Hello World\\n",
          }
          host { 'files.example.net': ip => '127.0.0.1', }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe service($service_name) do
        it { should be_enabled }
        it { should be_running }
      end

      it 'should answer to files.example.net' do
        shell("/usr/bin/curl -sSf files.example.net:80/index.html").stdout.should eq("Hello World\n")
        shell("/usr/bin/curl -sSf files.example.net:80/index.html.bak", {:acceptable_exit_codes => 22}).stderr.should match(/curl: \(22\) The requested URL returned error: 403/)
      end
    end

    describe 'other Directory options' do
      it 'should configure a vhost with multiple Directory sections' do
        pp = <<-EOS
          class { 'apache': }

          if versioncmp($apache::apache_version, '2.4') >= 0 {
            $_files_match_directory = { 'path' => 'private.html$', 'provider' => 'filesmatch', 'require' => 'all denied' }
          } else {
            $_files_match_directory = [ 
              { 'path' => 'private.html$', 'provider' => 'filesmatch', 'deny' => 'from all' },
              { 'path' => '/bar/bar.html', 'provider' => 'location', allow => [ 'from 127.0.0.1', ] },
            ]
          }

          $_directories = [
            { 'path' => '/var/www/files', },
            { 'path' => '/foo/', 'provider' => 'location', 'directoryindex' => 'notindex.html', },
            $_files_match_directory,
          ]

          apache::vhost { 'files.example.net':
            docroot     => '/var/www/files',
            directories => $_directories,
          }
          file { '/var/www/files/foo':
            ensure => directory,
          }
          file { '/var/www/files/foo/notindex.html':
            ensure  => file,
            content => "Hello Foo\\n",
          }
          file { '/var/www/files/private.html':
            ensure  => file,
            content => "Hello World\\n",
          }
          file { '/var/www/files/bar':
            ensure => directory,
          }
          file { '/var/www/files/bar/bar.html':
            ensure  => file,
            content => "Hello Bar\\n",
          }
          host { 'files.example.net': ip => '127.0.0.1', }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe service($service_name) do
        it { should be_enabled }
        it { should be_running }
      end

      it 'should answer to files.example.net' do
        shell("/usr/bin/curl -sSf files.example.net:80/").stdout.should eq("Hello World\n")
        shell("/usr/bin/curl -sSf files.example.net:80/foo/").stdout.should eq("Hello Foo\n")
        shell("/usr/bin/curl -sSf files.example.net:80/private.html", {:acceptable_exit_codes => 22}).stderr.should match(/curl: \(22\) The requested URL returned error: 403/)
        shell("/usr/bin/curl -sSf files.example.net:80/bar/bar.html").stdout.should eq("Hello Bar\n")
      end
    end

    describe 'SetHandler directive' do
      it 'should configure a vhost with a SetHandler directive' do
        pp = <<-EOS
          class { 'apache': }
          apache::mod { 'status': }
          host { 'files.example.net': ip => '127.0.0.1', }
          apache::vhost { 'files.example.net':
            docroot     => '/var/www/files',
            directories => [
              { path => '/var/www/files', },
              { path => '/server-status', provider => 'location', sethandler => 'server-status', },
            ],
          }
          file { '/var/www/files/index.html':
            ensure  => file,
            content => "Hello World\\n",
          }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe service($service_name) do
        it { should be_enabled }
        it { should be_running }
      end

      it 'should answer to files.example.net' do
        shell("/usr/bin/curl -sSf files.example.net:80/index.html").stdout.should eq("Hello World\n")
        shell("/usr/bin/curl -sSf files.example.net:80/server-status?auto").stdout.should match(/Scoreboard: /)
      end
    end
  end

  case fact('lsbdistcodename')
  when 'precise', 'wheezy'
    context 'vhost fallbackresouce example' do
      it 'should configure a vhost with Fallbackresource' do
        pp = <<-EOS
        class { 'apache': }
        apache::vhost { 'fallback.example.net':
          docroot         => '/var/www/fallback',
          fallbackresource => '/index.html'
        }
        file { '/var/www/fallback/index.html':
          ensure  => file,
          content => "Hello World\\n",
        }
        host { 'fallback.example.net': ip => '127.0.0.1', }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe service($service_name) do
        it { should be_enabled }
        it { should be_running }
      end

      it 'should answer to fallback.example.net' do
        shell("/usr/bin/curl fallback.example.net:80/Does/Not/Exist") do |r|
          r.stdout.should == "Hello World\n"
        end
      end

    end
  else
    # The current stable RHEL release (6.4) comes with Apache httpd 2.2.15
    # That was released March 6, 2010.
    # FallbackResource was backported to 2.2.16, and released July 25, 2010.
    # Ubuntu Lucid (10.04) comes with apache2 2.2.14, released October 3, 2009.
    # https://svn.apache.org/repos/asf/httpd/httpd/branches/2.2.x/STATUS
  end

  context 'virtual_docroot hosting separate sites' do
    it 'should configure a vhost with VirtualDocumentRoot' do
      pp = <<-EOS
        class { 'apache': }
        apache::vhost { 'virt.example.com':
          vhost_name      => '*',
          serveraliases   => '*virt.example.com',
          port            => '80',
          docroot         => '/var/www/virt',
          virtual_docroot => '/var/www/virt/%1',
        }
        host { 'virt.example.com': ip => '127.0.0.1', }
        host { 'a.virt.example.com': ip => '127.0.0.1', }
        host { 'b.virt.example.com': ip => '127.0.0.1', }
        file { [ '/var/www/virt/a', '/var/www/virt/b', ]: ensure => directory, }
        file { '/var/www/virt/a/index.html': ensure  => file, content => "Hello from a.virt\\n", }
        file { '/var/www/virt/b/index.html': ensure  => file, content => "Hello from b.virt\\n", }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service($service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    it 'should answer to a.virt.example.com' do
      shell("/usr/bin/curl a.virt.example.com:80", {:acceptable_exit_codes => 0}) do |r|
        r.stdout.should == "Hello from a.virt\n"
      end
    end

    it 'should answer to b.virt.example.com' do
      shell("/usr/bin/curl b.virt.example.com:80", {:acceptable_exit_codes => 0}) do |r|
        r.stdout.should == "Hello from b.virt\n"
      end
    end
  end

  context 'proxy_pass for alternative vhost' do
    it 'should configure a local vhost and a proxy vhost' do
      apply_manifest(%{
        class { 'apache': default_vhost => false, }
        apache::vhost { 'localhost':
          docroot => '/var/www/local',
          ip      => '127.0.0.1',
          port    => '8888',
        }
        apache::listen { '*:80': }
        apache::vhost { 'proxy.example.com':
          docroot    => '/var/www',
          port       => '80',
          add_listen => false,
          proxy_pass => {
            'path' => '/',
            'url'  => 'http://localhost:8888/subdir/',
          },
        }
        host { 'proxy.example.com': ip => '127.0.0.1', }
        file { ['/var/www/local', '/var/www/local/subdir']: ensure => directory, }
        file { '/var/www/local/subdir/index.html':
          ensure  => file,
          content => "Hello from localhost\\n",
        }
      }, :catch_failures => true)
    end

    describe service($service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    it 'should get a response from the back end' do
      shell("/usr/bin/curl --max-redirs 0 proxy.example.com:80") do |r|
        r.stdout.should == "Hello from localhost\n"
        r.exit_code.should == 0
      end
    end
  end

  describe 'ip_based' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          ip_based   => true,
          servername => 'test.server',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file($ports_file) do
      it { should be_file }
      it { should_not contain 'NameVirtualHost test.server' }
    end
  end

  describe 'add_listen' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': default_vhost => false }
        host { 'testlisten.server': ip => '127.0.0.1' }
        apache::listen { '81': }
        apache::vhost { 'testlisten.server':
          docroot    => '/tmp',
          port       => '80',
          add_listen => false,
          servername => 'testlisten.server',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file($ports_file) do
      it { should be_file }
      it { should_not contain 'Listen 80' }
      it { should contain 'Listen 81' }
    end
  end

  describe 'docroot' do
    it 'applies cleanly' do
      pp = <<-EOS
        user { 'test_owner': ensure => present, }
        group { 'test_group': ensure => present, }
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot       => '/tmp/test',
          docroot_owner => 'test_owner',
          docroot_group => 'test_group',
          docroot_mode  => '0750',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/tmp/test') do
      it { should be_directory }
      it { should be_owned_by 'test_owner' }
      it { should be_grouped_into 'test_group' }
      it { should be_mode 750 }
    end
  end

  describe 'default_vhost' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          default_vhost => true,
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file($ports_file) do
      it { should be_file }
      if fact('osfamily') == 'RedHat' and fact('operatingsystemmajrelease') == '7'
        it { should_not contain 'NameVirtualHost test.server' }
      elsif fact('operatingsystem') == 'Ubuntu' and fact('operatingsystemrelease') =~ /(14\.04|13\.10)/
        it { should_not contain 'NameVirtualHost test.server' }
      else
        it { should contain 'NameVirtualHost test.server' }
      end
    end

    describe file("#{$vhost_dir}/10-test.server.conf") do
      it { should be_file }
    end
  end

  describe 'options' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          options    => ['Indexes','FollowSymLinks', 'ExecCGI'],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'Options Indexes FollowSymLinks ExecCGI' }
    end
  end

  describe 'override' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          override   => ['All'],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'AllowOverride All' }
    end
  end

  describe 'logroot' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          logroot    => '/tmp',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain '  CustomLog "/tmp' }
    end
  end

  ['access', 'error'].each do |logtype|
    case logtype
    when 'access'
      logname = 'CustomLog'
    when 'error'
      logname = 'ErrorLog'
    end

    describe "#{logtype}_log" do
      it 'applies cleanly' do
        pp = <<-EOS
          class { 'apache': }
          host { 'test.server': ip => '127.0.0.1' }
          apache::vhost { 'test.server':
            docroot    => '/tmp',
            logroot    => '/tmp',
            #{logtype}_log => false,
          }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe file("#{$vhost_dir}/25-test.server.conf") do
        it { should be_file }
        it { should_not contain "  #{logname} \"/tmp" }
      end
    end

    describe "#{logtype}_log_pipe" do
      it 'applies cleanly' do
        pp = <<-EOS
          class { 'apache': }
          host { 'test.server': ip => '127.0.0.1' }
          apache::vhost { 'test.server':
            docroot    => '/tmp',
            logroot    => '/tmp',
            #{logtype}_log_pipe => '|/bin/sh',
          }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe file("#{$vhost_dir}/25-test.server.conf") do
        it { should be_file }
        it { should contain "  #{logname} \"|/bin/sh" }
      end
    end

    describe "#{logtype}_log_syslog" do
      it 'applies cleanly' do
        pp = <<-EOS
          class { 'apache': }
          host { 'test.server': ip => '127.0.0.1' }
          apache::vhost { 'test.server':
            docroot    => '/tmp',
            logroot    => '/tmp',
            #{logtype}_log_syslog => 'syslog',
          }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe file("#{$vhost_dir}/25-test.server.conf") do
        it { should be_file }
        it { should contain "  #{logname} \"syslog\"" }
      end
    end
  end

  describe 'access_log_format' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          logroot    => '/tmp',
          access_log_syslog => 'syslog',
          access_log_format => '%h %l',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'CustomLog "syslog" "%h %l"' }
    end
  end

  describe 'access_log_env_var' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot            => '/tmp',
          logroot            => '/tmp',
          access_log_syslog  => 'syslog',
          access_log_env_var => 'admin',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'CustomLog "syslog" combined env=admin' }
    end
  end

  describe 'aliases' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          aliases => [{ alias => '/image', path => '/ftp/pub/image' }],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'Alias /image "/ftp/pub/image"' }
    end
  end

  describe 'scriptaliases' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          scriptaliases => [{ alias => '/myscript', path  => '/usr/share/myscript', }],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'ScriptAlias /myscript "/usr/share/myscript"' }
    end
  end

  describe 'proxy' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': service_ensure => stopped, }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot    => '/tmp',
          proxy_dest => 'test2',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'ProxyPass          / test2/' }
    end
  end

  describe 'actions' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot => '/tmp',
          action  => 'php-fastcgi',
        }
      EOS
      pp = pp + "\nclass { 'apache::mod::actions': }" if fact('osfamily') == 'Debian'
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'Action php-fastcgi /cgi-bin virtual' }
    end
  end

  describe 'suphp' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': service_ensure => stopped, }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot          => '/tmp',
          suphp_addhandler => '#{$suphp_handler}',
          suphp_engine     => 'on',
          suphp_configpath => '#{$suphp_configpath}',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain "suPHP_AddHandler #{$suphp_handler}" }
      it { should contain 'suPHP_Engine on' }
      it { should contain "suPHP_ConfigPath \"#{$suphp_configpath}\"" }
    end
  end

  describe 'no_proxy_uris' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': service_ensure => stopped, }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot          => '/tmp',
          proxy_dest       => 'http://test2',
          no_proxy_uris    => [ 'http://test2/test' ],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'ProxyPass          / http://test2/' }
      it { should contain 'ProxyPass        http://test2/test !' }
    end
  end

  describe 'redirect' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot          => '/tmp',
          redirect_source  => ['/images'],
          redirect_dest    => ['http://test.server/'],
          redirect_status  => ['permanent'],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'Redirect permanent /images http://test.server/' }
    end
  end

  # Passenger isn't even in EPEL on el-5
  if default['platform'] !~ /^el-5/
    describe 'rack_base_uris' do
      if fact('osfamily') == 'RedHat'
        it 'adds epel' do
          pp = "class { 'epel': }"
          apply_manifest(pp, :catch_failures => true)
        end
      end

      it 'applies cleanly' do
        pp = <<-EOS
          class { 'apache': }
          host { 'test.server': ip => '127.0.0.1' }
          apache::vhost { 'test.server':
            docroot          => '/tmp',
            rack_base_uris  => ['/test'],
          }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe file("#{$vhost_dir}/25-test.server.conf") do
        it { should be_file }
        it { should contain 'RackBaseURI /test' }
      end
    end
  end


  describe 'request_headers' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot          => '/tmp',
          request_headers  => ['append MirrorID "mirror 12"'],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'append MirrorID "mirror 12"' }
    end
  end

  describe 'rewrite rules' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot          => '/tmp',
          rewrites => [
            { comment => 'test',
              rewrite_cond => '%{HTTP_USER_AGENT} ^Lynx/ [OR]',
              rewrite_rule => ['^index\.html$ welcome.html'],
            }
          ],
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain '#test' }
      it { should contain 'RewriteCond %{HTTP_USER_AGENT} ^Lynx/ [OR]' }
      it { should contain 'RewriteRule ^index.html$ welcome.html' }
    end
  end

  describe 'setenv/setenvif' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot  => '/tmp',
          setenv   => ['TEST /test'],
          setenvif => ['Request_URI "\.gif$" object_is_image=gif']
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'SetEnv TEST /test' }
      it { should contain 'SetEnvIf Request_URI "\.gif$" object_is_image=gif' }
    end
  end

  describe 'block' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot  => '/tmp',
          block    => 'scm',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain '<DirectoryMatch .*\.(svn|git|bzr)/.*>' }
    end
  end

  describe 'wsgi' do
    it 'import_script applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        class { 'apache::mod::wsgi': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot                     => '/tmp',
          wsgi_application_group      => '%{GLOBAL}',
          wsgi_daemon_process         => 'wsgi',
          wsgi_daemon_process_options => {processes => '2'},
          wsgi_process_group          => 'nobody',
          wsgi_script_aliases         => { '/test' => '/test1' },
	  wsgi_pass_authorization     => 'On',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    it 'import_script applies cleanly', :unless => (fact('lsbdistcodename') == 'lucid' or UNSUPPORTED_PLATFORMS.include?(fact('osfamily'))) do
      pp = <<-EOS
        class { 'apache': }
        class { 'apache::mod::wsgi': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot                     => '/tmp',
          wsgi_application_group      => '%{GLOBAL}',
          wsgi_daemon_process         => 'wsgi',
          wsgi_daemon_process_options => {processes => '2'},
          wsgi_import_script          => '/test1',
          wsgi_import_script_options  => { application-group => '%{GLOBAL}', process-group => 'wsgi' },
          wsgi_process_group          => 'nobody',
          wsgi_script_aliases         => { '/test' => '/test1' },
	  wsgi_pass_authorization     => 'On',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf"), :unless => (fact('lsbdistcodename') == 'lucid' or UNSUPPORTED_PLATFORMS.include?(fact('osfamily'))) do
      it { should be_file }
      it { should contain 'WSGIApplicationGroup %{GLOBAL}' }
      it { should contain 'WSGIDaemonProcess wsgi processes=2' }
      it { should contain 'WSGIImportScript /test1 application-group=%{GLOBAL} process-group=wsgi' }
      it { should contain 'WSGIProcessGroup nobody' }
      it { should contain 'WSGIScriptAlias /test "/test1"' }
      it { should contain 'WSGIPassAuthorization On' }
    end
  end

  describe 'custom_fragment' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot  => '/tmp',
          custom_fragment => inline_template('#weird test string'),
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain '#weird test string' }
    end
  end

  describe 'itk' do
    it 'applies cleanly' do
      pp = <<-EOS
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        apache::vhost { 'test.server':
          docroot  => '/tmp',
          itk      => { user => 'nobody', group => 'nobody' }
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'AssignUserId nobody nobody' }
    end
  end

  # So what does this work on?
  if default['platform'] !~ /^(debian-(6|7)|el-(5|6|7))/
    describe 'fastcgi' do
      it 'applies cleanly' do
        pp = <<-EOS
          class { 'apache': }
          class { 'apache::mod::fastcgi': }
          host { 'test.server': ip => '127.0.0.1' }
          apache::vhost { 'test.server':
            docroot        => '/tmp',
            fastcgi_server => 'localhost',
            fastcgi_socket => '/tmp/fast/1234',
            fastcgi_dir    => '/tmp/fast',
          }
        EOS
        apply_manifest(pp, :catch_failures => true)
      end

      describe file("#{$vhost_dir}/25-test.server.conf") do
        it { should be_file }
        it { should contain 'FastCgiExternalServer localhost -socket /tmp/fast/1234' }
        it { should contain '<Directory "/tmp/fast">' }
      end
    end
  end

  describe 'additional_includes' do
    it 'applies cleanly' do
      pp = <<-EOS
        if $::osfamily == 'RedHat' and $::selinux == 'true' {
          exec { 'set_apache_defaults':
            command => 'semanage fcontext -a -t httpd_sys_content_t "/apache_spec(/.*)?"',
            path    => '/bin:/usr/bin/:/sbin:/usr/sbin',
            require => Package[$semanage_package],
          }
          $semanage_package = $::operatingsystemmajrelease ? {
            '5'       => 'policycoreutils',
            'default' => 'policycoreutils-python',
          }

          package { $semanage_package: ensure => installed }
          exec { 'restorecon_apache':
            command => 'restorecon -Rv /apache_spec',
            path    => '/bin:/usr/bin/:/sbin:/usr/sbin',
            before  => Service['httpd'],
            require => Class['apache'],
          }
        }
        class { 'apache': }
        host { 'test.server': ip => '127.0.0.1' }
        file { '/apache_spec': ensure => directory, }
        file { '/apache_spec/include': ensure => present, content => '#additional_includes' }
        apache::vhost { 'test.server':
          docroot             => '/apache_spec',
          additional_includes => '/apache_spec/include',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{$vhost_dir}/25-test.server.conf") do
      it { should be_file }
      it { should contain 'Include "/apache_spec/include"' }
    end
  end

end
