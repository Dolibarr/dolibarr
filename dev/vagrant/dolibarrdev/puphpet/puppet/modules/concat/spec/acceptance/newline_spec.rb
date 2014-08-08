require 'spec_helper_acceptance'

describe 'concat ensure_newline parameter' do
  context '=> false' do
    pp = <<-EOS
      concat { '/tmp/concat/file':
        ensure_newline => false,
      }
      concat::fragment { '1':
        target  => '/tmp/concat/file',
        content => '1',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/file',
        content => '2',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/file') do
      it { should be_file }
      it { should contain '12' }
    end
  end

  context '=> true' do
    pp = <<-EOS
      concat { '/tmp/concat/file':
        ensure_newline => true,
      }
      concat::fragment { '1':
        target  => '/tmp/concat/file',
        content => '1',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/file',
        content => '2',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes  => true).stderr).to eq("")
      #XXX ensure_newline => true causes changes on every run because the files
      #are modified in place.
    end

    describe file('/tmp/concat/file') do
      it { should be_file }
      it { should contain "1\n2\n" }
    end
  end
end
