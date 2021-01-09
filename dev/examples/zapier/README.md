# HOW TO BUILD


## ENABLE MODULE ZAPIER ON DOLIBARR

This should also enable the module API (required for authentication by Zapier service and to execute action in Dolibarr by Zapier).

Create the Dolibarr login that will be used by Zapier to call APIs. Give the login the permissions on the action you plan to automate.


## CREATE A ZAPIER DEVELOPPER ACCOUNT

At first, you need to have a Zapier developper acoount, create it here [Zapier Platform](https://developer.zapier.com/)


## INSTALL ZAPIER COMMAND LINE TOOLS WITH LINK TO ZAPIER ONLINE ACCOUNT

### Install Node.js

An easy option to get set up with Node.js is to visit [https://nodejs.org/en/download/](https://nodejs.org/en/download/) and download the official installer for your OS. If you're installing with a package manager it's even easier.

After installation, confirm that Node.js is ready to use:
  `node --version`

### Install the Zapier CLI

Next let's install the Zapier CLI tools. The CLI will allow you to build your app, deploy it to the Zapier platform, do local testing, manage users and testers, view remote logs, collaborate with your team, and more:

  `cd dev/examples/zapier`

  `npm install -g zapier-platform-cli` to install the CLI globally

  `zapier --version` to return version of the CLI

### Run Zapier Login

Let's configure authentication between your dev environment and the Zapier platform. You'll use the email address and password you use to log in to the Zapier application.

  `zapier login`

This command will set up a .zapierrc file in your home directory.

### Install the Project

In zapier example directory, run:

  `cd dev/examples/zapier`

  `npm install`

### Deploying your App

Let's deploy it! When you're ready to try your code out on the Zapier platform use the push command. Only you will be able to see the app until you invite testers.

  `zapier register`   (the first time, choose name for example "Dolibarr")
  
  `zapier push`

After a push, the Application, with the name you defined during the register step, is available when creating a Zap.

You will find original tutorial here : [https://zapier.com/developer/start/introduction](https://zapier.com/developer/start/introduction)


### Create a Zap

Create a ZAP that use the application you registered.
For authentication, you must enter the login / pass of account used by Zapier to call APIs.

