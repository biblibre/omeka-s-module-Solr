const { defineConfig } = require('cypress');
const fs = require('node:fs');
const path = require('node:path');
const process = require('node:process');

module.exports = defineConfig({
    allowCypressEnv: false,
    env: {
        adminEmail: 'admin@example.com',
        adminPassword: 'root',
    },
    expose: {
        omekaLang: process.env.OMEKA_LANG ?? '',
    },
    viewportHeight: 720,
    viewportWidth: 1280,
    e2e: {
        supportFile: false,
        baseUrl: 'http://localhost/',
        setupNodeEvents(on, config) {
            on('after:screenshot', (details) => {
                if (!details.name) {
                    return;
                }

                const lang = process.env.OMEKA_LANG;
                const filename = details.name + (lang ? `.${lang}` : '') + '.png';
                const dest = path.join(path.dirname(__dirname), filename);
                const dirname = path.dirname(dest);
                fs.mkdirSync(dirname, { recursive: true });
                fs.copyFileSync(details.path, dest);
            });

            on('before:browser:launch', (browser, launchOptions) => {
                if (browser.name === 'electron' && browser.isHeadless) {
                  launchOptions.preferences.width = 1920
                  launchOptions.preferences.height = 1080
                }

                return launchOptions
            });
        },
    },
});
