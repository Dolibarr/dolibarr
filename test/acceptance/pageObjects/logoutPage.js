module.exports = {
    url: function () {
        return this.api.launchUrl + "admin/index.php?mainmenu=home&leftmenu=setup&mesg=setupnotcomplete";
    },

    commands: [
        {
            userOpensTheUserProfile: function () {
                return this.useXpath()
                    .waitForElementVisible('@userProfileDropdown')
                    .click('@userProfileDropdown')
            },

            userLogsOut: function () {
                return this.waitForElementVisible('@logoutButton')
                    .click('@logoutButton')
            }
        }
    ],

    elements: {
        userProfileDropdown: {
            selector: '#topmenu-login-dropdown'
        },
        logoutButton: {
            selector: '.pull-right'
        }
    }
}