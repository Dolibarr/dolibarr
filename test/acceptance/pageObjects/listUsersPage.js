const util = require('util')
module.exports = {
    url: function () {
        return this.api.launchUrl + "user/list.php?leftmenu=users";
    },

    commands: [
        {
            userShouldBeDisplayed: async function (dataTable) {
                const userlists = dataTable.hashes()
                this.useXpath()
                for (const element of userlists) {
                    let login = element['login']
                    let lastname = element['lastname']
                    const detail = util.format(this.elements.userList.selector, login, lastname)
                    await this.waitForElementVisible('@usersRow')
                        .waitForElementVisible(detail)
                }
                return this.useCss()
            },

            totalNumberOfUsers: function (number) {
                const userCount = util.format(this.elements.noOfUsers.selector, number)
                return this.useXpath()
                    .waitForElementVisible(userCount)
                    .useCss()
            }
        }
    ],

    elements: {
        usersRow: {
            selector: '//table[@class="tagtable liste"]/tbody/tr[position()>2]',
            locateStrategy: 'xpath'
        },
        noOfUsers: {
            selector: '//div[contains(@class, "titre inline-block") and contains(., "List of users")]/span[.="(%d)"]',
            locateStrategy: 'xpath'
        },
        userList: {
            selector: '//table[contains(@class, "tagtable liste")]/tbody/tr[position()>2]/td/a//span[@class=" nopadding usertext"][.="%s"]/../../following-sibling::td[.="%s"]',
            locateStrategy: 'xpath'
        }
    }
}