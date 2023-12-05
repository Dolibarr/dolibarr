const util = require('util');
module.exports = {
    url: function () {
        return this.api.launchUrl + 'user/list.php?leftmenu=users';
    },

    commands: [
        {
            listOfUsersDisplayed: async function (dataTable) {
                const usersList = dataTable.hashes();
                this.useXpath();
                for (const row of usersList) {
                    let login = row['login'];
                    let lastName = row['last name'];
                    const userDetail = util.format(this.elements.userList.selector, login, lastName);
                    await this.waitForElementVisible('@userRow')
                        .waitForElementVisible(userDetail);
                }
                return this.useCss();
            },

            numberOfUsersDisplayed: async function (number) {
                const userCount = util.format(this.elements.numberOfUsers.selector, number);
                await this.useXpath()
                    .waitForElementVisible(userCount);
                return this.useCss();
            }
        }
    ],

    elements: {
        userRow: {
            selector: '//table[contains(@class,"tagtable")]/tbody/tr[position()>2]',
            locateStrategy: 'xpath'
        },

        numberOfUsers: {
            selector: '//div[contains(@class, "titre inline-block") and contains(., "List of users")]/span[.="(%d)"]',
            locateStrategy: 'xpath'
        },

        userList: {
            selector: '//table[contains(@class,"tagtable")]/tbody/tr[position()>2]/td/a//span[normalize-space(@class="nopadding usertext")][.="%s"]/../../following-sibling::td[.="%s"]',
            locateStrategy: 'xpath'
        }
    }
};
