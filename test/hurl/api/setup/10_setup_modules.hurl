# Check module list
GET http://{{hostnport}}/api/index.php/setup/modules
HTTP 200
[Asserts]
jsonpath "$" count > 2

# Get all states for all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/all?status=all
HTTP 200
[Captures]
workflow_active: jsonpath "$['WORKFLOW']['active']"
[Asserts]
jsonpath "$.WORKFLOW.modName" == "modWorkflow"
jsonpath "$.WORKFLOW.origin" == "core"
jsonpath "$.WORKFLOW.active" matches /^\d{1}$/
jsonpath "$.WORKFLOW.publisher" == ""
jsonpath "$.WORKFLOW.version" == "dolibarr"

# Get active all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/all?status=active
HTTP 200
[Asserts]
jsonpath "$.*" count > 2

# Get disabled for all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/all?status=disabled
HTTP 200
[Asserts]
jsonpath "$.*" count > 2

# Get all states for all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/core?status=all
HTTP 200
[Asserts]
jsonpath "$.*" count > 2

# Get active all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/core?status=active
HTTP 200
[Asserts]
jsonpath "$.*" count > 2

# Get disabled for all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/core?status=disabled
HTTP 200
[Asserts]
jsonpath "$.*" count > 2

# Get all states for all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/external?status=all
HTTP 200
[Asserts]
jsonpath "$.*" count >= 0

# Get active all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/external?status=active
HTTP 200
[Asserts]
jsonpath "$.*" count >= 0

# Get disabled for all modules
GET http://{{hostnport}}/api/index.php/setup/modules/status/external?status=disabled
HTTP 200
[Asserts]
jsonpath "$.*" count >=0
