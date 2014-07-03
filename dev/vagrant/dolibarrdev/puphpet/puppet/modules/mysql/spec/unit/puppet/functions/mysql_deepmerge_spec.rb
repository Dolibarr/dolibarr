#! /usr/bin/env ruby -S rspec

require 'spec_helper'

describe Puppet::Parser::Functions.function(:mysql_deepmerge) do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  describe 'when calling mysql_deepmerge from puppet' do
    it "should not compile when no arguments are passed" do
      pending("Fails on 2.6.x, see bug #15912") if Puppet.version =~ /^2\.6\./
      Puppet[:code] = '$x = mysql_deepmerge()'
      expect {
        scope.compiler.compile
      }.to raise_error(Puppet::ParseError, /wrong number of arguments/)
    end

    it "should not compile when 1 argument is passed" do
      pending("Fails on 2.6.x, see bug #15912") if Puppet.version =~ /^2\.6\./
      Puppet[:code] = "$my_hash={'one' => 1}\n$x = mysql_deepmerge($my_hash)"
      expect {
        scope.compiler.compile
      }.to raise_error(Puppet::ParseError, /wrong number of arguments/)
    end
  end

  describe 'when calling mysql_deepmerge on the scope instance' do
    it 'should require all parameters are hashes' do
      expect { new_hash = scope.function_mysql_deepmerge([{}, '2'])}.to raise_error(Puppet::ParseError, /unexpected argument type String/)
      expect { new_hash = scope.function_mysql_deepmerge([{}, 2])}.to raise_error(Puppet::ParseError, /unexpected argument type Fixnum/)
    end

    it 'should accept empty strings as puppet undef' do
      expect { new_hash = scope.function_mysql_deepmerge([{}, ''])}.not_to raise_error
    end

    it 'should be able to mysql_deepmerge two hashes' do
      new_hash = scope.function_mysql_deepmerge([{'one' => '1', 'two' => '1'}, {'two' => '2', 'three' => '2'}])
      new_hash['one'].should   == '1'
      new_hash['two'].should   == '2'
      new_hash['three'].should == '2'
    end

    it 'should mysql_deepmerge multiple hashes' do
      hash = scope.function_mysql_deepmerge([{'one' => 1}, {'one' => '2'}, {'one' => '3'}])
      hash['one'].should == '3'
    end

    it 'should accept empty hashes' do
      scope.function_mysql_deepmerge([{},{},{}]).should == {}
    end

    it 'should mysql_deepmerge subhashes' do
      hash = scope.function_mysql_deepmerge([{'one' => 1}, {'two' => 2, 'three' => { 'four' => 4 } }])
      hash['one'].should == 1
      hash['two'].should == 2
      hash['three'].should == { 'four' => 4 }
    end

    it 'should append to subhashes' do
      hash = scope.function_mysql_deepmerge([{'one' => { 'two' => 2 } }, { 'one' => { 'three' => 3 } }])
      hash['one'].should == { 'two' => 2, 'three' => 3 }
    end

    it 'should append to subhashes 2' do
      hash = scope.function_mysql_deepmerge([{'one' => 1, 'two' => 2, 'three' => { 'four' => 4 } }, {'two' => 'dos', 'three' => { 'five' => 5 } }])
      hash['one'].should == 1
      hash['two'].should == 'dos'
      hash['three'].should == { 'four' => 4, 'five' => 5 }
    end

    it 'should append to subhashes 3' do
      hash = scope.function_mysql_deepmerge([{ 'key1' => { 'a' => 1, 'b' => 2 }, 'key2' => { 'c' => 3 } }, { 'key1' => { 'b' => 99 } }])
      hash['key1'].should == { 'a' => 1, 'b' => 99 }
      hash['key2'].should == { 'c' => 3 }
    end

    it 'should equate keys mod dash and underscore' do
      hash = scope.function_mysql_deepmerge([{  'a-b-c' => 1 } , { 'a_b_c' => 10 }])
      hash['a_b_c'].should == 10
      hash.should_not have_key('a-b-c')
    end

    it 'should keep style of the last when keys are euqal mod dash and underscore' do
      hash = scope.function_mysql_deepmerge([{  'a-b-c' => 1,  'b_c_d' => { 'c-d-e' => 2, 'e-f-g' => 3 }} , { 'a_b_c' => 10, 'b-c-d' => { 'c_d_e' => 12 } }])
      hash['a_b_c'].should == 10
      hash.should_not have_key('a-b-c')
      hash['b-c-d'].should == { 'e-f-g' => 3, 'c_d_e' => 12 }
      hash.should_not have_key('b_c_d')
    end
  end
end
