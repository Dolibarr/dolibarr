module.exports = {
	url: function () {
		return this.api.launchUrl + 'admin/admin/modules.php?mainmenu=home';
	},

	commands:
		{
			browsedToModulesApplicationsPage: function () {
				return this.useXpath()
					.waitForElementVisible('@modulesApplicationPageTitle');
			}
		},
	elements:
		{
			modulesApplicationsPageTitle: {
				selector: '//div//tr[@class="titre"]//div[.="Modules/Application setup"]',
				locateStrategy: 'xpath'
			},
			moduleContainer: {
				selector:'//div[@class="box-flex-container"]/div[@class="info-box-content info-box-text-module"]' // jshint ignore:line
			}
		}

};

