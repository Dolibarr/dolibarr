require 'spec_helper'

describe Puppet::Type.type(:postgresql_psql).provider(:ruby) do
  let(:name) { 'rspec psql test' }
  let(:resource) do
    Puppet::Type.type(:postgresql_psql).new({ :name => name, :provider => :ruby }.merge attributes)
  end

  let(:provider) { resource.provider }

  context("#run_sql_command") do
    describe "with default attributes" do
      let(:attributes) do { :db => 'spec_db' } end

      it "executes with the given psql_path on the given DB" do
        expect(provider).to receive(:run_command).with(['psql', '-d',
          attributes[:db], '-t', '-c', 'SELECT something'], 'postgres',
          'postgres')

        provider.run_sql_command("SELECT something")
      end
    end
    describe "with psql_path and db" do
      let(:attributes) do {
        :psql_path  => '/opt/postgres/psql',
        :psql_user  => 'spec_user',
        :psql_group => 'spec_group',
        :cwd        => '/spec',
        :db         => 'spec_db'
      } end

      it "executes with the given psql_path on the given DB" do
        expect(Dir).to receive(:chdir).with(attributes[:cwd]).and_yield
        expect(provider).to receive(:run_command).with([attributes[:psql_path],
          '-d', attributes[:db], '-t', '-c', 'SELECT something'],
          attributes[:psql_user], attributes[:psql_group])

        provider.run_sql_command("SELECT something")
      end
    end
    describe "with search_path string" do
      let(:attributes) do {
        :search_path => "schema1"
      } end

      it "executes with the given search_path" do
        expect(provider).to receive(:run_command).with(['psql', '-t', '-c',
          'set search_path to schema1; SELECT something'],
          'postgres', 'postgres')

        provider.run_sql_command("SELECT something")
      end
    end
    describe "with search_path array" do
      let(:attributes) do {
        :search_path => ['schema1','schema2'],
      } end

      it "executes with the given search_path" do
        expect(provider).to receive(:run_command).with(['psql', '-t', '-c',
          'set search_path to schema1,schema2; SELECT something'],
          'postgres',
          'postgres'
        )

        provider.run_sql_command("SELECT something")
      end
    end

  end

  context("#command") do
    context "when unless is specified" do
      [:true, :false, true, false].each do |refresh|
        context "and refreshonly is #{refresh}" do
          let(:attributes) { {
            :command     => 'SELECT something',
            :db          => 'spec_db',
            :unless      => 'SELECT something',
            :refreshonly => refresh
          } }

          it "does not fail when the status is successful" do
            expect(provider).to receive(:run_unless_sql_command).and_return ["1 row returned", 0]
            provider.command
          end

          it "returns the given command when rows are returned" do
            expect(provider).to receive(:run_unless_sql_command).and_return ["1 row returned", 0]
            expect(provider.command).to eq("SELECT something")
          end

          it "does not return the given command when no rows are returned" do
            expect(provider).to receive(:run_unless_sql_command).and_return ["0 rows returned", 0]
            expect(provider.command).to_not eq("SELECT something")
          end

          it "raises an error when the sql command fails" do
            allow(provider).to receive(:run_unless_sql_command).and_return ["Something went wrong", 1]
            expect { provider.command }.to raise_error(Puppet::Error, /Something went wrong/)
          end
        end
      end
    end

    context "when unless is not specified" do
      context "and refreshonly is true" do
        let(:attributes) do {
          :command     => 'SELECT something',
          :db          => 'spec_db',
          :refreshonly => :true
        } end
        it "does not run unless sql command" do
          expect(provider).to_not receive(:run_unless_sql_command)
          provider.command
        end

        it "returns the given command do disable sync" do
          expect(provider.command).to eq("SELECT something")
        end
      end

      context "and refreshonly is false" do
        let(:attributes) do {
          :command     => 'SELECT something',
          :db          => 'spec_db',
          :refreshonly => :false
        } end
        it "does not run unless sql command" do
          expect(provider).to_not receive(:run_unless_sql_command)
          provider.command
        end

        it "does not return the command so as to enable sync" do
          expect(provider.command).to_not eq("SELECT something")
        end
      end
    end
  end
end
