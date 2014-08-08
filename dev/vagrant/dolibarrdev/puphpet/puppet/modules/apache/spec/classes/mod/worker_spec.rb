require 'spec_helper'

describe 'apache::mod::worker', :type => :class do
  let :pre_condition do
    'class { "apache": mpm_module => false, }'
  end
  context "on a Debian OS" do
    let :facts do
      {
        :osfamily               => 'Debian',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :lsbdistcodename        => 'squeeze',
        :operatingsystem        => 'Debian',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should_not contain_apache__mod('worker') }
    it { should contain_file("/etc/apache2/mods-available/worker.conf").with_ensure('file') }
    it { should contain_file("/etc/apache2/mods-enabled/worker.conf").with_ensure('link') }

    context "with Apache version < 2.4" do
      let :params do
        {
          :apache_version => '2.2',
        }
      end

      it { should_not contain_file("/etc/apache2/mods-available/worker.load") }
      it { should_not contain_file("/etc/apache2/mods-enabled/worker.load") }

      it { should contain_package("apache2-mpm-worker") }
    end

    context "with Apache version >= 2.4" do
      let :params do
        {
          :apache_version => '2.4',
        }
      end

      it { should contain_file("/etc/apache2/mods-available/worker.load").with({
        'ensure'  => 'file',
        'content' => "LoadModule mpm_worker_module /usr/lib/apache2/modules/mod_mpm_worker.so\n"
        })
      }
      it { should contain_file("/etc/apache2/mods-enabled/worker.load").with_ensure('link') }
    end
  end
  context "on a RedHat OS" do
    let :facts do
      {
        :osfamily               => 'RedHat',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'RedHat',
        :id                     => 'root',
        :kernel                 => 'Linux',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should_not contain_apache__mod('worker') }
    it { should contain_file("/etc/httpd/conf.d/worker.conf").with_ensure('file') }

    context "with Apache version < 2.4" do
      let :params do
        {
          :apache_version => '2.2',
        }
      end

      it { should contain_file_line("/etc/sysconfig/httpd worker enable").with({
        'require' => 'Package[httpd]',
        })
      }
    end

    context "with Apache version >= 2.4" do
      let :params do
        {
          :apache_version => '2.4',
        }
      end

      it { should_not contain_apache__mod('event') }

      it { should contain_file("/etc/httpd/conf.d/worker.load").with({
        'ensure'  => 'file',
        'content' => "LoadModule mpm_worker_module modules/mod_mpm_worker.so\n",
        })
      }
    end
  end
  context "on a FreeBSD OS" do
    let :facts do
      {
        :osfamily               => 'FreeBSD',
        :operatingsystemrelease => '9',
        :concat_basedir         => '/dne',
        :operatingsystem        => 'FreeBSD',
        :id                     => 'root',
        :kernel                 => 'FreeBSD',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
      }
    end
    it { should contain_class("apache::params") }
    it { should_not contain_apache__mod('worker') }
    it { should contain_file("/usr/local/etc/apache22/Modules/worker.conf").with_ensure('file') }
  end

  # Template config doesn't vary by distro
  context "on all distros" do
    let :facts do
      {
        :osfamily               => 'RedHat',
        :operatingsystemrelease => '6',
        :concat_basedir         => '/dne',
      }
    end

    context 'defaults' do
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^<IfModule mpm_worker_module>$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+ServerLimit\s+25$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+StartServers\s+2$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MaxClients\s+150$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MinSpareThreads\s+25$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MaxSpareThreads\s+75$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+ThreadsPerChild\s+25$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MaxRequestsPerChild\s+0$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+ThreadLimit\s+64$/) }
    end

    context 'setting params' do
      let :params do
        {
          :serverlimit          => 10,
          :startservers         => 11,
          :maxclients           => 12,
          :minsparethreads      => 13,
          :maxsparethreads      => 14,
          :threadsperchild      => 15,
          :maxrequestsperchild  => 16,
          :threadlimit          => 17
        }
      end
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^<IfModule mpm_worker_module>$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+ServerLimit\s+10$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+StartServers\s+11$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MaxClients\s+12$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MinSpareThreads\s+13$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MaxSpareThreads\s+14$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+ThreadsPerChild\s+15$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+MaxRequestsPerChild\s+16$/) }
      it { should contain_file('/etc/httpd/conf.d/worker.conf').with(:content => /^\s+ThreadLimit\s+17$/) }
    end
  end
end
