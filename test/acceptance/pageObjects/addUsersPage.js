const util = require('util');
module.exports = {
    url: function () {
        return this.api.launchUrl + 'user/card.php?leftmenu=users&action=create';
    },

    commands: [
        {
            adminCreatesUser: async function (dataTable) {
                const userDetails = dataTable.rowsHash();
                let administrator = userDetails['administrator'];
                let gender = userDetails['gender'];
                await this.waitForElementVisible('@newUserAddOption')
                    .useXpath()
                    .waitForElementVisible('@lastnameField')
                    .clearValue('@lastnameField')
                    .setValue('@lastnameField', userDetails['last name'])
                    .waitForElementVisible('@loginField')
                    .clearValue('@loginField')
                    .setValue('@loginField', userDetails['login'])
                    .waitForElementVisible('@newUserPasswordField')
                    .clearValue('@newUserPasswordField')
                    .setValue('@newUserPasswordField', userDetails['password']);

                if (userDetails['administrator']) {
                    const admin = util.format(this.elements.administratorSelectOption.selector, administrator);
                    await this.waitForElementVisible('@administratorField')
                        .click('@administratorField')
                        .waitForElementVisible(admin)
                        .click(admin);
                }

                if (userDetails['gender']) {
                    const genderValue = util.format(this.elements.genderSelectOption.selector, gender)
                    await this.waitForElementVisible('@genderField')
                        .click('@genderField')
                        .waitForElementVisible(genderValue)
                        .click(genderValue);
                }
                return this.waitForElementVisible('@submitButton')
                    .click('@submitButton')
                    .useCss();
            },

            noPermissionMessage: async function (message) {
                await this.useXpath()
                    .waitForElementVisible('@noPermissionDefinedMessage')
                    .expect.element('@noPermissionDefinedMessage')
                    .text.to.equal(message);
                return this.useCss();
            },

            newUserShouldBeCreated: async function (lastname) {
                await this.useXpath()
                    .waitForElementVisible('@newUserCreated')
                    .expect.element('@newUserCreated')
                    .text.to.equal(lastname);
                return this.useCss();
            },

            noPermissionDefinedMessageNotShown: function (message) {
                return this.useXpath()
                    .waitForElementNotPresent('@noPermissionDefinedMessage')
                    .useCss();
            },

            userNotCreated: function (lastname) {
                return this.waitForElementVisible('@newUserAddOption');
            }
        }
    ],

    elements: {
        newUserAddOption: {
            selector: '.fiche'
        },

        lastnameField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//input[@id="lastname"]',
            locateStrategy: 'xpath'
        },

        loginField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//input[@name="login"]',
            locateStrategy: 'xpath'
        },

        newUserPasswordField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//input[@name="password"]',
            locateStrategy: 'xpath'
        },

        submitButton: {
            selector: '//div[@class="center"]/input[@class="button"]',
            locateStrategy: 'xpath'
        },

        administratorField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//select[@id="admin"]',
            locateStrategy: 'xpath'
        },

        administratorSelectOption: {
            selector: '//select[@id="admin"]/option[.="%s"]',
            locateStrategy: 'xpath'

        },

        genderField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//select[@id="gender"]',
            locateStrategy: 'xpath'
        },
        genderSelectOption: {
            selector: '//select[@id="gender"]/option[.="%s"]',
            locateStrategy: 'xpath'
        },

        noPermissionDefinedMessage: {
            selector: '//div[@class="jnotify-message"]',
            locateStrategy: 'xpath'
        },

        newUserCreated: {
            selector: '//div[contains(@class,"valignmiddle")]//div[contains(@class,"inline-block floatleft valignmiddle")]',
            locateStrategy: 'xpath'
        }
    }
};
