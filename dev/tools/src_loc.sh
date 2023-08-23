# Script to count lines of code of the project

# Count lines of code of Dolibarr itself
#cloc . --exclude-dir=includes --ignore-whitespace --vcs=git --by-file
cloc . --exclude-dir=includes --ignore-whitespace --vcs=git

# Count lines of code of external dependencies
cloc htdocs/includes --ignore-whitespace --vcs=git
