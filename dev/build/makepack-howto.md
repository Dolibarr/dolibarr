# Dolibarr Makepack How To

This documentation describe steps to build a BETA or RELEASE versions of Dolibarr.
There is a chapter for BETA version and a chapter for a RELEASE version.


## Prerequisites

### On Linux

Prerequisites to build the tgz, debian and rpm packages:

`apt-get install perl tar dpkg dpatch p7zip-full rpm zip php-cli`

Prerequisites to build autoexe DoliWamp package from Linux (works also on recent Ubuntu using the Wine wow64 mode):

`apt-get install wine`

- Launch "wine cmd" to check a drive Z: pointing to / exists (it is created by default, see ~/.wine/dosdevices/). The makepack script calls ISCC.exe with Windows paths using the Z: drive.

- Install Inno Setup 5.6.1 ANSI (file innosetup-5.6.1.exe, and NOT the file innosetup-5.6.1-unicode.exe: the [Code] section of doliwamp.iss is not compatible with the Unicode compiler) from https://www.jrsoftware.org or https://files.jrsoftware.org/is/5/
  For example by running `wine innosetup-5.6.1.exe /VERYSILENT`.
  The compiler ISCC.exe will be installed into "C:\Program Files (x86)\Inno Setup 5".

- Install WampServer into "C:\wamp64" to have Apache, PHP and MariaDB (For example by running `wine wampserver3.2.6_x64.exe` from https://www.wampserver.com or https://wampserver.aviatechno.net, see file dev/build/exe/doliwamp.iss to know the version of Wampserver to install. The versions and directory names of Apache/PHP/MariaDB must match the values found into doliwamp.iss).
  Alternative if the WampServer installer does not run correctly with Wine: download the WampServer installer, extract only the required directories with "innoextract", and copy them into ~/.wine/drive_c/wamp64/bin/ (bin/apache/apache2.4.65, bin/php/php8.4.27 and bin/mariadb/mariadb11.4.9 for the example above). Note that with recent WampServer installers (Inno Setup 6.4+), "innoextract" may not support them, so you can also install WampServer with Wine after installing the required Visual C++ redistributables, or use the official zips of Apache/PHP/MariaDB for Windows.

- Create a script called ISCC.exe into your Linux PATH (for example /usr/local/bin/ISCC.exe) so that the makepack script can find and run it. Example of content:

```
#!/bin/sh
exec wine "C:\\Program Files (x86)\\Inno Setup 5\\ISCC.exe" "$@"
```

Do not forget to make it executable: `chmod +x /usr/local/bin/ISCC.exe`.



### On Windows

Prerequisites to build autoexe DoliWamp package from Windows:

- Install Inno Setup 5.6.1 ANSI (file innosetup-5.6.1.exe, and NOT the file innosetup-5.6.1-unicode.exe: the [Code] section of doliwamp.iss is not compatible with the Unicode compiler) (https://www.jrsoftware.org)
- Install Microsoft Visual C++ Redistributable 2017 (https://learn.microsoft.com/en-US/cpp/windows/latest-supported-vc-redist?view=msvc-170)
- Install WampServer-3.2.6-64.exe (Apache 2.4.51, PHP 7.4.26, MariaDB 10.6.5 for example. Version must match the values found into doliwamp.iss)
- Install GIT for Windows (https://git-scm.com/ => You must choose option "Add Git bash profile", "Git commit as-is")
- Install Dolibarr current version:
  `git clone https://github.com/dolibarr/dolibarr  or  git clone --branch X.Y https://github.com/dolibarr/dolibarr`

- Add the path of PHP (C:\wamp64\bin\php\php7.4.26) and InnoSetup (C:\Program Files (x86)\Inno Setup 5) into the %PATH% of Windows.

- Create a config file `c:\dolibarr\dolibarr\htdocs\conf\conf.php` with content

```
  <?php
  $dolibarr_main_document_root="c:\dolibarr\dolibarr\htdocs";
  $dolibarr_main_url_root='http://localhost';
```

- To manually build the .exe from Windows (Note: running from makepack-dolibarr.php script is however recommended):

  Open the file dev/build/exe/doliwamp.iss and click on button "Compile".
  The .exe file will be build into directory build.
  

## Actions to do a FREEZE / BETA

This section describes steps made by Dolibarr packaging team to make a beta version of Dolibarr, step by step.

- Check that all files on local working repository are committed.

- Update version/info in ChangeLog, for this you can:

To generate a changelog of a **major new version** x.y.0 (from a repo on branch develop), you can do

```
cd ~/git/dolibarr
git log `diff -u <(git rev-list --first-parent x.(y-1).0)  <(git rev-list --first-parent develop) | sed -ne 's/^ //p' | head -1`.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e '^FIX\|NEW' | sort -u | sed 's/FIXED:/FIX:/g' | sed 's/FIXED :/FIX:/g' | sed 's/FIX :/FIX:/g' | sed 's/FIX /FIX: /g' | sed 's/NEW :/NEW:/g' | sed 's/NEW /NEW: /g' > /tmp/changelogtocopy
```

To generate a changelog of a **intermediate new version** x.y.0 (from a repo on branch x.y), you can do

```
cd ~/git/dolibarr_x.y
git log `diff -u <(git rev-list --first-parent x.(y-1).0)  <(git rev-list --first-parent x.y.0) | sed -ne 's/^ //p' | head -1`.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e '^FIX\|NEW' | sort -u | sed 's/FIXED:/FIX:/g' | sed 's/FIXED :/FIX:/g' | sed 's/FIX :/FIX:/g' | sed 's/FIX /FIX: /g' | sed 's/NEW :/NEW:/g' | sed 's/NEW /NEW: /g' > /tmp/changelogtocopy
```

To generate a changelog of a **maintenance version** x.y.z, you can do

```
cd ~/git/dolibarr_x.y
git log x.y.z-1.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e '^FIX\|NEW' | sort -u | sed 's/FIXED:/FIX:/g' | sed 's/FIXED :/FIX:/g' | sed 's/FIX :/FIX:/g' | sed 's/FIX /FIX: /g' | sed 's/NEW :/NEW:/g' | sed 's/NEW /NEW: /g' > /tmp/changelogtocopy
```

- Recopy the content of the output file into the file ChangeLog.
  
  Note: To know number of changed lines: git diff --shortstat A B
  
- Update version number with x.y.z-w in file htdocs/filefunc.inc.php

- Commit all changes.

- Run `makepack-dolibarr.pl` to check the generation of all packages. No need to publish them.

- Post a news message on dolibarr.org about the freeze by cloning a past news + relay the news url on social networks. Include the content of the previously generated ChangeLog file into the news.

- Create a branch x.y (but only when version seems stable enough).


## Actions to do a RELEASE

### On Linux

This files describe steps made by Dolibarr packaging team to make a complete release of Dolibarr, step by step.
We suppose the branch x.y has already been created during the beta (see previous step) and we want to release a version x.y.z (with z >= 0)

- Check there is no pending issue with flag "Priority High/Blocking". List can be found here: https://github.com/Dolibarr/dolibarr/issues?q=is%3Aissue%20state%3Aopen%20label%3A%22Priority%20-%20High%20%2F%20Blocking%22

- Check there is no pending open security issue: List can be found here: https://github.com/Dolibarr/dolibarr/issues?q=is%3Aissue%20state%3Aopen%20label%3A%22Priority%20-%20Critical%20or%20Security%22

- Check that all files on local working repository are committed.

- Update version/info in ChangeLog, for this:

To generate a changelog of a **major new version** x.0.0 (from a repo on branch develop or x.y):

```
cd ~/git/dolibarr
git log `diff -u <(git rev-list --first-parent x.(y-1).0)  <(git rev-list --first-parent develop) | sed -ne 's/^ //p' | head -1`.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e '^FIX\|NEW' | sort -u | sed 's/FIXED:/FIX:/g' | sed 's/FIXED :/FIX:/g' | sed 's/FIX :/FIX:/g' | sed 's/FIX /FIX: /g' | sed 's/NEW :/NEW:/g' | sed 's/NEW /NEW: /g' > /tmp/changelogtocopy
```

To generate a changelog of a **intermediate new version** x.y.0 (from a repo on branch x.y);

```
cd ~/git/dolibarr_x.y
git log `diff -u <(git rev-list --first-parent x.(y-1).0)  <(git rev-list --first-parent x.y.0) | sed -ne 's/^ //p' | head -1`.. --no-merges --pretty=short --oneline | sed -e "s/^[0-9a-z]* //" | grep -e '^FIX\|NEW' | sort -u | sed 's/FIXED:/FIX:/g' | sed 's/FIXED :/FIX:/g' | sed 's/FIX :/FIX:/g' | sed 's/FIX /FIX: /g' | sed 's/NEW :/NEW:/g' | sed 's/NEW /NEW: /g' > /tmp/changelogtocopy
```

To generate a changelog of a **maintenance version** x.y.z, you can do

```
cd ~/git/dolibarr_x.y
git log x.y.(z-1)..   | sed -e "s/^[0-9a-z]* //" | grep -e '^FIX\|NEW' | sort -u | sed 's/FIXED:/FIX:/g' | sed 's/FIXED :/FIX:/g' | sed 's/FIX :/FIX:/g' | sed 's/FIX /FIX: /g' | sed 's/NEW :/NEW:/g' | sed 's/NEW /NEW: /g' > /tmp/changelogtocopy
```

- Recopy the content of the output file into the file ChangeLog.
  
  Note: To know the number of lines changes: git diff --shortstat vA vB

- Update version number with x.y.z in file htdocs/filefunc.inc.php

- Commit all changes and push the changes (direct commit or PR) and check that CI is green after the push.

- Run makepack-dolibarr.pl with option 0 to generate the signature file and all the packages (or run the option 1 alone and then option of each packages you want to build).

- Check content of built packages (the files must have a relative dir "dolibarr-x.y.z/..." and the filelist-x.y.z.xml should be inside the packages too.

- Run makepack-dolibarr.pl again with option 98 to publish files on dolibarr foundation server (Dir /home/dolibarr/wwwroot/files/stable on www.dolibarr.org).

- Run makepack-dolibarr.pl again with option 99 to publish files on sourceforge. This will also add the official tag x.y.z.

- Post a news message in dolibarr.org web site by cloning a past news + relay the news url on social networks


### On Windows

The DoliWamp package can be built from Linux (see the prerequisites on Linux above, the makepack script calls ISCC.exe through Wine) or from Windows. And only when the build of the other packages has been generated.

Once prerequisites are solved, just run the script *makepack-dolibarr.pl* with option to build the .EXE. You should get the Dolibarr.exe DoliWamp package.
