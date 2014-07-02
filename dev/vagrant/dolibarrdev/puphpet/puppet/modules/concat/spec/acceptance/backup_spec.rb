require 'spec_helper_acceptance'

describe 'concat backup parameter' do
  context '=> puppet' do
    before :all do
      shell('rm -rf /tmp/concat')
      shell('mkdir -p /tmp/concat')
      shell("/bin/echo 'old contents' > /tmp/concat/file")
    end

    pp = <<-EOS
      concat { '/tmp/concat/file':
        backup => 'puppet',
      }
      concat::fragment { 'new file':
        target  => '/tmp/concat/file',
        content => 'new contents',
      }
    EOS

    it 'applies the manifest twice with "Filebucketed" stdout and no stderr' do
      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stderr).to eq("")
        expect(r.stdout).to match(/Filebucketed \/tmp\/concat\/file to puppet with sum 0140c31db86293a1a1e080ce9b91305f/) # sum is for file contents of 'old contents'
      end
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/file') do
      it { should be_file }
      it { should contain 'new contents' }
    end
  end

  context '=> .backup' do
    before :all do
      shell('rm -rf /tmp/concat')
      shell('mkdir -p /tmp/concat')
      shell("/bin/echo 'old contents' > /tmp/concat/file")
    end

    pp = <<-EOS
      concat { '/tmp/concat/file':
        backup => '.backup',
      }
      concat::fragment { 'new file':
        target  => '/tmp/concat/file',
        content => 'new contents',
      }
    EOS

    # XXX Puppet doesn't mention anything about filebucketing with a given
    # extension like .backup
    it 'applies the manifest twice  no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/file') do
      it { should be_file }
      it { should contain 'new contents' }
    end
    describe file('/tmp/concat/file.backup') do
      it { should be_file }
      it { should contain 'old contents' }
    end
  end

  # XXX The backup parameter uses validate_string() and thus can't be the
  # boolean false value, but the string 'false' has the same effect in Puppet 3
  context "=> 'false'" do
    before :all do
      shell('rm -rf /tmp/concat')
      shell('mkdir -p /tmp/concat')
      shell("/bin/echo 'old contents' > /tmp/concat/file")
    end

    pp = <<-EOS
      concat { '/tmp/concat/file':
        backup => '.backup',
      }
      concat::fragment { 'new file':
        target  => '/tmp/concat/file',
        content => 'new contents',
      }
    EOS

    it 'applies the manifest twice with no "Filebucketed" stdout and no stderr' do
      apply_manifest(pp, :catch_failures => true) do |r|
        expect(r.stderr).to eq("")
        expect(r.stdout).to_not match(/Filebucketed/)
      end
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/file') do
      it { should be_file }
      it { should contain 'new contents' }
    end
  end
end
