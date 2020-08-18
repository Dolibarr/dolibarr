const util = require('util')
module.exports = {
    url: function () {
        return this.api.launchUrl + "user/card.php?leftmenu=users&action=create";
    },

    commands: [
        {
            adminCreatesUser: async function (dataTable) {
                const userDetails = dataTable.rowsHash()
                let administrator = userDetails['administrator']
                let gender = userDetails['gender']
                await this.useXpath()
                    .waitForElementVisible('@NewuserAddOption')
                    .waitForElementVisible('@newUserLastNameField')
                    .clearValue('@newUserLastNameField')
                    .setValue('@newUserLastNameField', userDetails['lastname'])
                    .waitForElementVisible('@newUserLoginField')
                    .clearValue('@newUserLoginField')
                    .setValue('@newUserLoginField', userDetails['login'])
                    .waitForElementVisible('@newUserPassword')
                    .clearValue('@newUserPassword')
                    .setValue('@newUserPassword', userDetails['password'])
                if (userDetails['administrator']) {
                    const admin = util.format(this.elements.administratorSelectOption.selector, administrator)
                    await this.useXpath()
                        .waitForElementVisible('@administratorField')
                        .click('@administratorField')
                        .waitForElementVisible(admin)
                        .click(admin)
                }
                if (userDetails['gender']) {
                    const genderValue = util.format(this.elements.genderSelectOption.selector, gender)
                    await this.useXpath()
                        .waitForElementVisible('@genderField')
                        .click('@genderField')
                        .waitForElementVisible(genderValue)
                        .click(genderValue)
                }
                return this.waitForElementVisible('@createUserButton')
                    .click('@createUserButton')
                    .useCss()
            },

            newUserCreation: async function (lastname) {
                await this.useXpath()
                    .waitForElementVisible('@newUserCreated')
                    .expect.element('@newUserCreated')
                    .text.to.equal(lastname)
                return this.useCss()
            },

            messageDisplayed: async function (message) {
                await this.useXpath()
                    .waitForElementVisible('@noPermissionDefinedMessage')
                    .expect.element('@noPermissionDefinedMessage')
                    .text.to.equal(message)
                return this.useCss()
            },

            messageNotDisplayed: function (message) {
                return this.useXpath()
                    .waitForElementNotPresent('@noPermissionDefinedMessage')
            },

            userNotCreated: async function (message) {
                await this.waitForElementVisible('@NewuserAddOption')
            }
        }
    ],

    elements: {
        adminUsername: {
            selector: 'dolibarr'
        },
        adminPassword: {
            selector: 'password'
        },
        administratorField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//select[@id="admin"]',
            locateStrategy: 'xpath'
        },
        administratorSelectOption: {
            selector: '//select[@id = "admin"]/option[.="%s"]',
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
        NewuserAddOption: {
            selector: '.fiche'
        },
        newUserLastNameField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//input[@id="lastname"]',
            locateStrategy: 'xpath'
        },
        newUserLoginField: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//input[@name="login"]',
            locateStrategy: 'xpath'
        },
        newUserPassword: {
            selector: '//table[@class="border centpercent"]/tbody/tr/td//input[@name="password"]',
            locateStrategy: 'xpath'
        },
        createUserButton: {
            selector: '//div[@class= "center"]/input[@class = "button"]',
            locateStrategy: 'xpath'
        },
        newUserCreated: {
            selector: '//div[contains(@class,"valignmiddle")]//div[contains(@class,"inline-block floatleft valignmiddle")]',
            locateStrategy: 'xpath'
        },
        noPermissionDefinedMessage: {
            selector: '//div[@class="jnotify-message"]',
            locateStrategy: 'xpath'
        }
    }
}