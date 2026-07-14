---
name: code-review
description: Review code and fix bad practices
license: MIT
user-invocable: true
allowed-tools:
 - read_file
 - write_file
 - grep
---
 
 
# Skill: Review code for Dolibarr practices and fix it

## When to use this skill

Use this skill whenever the user asks to review code to fix bad practices or to update code to match good practices.


## General Rules

- Follow the coding style already used in files in module builder template in htdocs/modulebuilder/templates
- Modify the minimum amount of existing code.


## Rules

- Use PSR-12 coding style except tabulation that must use TAB characters and not SPACES.
- Remove all spaces at end of lines 
- Rewrite all code that is not in english


## Output

When generating code:

- provide only the relevant PHP code
- preserve the existing file formatting
- do not rewrite unrelated methods
- explain briefly what is being tested

