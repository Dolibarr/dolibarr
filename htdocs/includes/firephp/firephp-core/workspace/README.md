
The [PINF JavaScript Loader](https://github.com/pinf/loader-js) is used to provide a development environment and package releases for this project.

**NOTE:** It is assumed you have the _PINF JavaScript Loader_ mapped to the `commonjs` command and are using the `node` platform by default as explained [here](https://github.com/pinf/loader-js/blob/master/docs/Setup.md).


Publishing
==========

    git tag v...
    
    commonjs -v --script build .
    
    commonjs -v --script publish .


TODO: Auto-upload to PEAR channel server at http://pear.firephp.org/

NOTE: For PEAR RC releases: Change release stability to "beta" and capitalize "RC" in release version in package.xml
