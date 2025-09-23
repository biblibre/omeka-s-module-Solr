// ***********************************************************
// This example support/e2e.js is processed and
// loaded automatically before your test files.
//
// This is a great place to put global configuration and
// behavior that modifies Cypress.
//
// You can change the location of this file or turn off
// automatically serving support files with the
// 'supportFile' configuration option.
//
// You can read more here:
// https://on.cypress.io/configuration
// ***********************************************************

// Import commands.js using ES2015 syntax:
import './commands'

beforeEach(() => {
  /**
   * We have a network error in our tests (that leads to a console error) trying to load this font.
   * It doesn't do anything for our tests but not sure how to correct the issue.
   * There is an issue in Github for this: https://github.com/cypress-io/cypress/discussions/29302
   *
   * The easiest workaround I've found is to intercept the request and return an empty response.
   */
  cy.intercept('**/fonts/FiraCode-VF.woff2', {});
});
