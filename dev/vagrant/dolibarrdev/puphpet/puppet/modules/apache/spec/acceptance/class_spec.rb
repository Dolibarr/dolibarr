require 'spec_helper_acceptance'

describe 'apache class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  case fact('osfamily')
  when 'RedHat'
    package_name = 'httpd'
    service_name = 'httpd'
  when 'Debian'
    package_name = 'apache2'
    service_name = 'apache2'
  when 'FreeBSD'
    package_name = 'apache22'
    service_name = 'apache22'
  end

  context 'default parameters' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apache': }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      expect(apply_manifest(pp, :catch_failures => true).exit_code).to be_zero
    end

    describe package(package_name) do
      it { should be_installed }
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end
  end

  context 'custom site/mod dir parameters' do
    # Using puppet_apply as a helper
    it 'should work with no errors' do
      pp = <<-EOS
      if $::osfamily == 'RedHat' and $::selinux == 'true' {
        $semanage_package = $::operatingsystemmajrelease ? {
          '5'       => 'policycoreutils',
          'default' => 'policycoreutils-python',
        }

        package { $semanage_package: ensure => installed }
        exec { 'set_apache_defaults':
          command => 'semanage fcontext -a -t httpd_sys_content_t "/apache_spec(/.*)?"',
          path    => '/bin:/usr/bin/:/sbin:/usr/sbin',
          require => Package[$semanage_package],
        }
        exec { 'restorecon_apache':
          command => 'restorecon -Rv /apache_spec',
          path    => '/bin:/usr/bin/:/sbin:/usr/sbin',
          before  => Service['httpd'],
          require => Class['apache'],
        }
      }
      file { '/apache_spec': ensure => directory, }
      file { '/apache_spec/apache_custom': ensure => directory, }
      class { 'apache':
        mod_dir   => '/apache_spec/apache_custom/mods',
        vhost_dir => '/apache_spec/apache_custom/vhosts',
      }
      EOS

      # Run it twice and test for idempotency
      apply_manifest(pp, :catch_failures => true)
      apply_manifest(pp, :catch_changes => true)
    end

    describe service(service_name) do
      it { should be_enabled }
      it { should be_running }
    end
  end
end
