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

describe('basic setup', () => {
    it('create basic setup, index one item and run a search', () => {
        cy.loginAsAdmin();

        // Set the check interval to 1s
        cy.visit('/admin/module/configure?id=Search');
        cy.get('[name="search_check_interval"]').clear().type('1');
        cy.get('#page-actions button').click();

        // Change the Solr node URI
        cy.visit('/admin/solr/node/1/edit');
        const solrUri = Cypress.expose('solrUri');
        cy.get('input[name="o:uri"]').clear().type(solrUri);
        cy.scrollTo('top');
        cy.get('#page-actions button').click();

        // Create a Search index
        cy.visit('/admin/search/index/add');
        cy.get('input[name="o:name"]').type('Solr');
        cy.get('select[name="o:adapter"]').select('solr');
        cy.get('#page-actions button').click();
        cy.get('#page-actions button').click();

        // Create a Search page
        cy.visit('/admin/search/page/add');
        cy.get('input[name="o:name"]').type('Search');
        cy.get('input[name="o:path"]').type('search');
        cy.get('select[name="o:index_id"]').select('1');
        cy.get('select[name="o:form"]').select('standard');
        cy.get('#page-actions button').click();

        // Add a creator facet
        cy.intercept('/admin/search/facets/field-edit-sidebar*').as('facetFieldEditSidebar');
        cy.get('[name="o:settings[facets]"] + fieldset select[name="field_name_select"]').select('creator');
        cy.get('[name="o:settings[facets]"] + fieldset button').click();
        cy.wait('@facetFieldEditSidebar');
        cy.get('.sidebar.active input[name="facet_limit"]').type('10');
        cy.get('.sidebar.active input[name="facet_display_limit"]').type('10');
        cy.get('.sidebar.active button').click();

        // Add a relevance sort
        cy.intercept('/admin/search/sort-fields/field-edit-sidebar*').as('sortFieldsFieldEditSidebar');
        cy.get('[name="o:settings[sort_fields]"] + fieldset select[name="field_name_select"]').select('score desc');
        cy.get('[name="o:settings[sort_fields]"] + fieldset button').click();
        cy.wait('@sortFieldsFieldEditSidebar');
        cy.get('.sidebar.active button').click();

        cy.get('#page-actions button').click();

        // Create a public site
        cy.visit('/admin/site/add');
        cy.get('input[name="o:title"]').type('Home');
        cy.get('#page-actions button').click();

        // Create a public item with a title and a creator
        cy.visit('/admin/item/add');
        cy.get('.field[data-property-term="dcterms:title"] textarea').type('First item');
        cy.get('#property-selector [data-vocabulary-id="1"]').click();
        cy.get('#property-selector [data-vocabulary-id="1"] [data-property-term="dcterms:creator"]').click();
        cy.get('.field[data-property-term="dcterms:creator"] a[data-type="literal"]').click();
        cy.get('.field[data-property-term="dcterms:creator"] textarea').type('First creator');
        cy.get('#page-actions button').click();

        // Wait 1s so that the next visit triggers the sync job
        cy.wait(1000);
        cy.visit('/admin/job');
        cy.get('table tbody tr').should('have.length', 1);

        // Wait 1s again so that the job has enough time to finish
        cy.wait(1000);

        // Use the search form as an anonymous user
        cy.logout();
        cy.visit('/s/home/search');
        cy.get('#content form').submit();

        // There should be one result and one facet block containing one item
        cy.get('.search-results .resource-list .resource').should('have.length', 1);
        cy.get('.search-facets .search-facet').should('have.length', 1);
        cy.get('.search-facets .search-facet .search-facet-items .search-facet-item').should('have.length', 1);
    });
})
