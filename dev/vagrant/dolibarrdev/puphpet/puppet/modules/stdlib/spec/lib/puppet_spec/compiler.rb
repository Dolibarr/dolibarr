#! /usr/bin/env ruby -S rspec
module PuppetSpec::Compiler
  def compile_to_catalog(string, node = Puppet::Node.new('foonode'))
    Puppet[:code] = string
    Puppet::Parser::Compiler.compile(node)
  end

  def compile_to_ral(manifest)
    catalog = compile_to_catalog(manifest)
    ral = catalog.to_ral
    ral.finalize
    ral
  end

  def compile_to_relationship_graph(manifest, prioritizer = Puppet::Graph::SequentialPrioritizer.new)
    ral = compile_to_ral(manifest)
    graph = Puppet::Graph::RelationshipGraph.new(prioritizer)
    graph.populate_from(ral)
    graph
  end

  if Puppet.version.to_f >= 3.3
    def apply_compiled_manifest(manifest, prioritizer = Puppet::Graph::SequentialPrioritizer.new)
      transaction = Puppet::Transaction.new(compile_to_ral(manifest),
                                          Puppet::Transaction::Report.new("apply"),
                                          prioritizer)
      transaction.evaluate
      transaction.report.finalize_report

      transaction
    end
  else
    def apply_compiled_manifest(manifest)
      transaction = Puppet::Transaction.new(compile_to_ral(manifest), Puppet::Transaction::Report.new("apply"))
      transaction.evaluate
      transaction.report.finalize_report

      transaction
    end
  end

  def order_resources_traversed_in(relationships)
    order_seen = []
    relationships.traverse { |resource| order_seen << resource.ref }
    order_seen
  end
end
