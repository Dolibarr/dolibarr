module.exports = {
    url: function () {
        return this.api.launchUrl + 'admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete';
    },

    commands:
        [
            {
                userOpensProfile: async function () {
                    await this.useXpath()
                        .waitForElementVisible('@userProfileDropdown')
                        .click('@userProfileDropdown')
                    return this.useCss();
                },

                userLogsOut: function () {
                    return this.waitForElementVisible('@logoutButton')
                        .click('@logoutButton');
                }
            }
        ],

    elements: {

        logoutButton: {
            selector: '.pull-right'
        },

        userProfileDropdown: {
            selector: '//div[@id="topmenu-login-dropdown"]',
            locateStrategy: 'xpath'
        }
    }
};
