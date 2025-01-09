module.exports = {
    url: function () {
        return this.api.launchUrl + 'admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete';
    },

    commands: [
        {
            browsedToNewUserPage: function () {
                return this.useXpath()
                    .waitForElementVisible('@usersAndGroups')
                    .click('@usersAndGroups')
                    .waitForElementVisible('@newUser')
                    .click('@newUser')
                    .useCss();
            },

            browsedToListOfUsers: function () {
                return this.useXpath()
                    .waitForElementVisible('@usersAndGroups')
                    .click('@usersAndGroups')
                    .waitForElementVisible('@listOfUsers')
                    .click('@listOfUsers')
                    .useCss();
            }
        }
    ],

    elements: {
        usersAndGroups: {
            selector: '//div[@class="menu_titre"]/a[@title="Users & Groups"]',
            locateStrategy: 'xpath'
        },

        newUser: {
            selector: '//div[@class="menu_contenu menu_contenu_user_card"]/a[@title="New user"]',
            locateStrategy: 'xpath'
        },

        listOfUsers: {
            selector: '//a[@class="vsmenu"][@title="List of users"]',
            locateStrategy: 'xpath'
        }
    }
};
