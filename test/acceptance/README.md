# Run End-to-End Tests

### Run Selenium

Create a working directory

  `mkdir selenium; cd selenium;`
  
Selenium has been used for automating the browser.
  
[Download](https://www.selenium.dev/downloads/) the `latest stable version` of the `Selenium standalone server JAR file`.

  `wget https://selenium-release.storage.googleapis.com/3.141/selenium-server-standalone-3.141.59.jar`
  
Also [download](https://chromedriver.chromium.org/downloads) the `latest stable version` of `Chrome Driver`.

Once you have downloaded Chrome Driver, you need to unzip it by running the following command:

  `wget https://chromedriver.storage.googleapis.com/86.0.4240.22/chromedriver_linux64.zip`
  `unzip chromedriver_linux64.zip`
    
Once you have unzipped it, you need to move the *chromedriver* file (shared library) and place it inside the same folder where you have placed the Selenium standalone server file.

Now we can run selenium by two ways:

* Start selenium server with a command which usually looks like:

   `java -jar selenium-server-standalone-*.jar -port 4444`

* Or run selenium in docker with

   `docker run -d -p 4444:4444 -p 5900:5900 -v /dev/shm:/dev/shm selenium/standalone-chrome-debug`
   
   or `docker run -d --network="host" -v /dev/shm:/dev/shm selenium/standalone-chrome-debug`

   or `docker run -d --network host -v /dev/shm:/dev/shm selenium/standalone-chrome-debug`

### Run the acceptance tests 

* Install *yarn*. For example on Ubuntu:

   ```
   sudo apt install yarnpkg
   ```

* Install *npm* tools to manage *nodejs* libraries. For example on Ubuntu:

   ```
   apt install npm
   ```
   
* Go into the git local repository of the Dolibarr version to test.

   ```
   cd ~/git/dolibarr
   npm install cucumber nightwatch-api nightwatch
   npm update
   ```

* Copy the file *nightwatch.conf.js* inside the root directory of the project and inside this configuration file set the following environment variable. We can change the default values according to our local configuration.

   ```
    const admin_username = process.env.ADMIN_USERNAME || 'admin';

    const admin_password = process.env.ADMIN_PASSWORD || 'password';

    const launch_url = process.env.LAUNCH_URL || 'http://localhost/dolibarr/htdocs/';
   ```

* You can run a test using following commands

  `LAUNCH_URL='<launch_url>'; ADMIN_USERNAME='<admin_username>'; ADMIN_PASSWORD='<admin_password>';`
  
  `yarnpkg run test:e2e test/acceptance/features/<feature_file>`
   
  For example: `yarnpkg run test:e2e test/acceptance/features/WebUI/addUsers.feature`
 
  Note: The script to run all the acceptance tests is specified in `scripts` object of `package.json` file inside the project's root directory as :
 
  `"test:e2e": "node_modules/cucumber/bin/cucumber-js --require test/acceptance/index.js --require test/acceptance/stepDefinitions -f node_modules/cucumber-pretty"`
     
  After you run the above command you can see the test running. For that : 
  
* open `Remmina` (Remmina is a Remote Desktop Client and comes installed with Ubuntu)
  
* choose `VNC` and enter `localhost` on the address bar
  
* enter `secret` as the password
