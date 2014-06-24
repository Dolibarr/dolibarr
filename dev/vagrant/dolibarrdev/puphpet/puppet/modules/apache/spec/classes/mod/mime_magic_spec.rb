require 'spec_helper'

# This function is called inside the OS specific contexts
def general_mime_magic_specs
  it { should contain_apache__mod("mime_magic") }
end

describe 'apache::mod::mime_magic', :type => :class do
  let :pre_condition do
    'include apache'
  end

  context "On a Debian OS with default params" do
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

    general_mime_magic_specs()

    it do
      should contain_file("mime_magic.conf").with_content(
        "MIMEMagicFile \"/etc/apache2/magic\"\n"
      )
    end

    it { should contain_file("mime_magic.conf").with({
      :ensure => 'file',
      :path   => '/etc/apache2/mods-available/mime_magic.conf',
    } ) }
    it { should contain_file("mime_magic.conf symlink").with({
      :ensure => 'link',
      :path   => '/etc/apache2/mods-enabled/mime_magic.conf',
    } ) }

    context "with magic_file => /tmp/Debian_magic" do
      let :params do
        { :magic_file => "/tmp/Debian_magic" }
      end

      it do
        should contain_file("mime_magic.conf").with_content(
          "MIMEMagicFile \"/tmp/Debian_magic\"\n"
        )
      end
    end

  end

  context "on a RedHat OS with default params" do
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

    general_mime_magic_specs()

    it do
      should contain_file("mime_magic.conf").with_content(
        "MIMEMagicFile \"/etc/httpd/conf/magic\"\n"
      )
    end

    it { should contain_file("mime_magic.conf").with_path("/etc/httpd/conf.d/mime_magic.conf") }

  end

  context "with magic_file => /tmp/magic" do
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

    let :params do
      { :magic_file => "/tmp/magic" }
    end

    it do
      should contain_file("mime_magic.conf").with_content(
        "MIMEMagicFile \"/tmp/magic\"\n"
      )
    end
  end


end
