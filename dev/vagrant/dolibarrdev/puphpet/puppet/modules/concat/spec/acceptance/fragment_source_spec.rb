require 'spec_helper_acceptance'

describe 'concat::fragment source' do
  context 'should read file fragments from local system' do
    before(:all) do
      shell("/bin/echo 'file1 contents' > /tmp/concat/file1")
      shell("/bin/echo 'file2 contents' > /tmp/concat/file2")
    end

    pp = <<-EOS
      concat { '/tmp/concat/foo': }

      concat::fragment { '1':
        target  => '/tmp/concat/foo',
        source  => '/tmp/concat/file1',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/foo',
        content => 'string1 contents',
      }
      concat::fragment { '3':
        target  => '/tmp/concat/foo',
        source  => '/tmp/concat/file2',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/foo') do
      it { should be_file }
      it { should contain 'file1 contents' }
      it { should contain 'string1 contents' }
      it { should contain 'file2 contents' }
    end
  end # should read file fragments from local system

  context 'should create files containing first match only.' do
    before(:all) do
      shell('rm -rf /tmp/concat /var/lib/puppet/concat')
      shell('mkdir -p /tmp/concat')
      shell("/bin/echo 'file1 contents' > /tmp/concat/file1")
      shell("/bin/echo 'file2 contents' > /tmp/concat/file2")
    end

    pp = <<-EOS
      concat { '/tmp/concat/result_file1':
        owner   => root,
        group   => root,
        mode    => '0644',
      }
      concat { '/tmp/concat/result_file2':
        owner   => root,
        group   => root,
        mode    => '0644',
      }
      concat { '/tmp/concat/result_file3':
        owner   => root,
        group   => root,
        mode    => '0644',
      }

      concat::fragment { '1':
        target  => '/tmp/concat/result_file1',
        source => [ '/tmp/concat/file1', '/tmp/concat/file2' ],
        order   => '01',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/result_file2',
        source => [ '/tmp/concat/file2', '/tmp/concat/file1' ],
        order   => '01',
      }
      concat::fragment { '3':
        target  => '/tmp/concat/result_file3',
        source => [ '/tmp/concat/file1', '/tmp/concat/file2' ],
        order   => '01',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end
    describe file('/tmp/concat/result_file1') do
      it { should be_file }
      it { should contain 'file1 contents' }
      it { should_not contain 'file2 contents' }
    end
    describe file('/tmp/concat/result_file2') do
      it { should be_file }
      it { should contain 'file2 contents' }
      it { should_not contain 'file1 contents' }
    end
    describe file('/tmp/concat/result_file3') do
      it { should be_file }
      it { should contain 'file1 contents' }
      it { should_not contain 'file2 contents' }
    end
  end

  context 'should fail if no match on source.' do
    before(:all) do
      shell('rm -rf /tmp/concat /var/lib/puppet/concat')
      shell('mkdir -p /tmp/concat')
      shell('/bin/rm -rf /tmp/concat/fail_no_source /tmp/concat/nofilehere /tmp/concat/nothereeither')
    end

    pp = <<-EOS
      concat { '/tmp/concat/fail_no_source':
        owner   => root,
        group   => root,
        mode    => '0644',
      }

      concat::fragment { '1':
        target  => '/tmp/concat/fail_no_source',
        source => [ '/tmp/concat/nofilehere', '/tmp/concat/nothereeither' ],
        order   => '01',
      }
    EOS

    it 'applies the manifest with resource failures' do
      apply_manifest(pp, :expect_failures => true)
    end
    describe file('/tmp/concat/fail_no_source') do
      #FIXME: Serverspec::Type::File doesn't support exists? for some reason. so... hack.
      it { should_not be_file }
      it { should_not be_directory }
    end
  end
end

