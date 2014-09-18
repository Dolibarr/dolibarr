require 'spec_helper'

describe 'elasticsearch::ruby', :type => 'define' do

  let(:facts) { {:operatingsystem => 'CentOS' }}

  [ 'tire', 'stretcher', 'elastic_searchable', 'elasticsearch'].each do |rubylib|

    context "installation of library #{rubylib}" do

      let(:title) { rubylib }

      it { should contain_package(rubylib).with(:provider => 'gem') }

    end

  end

end
