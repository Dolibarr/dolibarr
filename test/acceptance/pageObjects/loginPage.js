module.exports = {
    url: function () {
        return this.api.launchUrl;
    },

    commands: [
        {
            waitForLoginPage: function () {
                return this.waitForElementVisible('@loginTable');
            },

            userLogsInWithUsernameAndPassword: function (username, password) {
                return this.waitForElementVisible('@userNameField')
                    .setValue('@userNameField', username)
                    .waitForElementVisible('@passwordField')
                    .setValue('@passwordField', password)
                    .useXpath()
                    .waitForElementVisible('@loginButton')
                    .click('@loginButton')
                    .useCss();
            },

            successfulLogin: function () {
                return this.waitForElementNotPresent('@loginTable')
                    .waitForElementVisible('@userProfileDropdown');
            },

            userIsLoggedIn: async function (login) {
                await this.waitForElementNotPresent('@loginTable')
                    .useXpath()
                    .waitForElementVisible('@userLogin')
                    .expect.element('@userLogin')
                    .text.to.equal(login);
                return this.useCss();
            },

            unsuccessfulLogin: function () {
                return this.waitForElementVisible('@loginTable')
                    .waitForElementNotPresent('@userProfileDropdown');
            },

            loginErrorDisplayed: async function (errorMessage) {
                await this.useXpath()
                    .waitForElementVisible('@loginError')
                    .expect.element('@loginError')
                    .text.to.equal(errorMessage);
                return this.useCss();
            }
        }
    ],

    elements: {
        loginButton: {
            selector: '//div[@id="login-submit-wrapper"]/input[@type="submit"]',
            locateStrategy: 'xpath'
        },

        userNameField: {
            selector: '#username'
        },

        passwordField: {
            selector: '#password'
        },

        loginTable: {
            selector: '.login_table'
        },

        userProfileDropdown: {
            selector: '#topmenu-login-dropdown'
        },

        userLogin: {
            selector: '//div[@id="topmenu-login-dropdown"]/a//span[contains(@class,"atoploginusername")]',
            locateStrategy: 'xpath'
        },

        loginError: {
            selector: '//div[@class="center login_main_message"]/div[@class="error"]',
            locateStrategy: 'xpath'
        }
    }
};
