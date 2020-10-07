module.exports = {
    url: function () {
        return this.api.launchUrl;
    },

    commands: [
        {
            waitForLoginPage: function () {
                return this.waitForElementVisible('@loginTable')
            },

            userLogsInWithUsernameAndPassword: function (username, password) {
                return this.waitForElementVisible('@usernameField')
                    .setValue('@usernameField', username)
                    .waitForElementVisible('@passwordField')
                    .setValue('@passwordField', password)
                    .useXpath()
                    .waitForElementVisible('@loginButton')
                    .click('@loginButton')
                    .useCss();
            },

            successfulLogin: function () {
                return this.waitForElementNotPresent('@loginTable')
                    .waitForElementVisible('@userProfileDropdown')
            },

            userIsLoggedIn: async function (login) {
                await this.useXpath()
                    .waitForElementVisible('@userlogin')
                    .expect.element('@userlogin')
                    .text.to.equal(login)
                return this.useCss()
            },

            unsuccessfulLogin: function () {
                return this.waitForElementVisible('@loginTable')
                    .waitForElementNotPresent('@userProfileDropdown')
            },

            errorMessageDisplay: async function (errorMessage) {
                await this.useXpath()
                    .waitForElementVisible('@loginError')
                    .expect.element('@loginError')
                    .text.to.equal(errorMessage)
                return this.useCss();
            }

            // userBrowsesToForgottenPassword: function () {
            //     return this.useXpath()
            //         .waitForElementVisible('@passwordforgottenPage')
            //         .click('@passwordforgottenPage')
            // },
            //
            // userEntersSecurityCode: async function (username) {
            //     await this.waitForElementVisible('@loginTable')
            //         .waitForElementVisible('@usernameField')
            //         .setValue('@usernameField', username)
            //         .waitForElementVisible('@securityCodeField')
            //     return this.useCss()
            // },
            //
            // messageForSecurityCode: function (codeMessage) {
            //     return this.useXpath()
            //         .waitForElementVisible('@securityCodeMessage')
            //         .expect.element('@securityCodeMessage')
            //         .text.to.equal(codeMessage)
            //
            // }
        }
    ],

    elements:
        {
            userlogin: {
                selector: '//div[@id="topmenu-login-dropdown"]/a//span[contains(@class,"atoploginusername")]',
                locateStrategy: 'xpath'
            },
            loginButton: {
                selector: '//div[@id="login-submit-wrapper"]/input[@type="submit"]',
                locateStrategy: 'xpath'
            },
            usernameField: {
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
            loginError: {
                selector: '//div[@class="center login_main_message"]/div[@class="error"]',
                locateStrategy: 'xpath'
            }
            // passwordforgottenPage: {
            //     selector: '//div[@class="center"]//descendant::a[1]',
            //     locateStrategy: 'xpath'
            // },
            // securityCodeField: {
            //     selector: '#securitycode'
            // },
            // securityCodeMessage: {
            //     selector: '//div[contains(@class, "center login_main_message")]/div[contains(@class, "ok clearboth")]/div[contains(@class, "warning paddingtopbottom")]',
            //     locateStrategy:'xpath'
            // }
            
        }
};
