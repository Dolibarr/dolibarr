# Run End-to-End Tests

### Run Selenium

Selenium has been used for automating the browser.

[Download](https://www.selenium.dev/downloads/) the `latest stable version` of the `Selenium standalone server JAR file`.

Also [download](https://chromedriver.chromium.org/downloads) the `latest stable version` of `Chrome Driver`.

Once you have downloaded Chrome Driver, you need to unzip it by running the following command:

  `unzip chromedriver_linux64.zip`
    
Once you have unzipped it, you need to move the *chromedriver* file (shared library) and place it inside the same folder where you have placed the Selenium standalone server file.

Now we can run selenium by two ways:

* Start selenium server with a command which usually looks like:

   `java -jar selenium-server-standalone-<selenium version>.jar -port 4444`


* Run selenium in docker with

   `docker run -d -p 4444:4444 -p 5900:5900 -v /dev/shm:/dev/shm selenium/standalone-chrome-debug`
   
   or `docker run -d --network="host" -v /dev/shm:/dev/shm selenium/standalone-chrome-debug`

   or `docker run -d --network host -v /dev/shm:/dev/shm selenium/standalone-chrome-debug`

### Run the acceptance tests 

* Install *yarn*. For example on Ubuntu:

   ```
   apt install yarnpkg
   ```

* Install *nodejs* libraries. For example on Ubuntu:

   ```
   apt install npm
   npm install cucumber
   npm install nightwatch-api
   npm install nightwatch
   npm update
   ```

* In *nightwatch.conf.js* file inside the root directory of the project and inside the configuration file following environment variable has been specified. We can change the default values according to our local configuration.

   ```
    const admin_username = process.env.ADMIN_USERNAME || 'admin';

    const admin_password = process.env.ADMIN_PASSWORD || 'password';

    const launch_url = process.env.LAUNCH_URL || 'http://localhost/dolibarr/htdocs/';
   ```

* You can run a test using following commands

  `LAUNCH_URL='<launch_url>'; ADMIN_USERNAME='<admin_username>'; ADMIN_PASSWORD='<admin_password>';`
  
  `yarn run test:e2e test/acceptance/features/<feature_file>`
   
  For example: `yarn run test:e2e test/acceptance/features/WebUI/addUsers.feature`
 
  Note: The script to run all the acceptance tests is specified in `scripts` object of `package.json` file inside the project's root directory as :
 
  `"test:e2e": "node_modules/cucumber/bin/cucumber-js --require test/acceptance/index.js --require test/acceptance/stepDefinitions -f node_modules/cucumber-pretty"`
     
  After you run the above command you can see the test running. For that : 
  
* open `Remmina` (Remmina is a Remote Desktop Client and comes installed with Ubuntu)
  
* choose `VNC` and enter `localhost` on the address bar
  
* enter `secret` as the password
