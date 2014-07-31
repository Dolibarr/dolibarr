require 'spec_helper'

describe 'erlang', :type => :class do


  context 'on Debian' do
    let(:facts) { {
      :osfamily => 'Debian',
      :lsbdistid => 'debian',
      :lsbdistcodename => 'squeeze'
    }}

    context 'with no parameters' do
      it { should compile.with_all_deps }
      it { should contain_package('erlang-nox').with_ensure('present') }
      it { should contain_apt__source('erlang').with(
        'key_source' => 'http://packages.erlang-solutions.com/debian/erlang_solutions.asc',
        'key'        => 'D208507CA14F4FCA'
        ) }
    end

    context 'with a custom version' do
      let(:params) { {'version' => 'absent' } }
      it { should contain_package('erlang-nox').with_ensure('absent') }
    end

    context 'with a custom package name' do
      let(:params) { {'package_name' => 'not-erlang' } }
      it { should contain_package('not-erlang').with_ensure('present') }
    end

    context 'with custom repository details' do
      let(:params) { {
          'key_signature'            => '1234ABCD',
          'repos'                    => 'main',
          'remote_repo_location'     => 'http://example.com/debian',
          'remote_repo_key_location' => 'http://example.com/debian/key.asc',
        } }
      it { should contain_apt__source('erlang').with(
        'location'   => 'http://example.com/debian',
        'key_source' => 'http://example.com/debian/key.asc',
        'key'        => '1234ABCD',
        'repos'      => 'main'
        ) }
    end

  end

  context 'on RedHat 5' do
    let(:facts) { {:osfamily => 'RedHat', :operatingsystemrelease => '5.9' } }

    context "epel enabled" do
      let(:params) {{ :epel_enable => true }}
      it { should contain_class('epel') }
    end

    context "epel disabled" do
      let(:params) {{ :epel_enable => false }}
      it { should_not contain_class('epel') }
    end

    context 'with no parameters' do
      it { should contain_package('erlang').with_ensure('present') }
      it { should contain_exec('erlang-repo-download').with(
        'command' => 'curl -o /etc/yum.repos.d/epel-erlang.repo http://repos.fedorapeople.org/repos/peter/erlang/epel-erlang.repo',
        'path'    => '/bin:/sbin:/usr/bin:/usr/sbin:/usr/local/bin:/usr/local/sbin'
        )
      }
    end

    context 'with a custom repository' do
      let(:params) { {
          'local_repo_location'  => '/tmp/yum.repos.d/tmp.repo',
          'remote_repo_location' => 'http://example.com/fake.repo',
        } }

      it { should contain_exec('erlang-repo-download').with(
        'command' => 'curl -o /tmp/yum.repos.d/tmp.repo http://example.com/fake.repo'
        )
      }
    end

    context 'with a custom version' do
      let(:params) { {'version' => 'absent' } }
      it { should contain_package('erlang').with_ensure('absent') }
    end
  end

  context 'on RedHat 6' do
    let(:facts) { {:osfamily => 'RedHat', :operatingsystemrelease => '6.4' } }

    context "epel enabled" do
      let(:params) {{ :epel_enable => true }}
      it { should contain_class('epel') }
    end

    context "epel disabled" do
      let(:params) {{ :epel_enable => false }}
      it { should_not contain_class('epel') }
    end

    context 'with no parameters' do
      it { should contain_package('erlang').with_ensure('present') }
    end

    context 'with a custom version' do
      let(:params) { {'version' => 'absent' } }
      it { should contain_package('erlang').with_ensure('absent') }
    end
  end

  context 'on SUSE' do
    let(:facts) {{ :osfamily => 'SUSE', }}

    context 'with no parameters' do
      it { should contain_package('erlang').with_ensure('present') }
    end

    context 'with a custom version' do
      let(:params) { {'version' => 'absent' } }
      it { should contain_package('erlang').with_ensure('absent') }
    end
  end

  context 'on Archlinux' do
    let(:facts) {{ :osfamily => 'Archlinux', }}

    context 'with no parameters' do
      it { should contain_package('erlang').with_ensure('present') }
    end

    context 'with a custom version' do
      let(:params) { {'version' => 'absent' } }
      it { should contain_package('erlang').with_ensure('absent') }
    end
  end

end
