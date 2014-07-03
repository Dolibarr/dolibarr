#!/bin/bash

# report_mongodb.sh - Made for Puppi
# e.g. somemongohost/dbname

# Sources common header for Puppi scripts
. $(dirname $0)/header || exit 10


# Show help
showhelp () {
    echo "This script reports deployments to a mongo DB."
    echo "It has the following options:"
    echo "-e <env_key> - Facter key to identify server environment (default: env)."
    echo "If no facter key can be found, the fallback is ''environment''."
    echo 
    echo "Examples:"
    echo "deploy_files.sh mongodb://someuser:hispassword@somehost/somedb"
    echo "deploy_files.sh -e env mongodb://someuser:hispassword@somehost/somedb"
}


env_key="env"
fallback_key="environment"

while [ $# -gt 0 ]; do
  case "$1" in
    -e)
      env_key=$2
      echo "env_key"
      shift 2 ;;
    *)
      mongourl=$1
      shift 1
      ;;
  esac
done

if [ "$EXITCRIT" = "1" ] ; then
    proposed_exit=2
fi

if [ "$EXITWARN" = "1" ] ; then
    proposed_exit=1
fi

# check prerequisites
mongo -version > /dev/null
if [ $? -ne 0 ]; then
        echo "mongo-client is not installed, aborting"
        exit $proposed_exit
fi

fqdn=$(facter fqdn)

environment=$(facter ${env_key} -p)

if [ -z "${environment} ]
then
    environment=$(facter ${fallback_key} -p)
fi


# something like mongodb://someuser:hispassword@somehost/somedb


if [[ ! $mongourl =~ "mongodb://" ]]; then
  echo "WARNING: mongourl invalid! Please use a valid monurl!"
  showhelp
  exit $proposed_exit
fi

if [[ $mongourl =~ @ ]]; then
  # ok we have to deal with passwords
  # you HAVE to provide a password if you provide a user
  mongodb=`echo $mongourl | sed 's/.*@//'`
  mongouser=`echo $mongourl | sed 's/mongodb:\/\///' | sed 's/:.*//' `
  mongopassword=`echo $mongourl | sed 's/mongodb:\/\///' | sed 's/[^:]*://' | sed 's/@.*//' `
  mongoarguments="--username $mongouser --password $mongopassword"
else
  mongodb=`echo $mongourl | sed 's/mongodb:\/\///'` 	
fi

result=$(grep result $logdir/$project/$tag/summary | awk '{ print $NF }')
summary=$(cat $logdir/$project/$tag/summary)

mcmd="db.deployments.insert({ts:new Date(),result:\"${result}\",fqdn:\"${fqdn}\",project:\"${project}\",source:\"${source}\",tag:\"${tag}\",version:\"${version}\",artifact:\"${artifact}\",testmode:\"${testmode}\",warfile:\"${warfile}\",environment:\"${environment}\"}); quit(0)"


mongo $mongoarguments $mongodb --eval "$mcmd"

# Now do a reporting to enable "most-recent-versions on all servers"

read -r -d '' mcmd <<'EOF'
var map = function() {
  project=this.project ;
  emit( this.fqdn +":"+ this.project,  {project:this.project, fqdn:this.fqdn, ts:this.ts,version:this.version,environment:this.environment}  );
};
var reduce = function(k,vals) {
  result = vals[0];
  vals.forEach(function(val) { if (val.ts > result.ts) result=val } ) ;
  return result;
};
db.deployments.mapReduce(
  map,
  reduce,
  {out:{replace:"versions"}})
EOF

mongo $mongoarguments $mongodb --eval "$mcmd"

exit $proposed_exit
