require 'spec_helper_acceptance'

describe 'quoted paths' do
  before(:all) do
    shell('rm -rf "/tmp/concat test" /var/lib/puppet/concat')
    shell('mkdir -p "/tmp/concat test"')
  end

  context 'path with blanks' do
    pp = <<-EOS
      concat { '/tmp/concat test/foo':
      }
      concat::fragment { '1':
        target  => '/tmp/concat test/foo',
        content => 'string1',
      }
      concat::fragment { '2':
        target  => '/tmp/concat test/foo',
        content => 'string2',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat test/foo') do
      it { should be_file }
      it { should contain "string1\nsring2" }
    end
  end
end
