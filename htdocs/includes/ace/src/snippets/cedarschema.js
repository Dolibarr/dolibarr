define("ace/snippets/cedarschema.snippets",["require","exports","module"],function(e,t,n){n.exports='snippet namespace\n	namespace ${1:Namespace} {\n	    ${0}\n	}\nsnippet entity\n	entity ${1:EntityName} {\n	    ${0}\n	};\nsnippet entity_in\n	entity ${1:EntityName} in [${2:Parent}] {\n	    ${0}\n	};\nsnippet action\n	action "${1:actionName}" appliesTo {\n	    principal: [${2:Principal}],\n	    resource: [${3:Resource}],\n	    context: {${0}}\n	};\nsnippet action_in\n	action "${1:actionName}" in [${2:ParentAction}] appliesTo {\n	    principal: [${3:Principal}],\n	    resource: [${4:Resource}],\n	    context: {${0}}\n	};\nsnippet type\n	type ${1:TypeName} = {\n	    ${0}\n	};\n'}),define("ace/snippets/cedarschema",["require","exports","module","ace/snippets/cedarschema.snippets"],function(e,t,n){"use strict";t.snippetText=e("./cedarschema.snippets"),t.scope="cedarschema"});                (function() {
                    window.require(["ace/snippets/cedarschema"], function(m) {
                        if (typeof module == "object" && typeof exports == "object" && module) {
                            module.exports = m;
                        }
                    });
                })();
