const { defineConfig } = require('cypress');
const fs = require('node:fs');
const path = require('node:path');
const process = require('node:process');
const child_process = require('node:child_process');

module.exports = defineConfig({
    allowCypressEnv: false,
    env: {
        adminEmail: 'admin@example.com',
        adminPassword: 'root',
    },
    expose: {
        solrUri: process.env.SOLR_URI ?? 'http://localhost:8983/solr/e2e',
    },
    viewportHeight: 720,
    viewportWidth: 1280,
    e2e: {
        supportFile: false,
        baseUrl: 'http://localhost/',
    },
});
