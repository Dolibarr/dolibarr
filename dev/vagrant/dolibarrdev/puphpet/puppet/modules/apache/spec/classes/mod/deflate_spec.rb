require 'spec_helper'

# This function is called inside the OS specific contexts
def general_deflate_specs
  it { should contain_apache__mod("deflate") }

  it do
    should contain_file("deflate.conf").with_content(
      "AddOutputFilterByType DEFLATE text/html text/plain text/xml\n"\
      "AddOutputFilterByType DEFLATE text/css\n"\
      "AddOutputFilterByType DEFLATE application/x-javascript application/javascript application/ecmascript\n"\
      "AddOutputFilterByType DEFLATE application/rss+xml\n"\
      "\n"\
      "DeflateFilterNote Input instream\n"\
      "DeflateFilterNote Output outstream\n"\
      "DeflateFilterNote Ratio ratio\n"
    )
  end
end

describe 'apache::mod::deflate', :type => :class do
  let :pre_condition do
    'include apache'
  end

  context "On a Debian OS with default params" do
    let :facts do
      {
        :id                     => 'root',
        :lsbdistcodename        => 'squeeze',
        :osfamily               => 'Debian',
        :operatingsystem        => 'Debian',
        :operatingsystemrelease => '6',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        :concat_basedir         => '/dne',
      }
    end

    # Load the more generic tests for this context
    general_deflate_specs()

    it { should contain_file("deflate.conf").with({
      :ensure => 'file',
      :path   => '/etc/apache2/mods-available/deflate.conf',
    } ) }
    it { should contain_file("deflate.conf symlink").with({
      :ensure => 'link',
      :path   => '/etc/apache2/mods-enabled/deflate.conf',
    } ) }
  end

  context "on a RedHat OS with default params" do
    let :facts do
      {
        :id                     => 'root',
        :osfamily               => 'RedHat',
        :operatingsystem        => 'RedHat',
        :operatingsystemrelease => '6',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        :concat_basedir         => '/dne',
      }
    end

    # Load the more generic tests for this context
    general_deflate_specs()

    it { should contain_file("deflate.conf").with_path("/etc/httpd/conf.d/deflate.conf") }
  end

  context "On a FreeBSD OS with default params" do
    let :facts do
      {
        :id                     => 'root',
        :osfamily               => 'FreeBSD',
        :operatingsystem        => 'FreeBSD',
        :operatingsystemrelease => '9',
        :path                   => '/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin',
        :concat_basedir         => '/dne',
      }
    end

    # Load the more generic tests for this context
    general_deflate_specs()

    it { should contain_file("deflate.conf").with({
      :ensure => 'file',
      :path   => '/usr/local/etc/apache22/Modules/deflate.conf',
    } ) }
  end
end
