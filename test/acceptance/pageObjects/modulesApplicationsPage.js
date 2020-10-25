const util = require('util')
module.exports = {
	url: function () {
		return this.api.launchUrl + 'admin/admin/modules.php?mainmenu=home';
	},

	commands:
		{
			browsedToModulesApplicationsPage: function () {
				return this.useXpath()
					.waitForElementVisible('@modulesApplicationsPageTitle');
			},
			moduleIsAutoEnabled: function (module) {
				const requiredActivatedModule = this.elements.moduleContainer.selector +
					util.format(this.elements.specificModule.selector, module) +
				    this.elements.activatedModule.selector;
				return this.useXpath()
					.waitForElementVisible(requiredActivatedModule);
			},
			assertNumberOfActivatedModules: function (number) {
				const requiredNumberOfActivatedModules = util.format(
					this.elements.numberOfActivatedModules.selector,
					number);
				console.log(requiredNumberOfActivatedModules);
				return this.useXpath()
					.waitForElementVisible(requiredNumberOfActivatedModules)
				    .useCss();

			},
			enableModules: async function (modules) {
				for (let module of modules) {
					const enableButton = this.elements.moduleContainer.selector +
						util.format(this.elements.specificModule.selector, module.modules) +
						this.elements.moduleEnableButton.selector;
					await this.useXpath()
						.waitForElementVisible(enableButton)
						.click(enableButton)
						.useCss();
				}
			},
			assertModulesDisplayedInNavBar: async function (modules) {
				for (let module of modules) {
					let moduleInNavBar = util.format(
						this.elements.moduleInNavBar.selector,
						module.modules);
					await this.useXpath()
						.waitForElementVisible(moduleInNavBar)
						.useCss();
				}
			}
		},
	elements:
		{
			modulesApplicationsPageTitle: {
				selector: '//div//tr[@class="titre"]//div[.="Modules/Application setup"]',
				locateStrategy: 'xpath'
			},
			moduleContainer: {
				selector:
					'//div[@class="box-flex-container"]//div[contains(@class,"info-box-content info-box-text-module")]',
			    locateStrategy: 'xpath'
			},
			specificModule : {
				selector: '/span[.="%s"]',
				locateStrategy: 'xpath'
			},
			activatedModule: {
				selector: '/../div[contains(@class,"info-box-actions")]//span[@title="Required"]',
				locateStrategy: 'xpath'
			},
			numberOfActivatedModules: {
				selector: '//div/span[.="Activated modules"]/following-sibling::b[@class="largenumber"][.="%d / 74"]',
				locateStrategy: 'xpath'
			},
			moduleEnableButton: {
				selector: '/../div[contains(@class,"info-box-actions")]//span[@title="Disabled"]',
				locateStrategy: 'xpath'
			},
			moduleInNavBar: {
				selector: '//li[@class="tmenu"]//a[@class="tmenu"][@title="%s"]'
			}
		}

};

