define("ace/snippets/nunjucks",["require","exports","module"], function(require, exports, module) {
"use strict";

exports.snippetText =undefined;
exports.scope = "nunjucks";

});                (function() {
                    window.require(["ace/snippets/nunjucks"], function(m) {
                        if (typeof module == "object" && typeof exports == "object" && module) {
                            module.exports = m;
                        }
                    });
                })();
            