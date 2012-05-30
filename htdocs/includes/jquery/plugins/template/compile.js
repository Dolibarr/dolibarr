#!/usr/bin/env node
/*
 * JavaScript Templates Compiler 2.1.0
 * https://github.com/blueimp/JavaScript-Templates
 *
 * Copyright 2011, Sebastian Tschan
 * https://blueimp.net
 *
 * Licensed under the MIT license:
 * http://www.opensource.org/licenses/MIT
 */

/*jslint nomen: true */
/*global require, __dirname, process, console */

(function () {
    "use strict";
    var tmpl = require("./tmpl.js").tmpl,
        fs = require("fs"),
        path = require("path"),
        jsp = require("uglify-js").parser,
        pro = require("uglify-js").uglify,
        // Retrieve the content of the minimal runtime:
        runtime = fs.readFileSync(__dirname + "/runtime.js", "utf8"),
        // A regular expression to parse templates from script tags in a HTML page:
        regexp = /<script( id="([\w\-]+)")? type="text\/x-tmpl"( id="([\w\-]+)")?>([\s\S]+?)<\/script>/gi,
        // A regular expression to match the helper function names:
        helperRegexp = new RegExp(
            tmpl.helper.match(/\w+(?=\s*=\s*function\s*\()/g).join("\\s*\\(|") + "\\s*\\("
        ),
        // A list to store the function bodies:
        list = [],
        code,
        ast;
    // Extend the Templating engine with a print method for the generated functions:
    tmpl.print = function (str) {
        // Only add helper functions if they are used inside of the template:
        var helper = helperRegexp.test(str) ? tmpl.helper : "",
            body = str.replace(tmpl.regexp, tmpl.func);
        if (helper || (/_e\s*\(/.test(body))) {
            helper = "_e=tmpl.encode" + helper + ",";
        }
        return "function(" + tmpl.arg + ",tmpl){" +
            ("var " + helper + "_s='" + body + "';return _s;")
            .split("_s+='';").join("") + "}";
    };
    // Loop through the command line arguments:
    process.argv.forEach(function (file, index) {
        var listLength = list.length,
            content,
            result,
            id;
        // Skipt the first two arguments, which are "node" and the script:
        if (index > 1) {
            content = fs.readFileSync(file, "utf8");
            while (true) {
                // Find templates in script tags:
                result = regexp.exec(content);
                if (!result) {
                    break;
                }
                id = result[2] || result[4];
                list.push("'" + id + "':" + tmpl.print(result[5]));
            }
            if (listLength === list.length) {
                // No template script tags found, use the complete content:
                id = path.basename(file, path.extname(file));
                list.push("'" + id + "':" + tmpl.print(content));
            }
        }
    });
    // Combine the generated functions as cache of the minimal runtime:
    code = runtime.replace("{}", "{" + list.join(",") + "}");
    // Parse the code and get the initial AST (Abstract Syntac Tree):
    ast = jsp.parse(code);
    // Get a new AST with mangled names:
    ast = pro.ast_mangle(ast);
    // Get an AST with compression optimizations:
    ast = pro.ast_squeeze(ast);
    // Generate the code and print it to the console output:
    console.log(pro.gen_code(ast));
}());
