require 'spec_helper_acceptance'

codename = fact('lsbdistcodename')
case fact('operatingsystem')
when 'Ubuntu'
  repos = 'main universe multiverse restricted'
when 'Debian'
  repos = 'main contrib non-free'
end

describe 'apt::backports class', :unless => UNSUPPORTED_PLATFORMS.include?(fact('osfamily')) do
  context 'defaults' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt::backports': }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end
  end

  context 'release' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt::backports': release => '#{codename}' }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/sources.list.d/backports.list') do
      it { should be_file }
      it { should contain "#{codename}-backports #{repos}" }
    end
  end

  context 'location' do
    it 'should work with no errors' do
      pp = <<-EOS
      class { 'apt::backports': release => 'precise', location => 'http://localhost/ubuntu' }
      EOS

      apply_manifest(pp, :catch_failures => true)
    end

    describe file('/etc/apt/sources.list.d/backports.list') do
      it { should be_file }
      it { should contain "deb http://localhost/ubuntu precise-backports #{repos}" }
    end
  end

  context 'reset' do
    it 'deletes backport files' do
      shell('rm -rf /etc/apt/sources.list.d/backports.list')
      shell('rm -rf /etc/apt/preferences.d/backports.pref')
    end
  end

end
