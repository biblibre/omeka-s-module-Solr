Cypress.Screenshot.defaults({
    capture: 'viewport',
})

Cypress.Commands.add('loginAsAdmin', () => {
    cy.env(['adminEmail', 'adminPassword']).then(env => {
        cy.visit('/login')
        cy.get('input[name="email"]').type(env.adminEmail);
        cy.get('input[name="password"]').type(env.adminPassword);
        cy.get('#loginform input[type="submit"]').click();
    });
});
Cypress.Commands.add('logout', () => {
    cy.visit('/logout');
});

describe('screenshots', () => {
    let lang;
    let strings;

    function __(key) {
        return strings[key]?.[lang] ?? strings[key]?.[''] ?? key;
    }

    before(function() {
        cy.loginAsAdmin();
        lang = Cypress.expose('omekaLang');
        cy.fixture('strings').then(_strings => { strings = _strings });
        cy.logout();
    });

    it('node configuration', () => {
        cy.loginAsAdmin();

        cy.visit('/admin/solr');
        cy.screenshot('images/node-browse');

        cy.get('.o-icon-edit').click();
        cy.screenshot('images/node-edit');

        cy.visit('/admin/solr');
        cy.get('.fa-database').click();
        cy.screenshot('images/node-mapping-browse');

        cy.get('a[href$="/mapping/items"]:eq(0)').click();
        cy.screenshot('images/node-mapping-items-browse');

        cy.visit('/admin/solr/node/1/mapping/items/add')
        cy.screenshot('images/node-mapping-items-add');

        cy.visit('/admin/solr/node/1/fields')
        cy.screenshot('images/node-fields');

        cy.visit('/admin/solr/node/1/fields/add')
        cy.screenshot('images/node-fields-add');
    })
})
