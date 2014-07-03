#!/usr/bin/env ruby -S rspec
require 'spec_helper'

describe "the pick_default function" do
  let(:scope) { PuppetlabsSpec::PuppetInternals.scope }

  it "should exist" do
    Puppet::Parser::Functions.function("pick_default").should == "function_pick_default"
  end

  it 'should return the correct value' do
    scope.function_pick_default(['first', 'second']).should == 'first'
  end

  it 'should return the correct value if the first value is empty' do
    scope.function_pick_default(['', 'second']).should == 'second'
  end

  it 'should skip empty string values' do
    scope.function_pick_default(['', 'first']).should == 'first'
  end

  it 'should skip :undef values' do
    scope.function_pick_default([:undef, 'first']).should == 'first'
  end

  it 'should skip :undefined values' do
    scope.function_pick_default([:undefined, 'first']).should == 'first'
  end

  it 'should return the empty string if it is the last possibility' do
    scope.function_pick_default([:undef, :undefined, '']).should == ''
  end

  it 'should return :undef if it is the last possibility' do
    scope.function_pick_default(['', :undefined, :undef]).should == :undef
  end

  it 'should return :undefined if it is the last possibility' do
    scope.function_pick_default([:undef, '', :undefined]).should == :undefined
  end

  it 'should return the empty string if it is the only possibility' do
    scope.function_pick_default(['']).should == ''
  end

  it 'should return :undef if it is the only possibility' do
    scope.function_pick_default([:undef]).should == :undef
  end

  it 'should return :undefined if it is the only possibility' do
    scope.function_pick_default([:undefined]).should == :undefined
  end

  it 'should error if no values are passed' do
    expect { scope.function_pick_default([]) }.to raise_error(Puppet::Error, /Must receive at least one argument./)
  end
end
