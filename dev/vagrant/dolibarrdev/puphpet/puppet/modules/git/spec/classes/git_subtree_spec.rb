require 'spec_helper'

describe 'git::subtree' do

  context 'when git version < 1.7.0' do
    let(:facts) { { :git_version => '1.6.0' } }

    it 'should fail' do
      expect { should create_class('git::subtree') }.to raise_error(Puppet::Error, /git-subtree requires git 1.7 or later!/)
    end
  end

  context 'when git version > 1.7.0 and < 1.7.11' do
    let(:facts) { {
      :git_version   => '1.7.0',
      :git_exec_path => '/usr/lib/git-core',
    } }

    it { should create_class('git') }

    it { should create_vcsrepo('/usr/src/git-subtree').with({
      :ensure   => 'present',
      :source   => 'http://github.com/apenwarr/git-subtree.git',
      :provider => 'git',
      :revision => '2793ee6ba',
    })}

    it { should create_exec('/usr/bin/make prefix=/usr libexecdir=/usr/lib/git-core').with({
      :creates => '/usr/src/git-subtree/git-subtree',
      :cwd     => '/usr/src/git-subtree',
    })}

    it { should create_exec('/usr/bin/make prefix=/usr libexecdir=/usr/lib/git-core install').with({
      :creates => '/usr/lib/git-core/git-subtree',
      :cwd     => '/usr/src/git-subtree',
    })}

    it { should create_file('/etc/bash_completion.d/git-subtree').with({
      :ensure => 'file',
      :source => 'puppet:///modules/git/subtree/bash_completion.sh',
      :mode   => '0644',
    })}
  end 

  context 'when git version >= 1.7.11' do
    let(:facts) { {
      :git_version   => '1.7.11',
      :git_exec_path => '/usr/lib/git-core',
    } }

    it { should create_class('git') }

    it { should create_exec('/usr/bin/make prefix=/usr libexecdir=/usr/lib/git-core').with({
      :creates => '/usr/share/doc/git-core/contrib/subtree/git-subtree',
      :cwd     => '/usr/share/doc/git-core/contrib/subtree',
    })}

    it { should create_exec('/usr/bin/make prefix=/usr libexecdir=/usr/lib/git-core install').with({
      :creates => '/usr/lib/git-core/git-subtree',
      :cwd     => '/usr/share/doc/git-core/contrib/subtree',
    })}

    it { should create_file('/etc/bash_completion.d/git-subtree').with({
      :ensure => 'file',
      :source => 'puppet:///modules/git/subtree/bash_completion.sh',
      :mode   => '0644',
    })}
  end 

end
