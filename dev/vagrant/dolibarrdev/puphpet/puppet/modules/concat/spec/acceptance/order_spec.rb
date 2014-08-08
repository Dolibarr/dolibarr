require 'spec_helper_acceptance'

describe 'concat order' do
  before(:all) do
    shell('rm -rf /tmp/concat /var/lib/puppet/concat')
    shell('mkdir -p /tmp/concat')
  end

  context '=> alpha' do
    pp = <<-EOS
      concat { '/tmp/concat/foo':
        order => 'alpha'
      }
      concat::fragment { '1':
        target  => '/tmp/concat/foo',
        content => 'string1',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/foo',
        content => 'string2',
      }
      concat::fragment { '10':
        target  => '/tmp/concat/foo',
        content => 'string10',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/foo') do
      it { should be_file }
      it { should contain "string10\nstring1\nsring2" }
    end
  end

  context '=> numeric' do
    pp = <<-EOS
      concat { '/tmp/concat/foo':
        order => 'numeric'
      }
      concat::fragment { '1':
        target  => '/tmp/concat/foo',
        content => 'string1',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/foo',
        content => 'string2',
      }
      concat::fragment { '10':
        target  => '/tmp/concat/foo',
        content => 'string10',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/foo') do
      it { should be_file }
      it { should contain "string1\nstring2\nsring10" }
    end
  end
end # concat order

describe 'concat::fragment order' do
  before(:all) do
    shell('rm -rf /tmp/concat /var/lib/puppet/concat')
    shell('mkdir -p /tmp/concat')
  end

  context '=> reverse order' do
    pp = <<-EOS
      concat { '/tmp/concat/foo': }
      concat::fragment { '1':
        target  => '/tmp/concat/foo',
        content => 'string1',
        order   => '15',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/foo',
        content => 'string2',
        # default order 10
      }
      concat::fragment { '3':
        target  => '/tmp/concat/foo',
        content => 'string3',
        order   => '1',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/foo') do
      it { should be_file }
      it { should contain "string3\nstring2\nsring1" }
    end
  end

  context '=> normal order' do
    pp = <<-EOS
      concat { '/tmp/concat/foo': }
      concat::fragment { '1':
        target  => '/tmp/concat/foo',
        content => 'string1',
        order   => '01',
      }
      concat::fragment { '2':
        target  => '/tmp/concat/foo',
        content => 'string2',
        order   => '02'
      }
      concat::fragment { '3':
        target  => '/tmp/concat/foo',
        content => 'string3',
        order   => '03',
      }
    EOS

    it 'applies the manifest twice with no stderr' do
      expect(apply_manifest(pp, :catch_failures => true).stderr).to eq("")
      expect(apply_manifest(pp, :catch_changes => true).stderr).to eq("")
    end

    describe file('/tmp/concat/foo') do
      it { should be_file }
      it { should contain "string1\nstring2\nsring3" }
    end
  end
end # concat::fragment order
