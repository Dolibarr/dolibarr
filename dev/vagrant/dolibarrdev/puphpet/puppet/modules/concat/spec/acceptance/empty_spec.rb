require 'spec_helper_acceptance'

describe 'concat force empty parameter' do
  context 'should run successfully' do
    pp = <<-EOS
      concat { '/tmp/concat/file':
        owner => root,
        group => root,
        mode  => '0644',
        force => true,
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/file') do
      it { should be_file }
      it { should_not contain '1\n2' }
    end
  end
end
