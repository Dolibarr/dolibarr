require 'spec_helper_acceptance'

describe 'apache::mod::php class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
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

  context "default php config" do
    it 'succeeds in puppeting php' do
      pp= <<-EOS
        class { 'apache':
          mpm_module => 'prefork',
        }
        class { 'apache::mod::php': }
        apache::vhost { 'php.example.com':
          port    => '80',
          docroot => '/var/www/php',
        }
        host { 'php.example.com': ip => '127.0.0.1', }
        file { '/var/www/php/index.php':
          ensure  => file,
          content => "<?php phpinfo(); ?>\\n",
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    describe file("#{mod_dir}/php5.conf") do
      it { should contain "DirectoryIndex index.php" }
    end

    it 'should answer to php.example.com' do
      shell("/usr/bin/curl php.example.com:80") do |r|
        r.stdout.should =~ /PHP Version/
        r.exit_code.should == 0
      end
    end
  end

  context "custom extensions, php_admin_flag, and php_admin_value" do
    it 'succeeds in puppeting php' do
      pp= <<-EOS
        class { 'apache':
          mpm_module => 'prefork',
        }
        class { 'apache::mod::php':
          extensions => ['.php','.php5'],
        }
        apache::vhost { 'php.example.com':
          port             => '80',
          docroot          => '/var/www/php',
          php_admin_values => { 'open_basedir' => '/var/www/php/:/usr/share/pear/', },
          php_admin_flags  => { 'engine' => 'on', },
        }
        host { 'php.example.com': ip => '127.0.0.1', }
        file { '/var/www/php/index.php5':
          ensure  => file,
          content => "<?php phpinfo(); ?>\\n",
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end

    describe file("#{vhost_dir}/25-php.example.com.conf") do
      it { should contain "  php_admin_flag engine on" }
      it { should contain "  php_admin_value open_basedir /var/www/php/:/usr/share/pear/" }
    end

    it 'should answer to php.example.com' do
      shell("/usr/bin/curl php.example.com:80") do |r|
        r.stdout.should =~ /\/usr\/share\/pear\//
        r.exit_code.should == 0
      end
    end
  end

  context "provide custom config file" do
    it 'succeeds in puppeting php' do
      pp= <<-EOS
        class {'apache':
          mpm_module => 'prefork',
        }
        class {'apache::mod::php':
          content => '# somecontent',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{mod_dir}/php5.conf") do
      it { should contain "# somecontent" }
    end
  end

  context "provide content and template config file" do
    it 'succeeds in puppeting php' do
      pp= <<-EOS
        class {'apache':
          mpm_module => 'prefork',
        }
        class {'apache::mod::php':
          content  => '# somecontent',
          template => 'apache/mod/php5.conf.erb',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{mod_dir}/php5.conf") do
      it { should contain "# somecontent" }
    end
  end

  context "provide source has priority over content" do
    it 'succeeds in puppeting php' do
      pp= <<-EOS
        class {'apache':
          mpm_module => 'prefork',
        }
        class {'apache::mod::php':
          content => '# somecontent',
          source  => 'puppet:///modules/apache/spec',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{mod_dir}/php5.conf") do
      it { should contain "# This is a file only for spec testing" }
    end
  end

  context "provide source has priority over template" do
    it 'succeeds in puppeting php' do
      pp= <<-EOS
        class {'apache':
          mpm_module => 'prefork',
        }
        class {'apache::mod::php':
          template => 'apache/mod/php5.conf.erb',
          source   => 'puppet:///modules/apache/spec',
        }
      EOS
      apply_manifest(pp, :catch_failures => true)
    end

    describe file("#{mod_dir}/php5.conf") do
      it { should contain "# This is a file only for spec testing" }
    end
  end

end
