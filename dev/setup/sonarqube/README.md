= Install SonarQube locally

Check you are using Install Java SDK 17
java --version must show 61

To install java sdk 17 on ubuntu:
sudo apt update
sudo apt install -y openjdk-17-jdk

Unzip the sonar package into a directory

Edit the file conf/sonar.properties to modify port 9000 and 9001 (already used by Eclipse or xdebug) into 9080 and 9081

Launch sonar with
bin/linux*/sonar.sh console
