define("ace/snippets/cedar.snippets",["require","exports","module"],function(e,t,n){n.exports='snippet permit\n	permit (\n	    principal == ${1:Principal}::"${2:id}",\n	    action == Action::"${3:action}",\n	    resource == ${4:Resource}::"${5:id}"\n	)${0};\nsnippet permit_when\n	permit (principal, action, resource)\n	when { ${0:condition} };\nsnippet forbid\n	forbid (principal, action, resource)\n	when { ${0:condition} };\nsnippet forbid_unless\n	forbid (principal, action, resource)\n	unless { ${0:condition} };\nsnippet when\n	when { ${0:condition} }\nsnippet unless\n	unless { ${0:condition} }\nsnippet annotation\n	@${1:name}("${2:value}")\n'}),define("ace/snippets/cedar",["require","exports","module","ace/snippets/cedar.snippets"],function(e,t,n){"use strict";t.snippetText=e("./cedar.snippets"),t.scope="cedar"});                (function() {
                    window.require(["ace/snippets/cedar"], function(m) {
                        if (typeof module == "object" && typeof exports == "object" && module) {
                            module.exports = m;
                        }
                    });
                })();
