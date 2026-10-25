# GET list of countries with no regions or states
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$" count > 0
jsonpath "$[*]" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists


# GET list of countries with no regions
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?loadregions=0
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists


# GET list of countries with no states
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists


# GET list of countries with neither regions nor states
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?loadregions=0&loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists



# GET list of countries with regions
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?loadregions=1
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists
jsonpath "$[*].regions" exists
jsonpath "$[*].regions" count > 0

# GET list of countries with states
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists
jsonpath "$[*].states" count > 0
jsonpath "$[*].states" exists

# GET list of countries with both regions and states
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries?loadregions=1&loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isList
jsonpath "$[*].id" exists
jsonpath "$[*].code" exists
jsonpath "$[*].label" exists
jsonpath "$[*].active" exists
jsonpath "$[*].code_iso" exists
jsonpath "$[*].numeric_code" exists
jsonpath "$[*].regions" exists
jsonpath "$[*].states" exists


# GET with ID 1 - which should be France
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadregions=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/1?loadregions=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadstates=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/1?loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadregions=0 and loadstates=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/1?loadregions=0&loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists


# GET with ID 1 - which should be France explicit loadregions=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/1?loadregions=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" exists
jsonpath "$.states" == null


# GET with ID 1 - which should be France explicit loadstates=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/1?loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" == null
jsonpath "$.states" exists

# GET with ID 1 - which should be France explicit loadregions=1 and loadstates=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/1?loadregions=1&loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" exists
jsonpath "$.states" exists


# GET with ID 1 - which should be France
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/FR
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadregions=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/FR?loadregions=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadstates=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/FR?loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadregions=0 and loadstates=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/FR?loadregions=0&loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists


# GET with ID 1 - which should be France explicit loadregions=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/FR?loadregions=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" exists
jsonpath "$.states" == null


# GET with ID 1 - which should be France explicit loadstates=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/FR?loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" == null
jsonpath "$.states" exists

# GET with ID 1 - which should be France explicit loadregions=1 and loadstates=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byCode/FR?loadregions=1&loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" exists
jsonpath "$.states" exists


# GET with ID 1 - which should be France
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/FRA
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadregions=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/FRA?loadregions=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadstates=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/FRA?loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists

# GET with ID 1 - which should be France explicit loadregions=0 and loadstates=0
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/FRA?loadregions=0&loadstates=0
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" not exists
jsonpath "$.states" not exists


# GET with ID 1 - which should be France explicit loadregions=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/FRA?loadregions=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" exists
jsonpath "$.states" == null


# GET with ID 1 - which should be France explicit loadstates=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/FRA?loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" == null
jsonpath "$.states" exists

# GET with ID 1 - which should be France explicit loadregions=1 and loadstates=1
GET http://{{hostnport}}/api/index.php/setup/dictionary/countries/byISO/FRA?loadregions=1&loadstates=1
HTTP 200
[Asserts]
jsonpath "$" isObject
jsonpath "$.id" exists
jsonpath "$.code" exists
jsonpath "$.label" exists
jsonpath "$.active" exists
jsonpath "$.code_iso" exists
jsonpath "$.numeric_code" exists
jsonpath "$.regions" exists
jsonpath "$.states" exists
