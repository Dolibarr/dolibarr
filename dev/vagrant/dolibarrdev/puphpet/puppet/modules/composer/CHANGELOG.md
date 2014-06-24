v1.2.1
======
f44b7e5 Now also supports Amazon Linux (RedHat)

9341805 Now `suhosin_enabled` parameter is correctly documented.

v1.2.0
======
66b071a (HEAD, tag: 1.2.0, master) Bumping version to 1.2.0

166ec87 Updated README.md

626ee43 (origin/master, origin/HEAD) Updated CHANGELOG format

1364058 Moved CHANGELOG to markdown format

6f21dcb Updated LICENSE file

6209eb8 Added CHANGELOG file

6307d5a Add parameter 'php_bin' to override name or path of php binary

9e484e9 (origin/rspec_head_fixes, rspec_head_fixes) just match on errorname, not specific exception

db4176e update specs for latest rspec-puppet 1.0.1+

v1.1.1
======
17b2309 (tag: 1.1.1) Update Modulefile

d848038 Used puppetlabs/git >= 0.0.2

0d75cff doc updates for 1.1.0 release

v1.1.0
======
3b46e4d (tag: 1.1.0) bumping version to 1.1.0 for refreshonly and user features

5290e8e support setting exec user for project and exec

6af1e25 ignore puppet module package folder

c2106ec Add refreshonly parameter to exec

v1.0.1
======
fb1fd04 (tag: 1.0.1) Bumped version to 1.0.1

bf43913 (origin/deprecated_erb_variables) fix deprecated variables in the exec erb template

342b898 (origin/documentation_refactor) document refactor, add spec test information

3677acc adding tests for new suhosin_enable param and Debian family

de86c0d Only run augeas commands if suhosin is enabled

v1.0.0
======
f5d214a (tag: 1.0.0) Bumping version to 1.0.0

12589bf fixes for travis-ci building

5279b92 spec testing using rspec-puppet

3069608 documentation updates for composer_home and previous PRs

b5faa45 add a composer_home fact and use it to set up environment

v0.1.1
======
dbc0c74 Bumping version to 0.1.1

b4833d6 no-custom-installers is deprecated in favor of no-plugins

acdc73c dry up the composer binary download code

41f3a7b CentOS isn't actually an $::osfamily value

d54c0db PHP binary is provided by php-cli on RHEL systems

v0.1.0
======
1e8f9f1 (tag: 0.1.0) Adding License file.

523c28f (igalic/option-names, igalic-option-names) update readme with the new options

3d2ddda double-negating option names is confusing

be518cf (igalic/style, igalic-style) Fix puppet lint complaints

4050077 There's no need for these files to be executable

522e93c Updated temp path.

bf0f9e7 Support centos/redhat

f45e9de Support redhat/centos

920d1ca Support redhat/centos

v0.0.6
======
78643ef (tag: 0.0.6) Bumping version to 0.0.6

0fbfb53 Fixing bug where global path is overwritten by local scope.

v0.0.5
======
ee4e49b (tag: 0.0.5) Bumping version to 0.0.5

17ca5ee Added varaible composer path to exec calls.

v0.0.4
======
e94be5e (tag: 0.0.4) Bumping version to 0.0.4

a27e45f Fixed dry_run parameter

28cfee8 Adding version parameter to project task README

v0.0.3
======
4787b24 Bumping version to 0.0.3

4ee9547 (tag: 0.0.3) Fixing type in exec manifest.

v0.0.2
======
974d2ad (tag: 0.0.2) Bumping version to 0.0.2

667eb18 Fixed README

925aa97 Fixed Modulefile.
