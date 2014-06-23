require 'spec_helper'

describe 'composer::params' do
  ['RedHat', 'Debian', 'Linux'].each do |osfamily|
    context "on #{osfamily} operating system family" do
      let(:facts) { {
        :osfamily        => osfamily,
        :operatingsystem => 'Amazon',
      } }

      it { should compile }
    end
  end
end
