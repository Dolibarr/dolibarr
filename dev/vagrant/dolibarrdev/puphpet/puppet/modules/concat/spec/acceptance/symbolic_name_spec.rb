require 'spec_helper_acceptance'

describe 'symbolic name' do
  pp = <<-EOS
    concat { 'not_abs_path':
      path => '/tmp/concat/file',
    }

    concat::fragment { '1':
      target  => 'not_abs_path',
      content => '1',
      order   => '01',
    }

    concat::fragment { '2':
      target  => 'not_abs_path',
      content => '2',
      order   => '02',
    }
  EOS

  it 'applies the manifest twice with no stderr' do
    expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
    expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
  end

  describe file('/tmp/concat/file') do
    it { should be_file }
    it { should contain '1' }
    it { should contain '2' }
  end
end
