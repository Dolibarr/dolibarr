
== Install exakat ==
mkdir exakat 
cd exakat 
curl -o exakat.phar http://dist.exakat.io/index.php?file=latest 
curl -o apache-tinkerpop-gremlin-server-3.3.5-bin.zip http://dist.exakat.io/apache-tinkerpop-gremlin-server-3.3.5-bin.zip
unzip apache-tinkerpop-gremlin-server-3.3.5-bin.zip
mv apache-tinkerpop-gremlin-server-3.3.5 tinkergraph 
rm -rf apache-tinkerpop-gremlin-server-3.3.5-bin.zip
cd tinkergraph ./bin/gremlin-server.sh -i org.apache.tinkerpop neo4j-gremlin 3.3.5
cd ..
 
php exakat.phar version
php exakat.phar doctor

== Init project ==
php 


Edit config.ini file to exclude some dirs:
ignore_dirs[] = "/htdocs/includes";
ignore_dirs[] = "/scripts";
ignore_dirs[] = "/build";
ignore_dirs[] = "/dev";
ignore_dirs[] = "/documents";


== Analyze project ==
php
