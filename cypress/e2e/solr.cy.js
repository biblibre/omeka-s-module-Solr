function createItem(title, date, authors, hidden = false)
{
    cy.get('#menu .items').click();
    cy.get('#page-actions .button').click();
    cy.get('#properties [data-property-term="dcterms:title"] .inputs textarea').click();
    cy.get('#properties [data-property-term="dcterms:title"] .inputs textarea').type(title);
    cy.get('#property-selector').contains('Dublin Core').click();
    if (date != '')
    {
      cy.get('#property-selector [data-child-search="Date"] .selectable').click();
    }
    if (authors.length > 0)
    {
      cy.get('#property-selector [data-child-search="Creator"] .selectable').click();
    }
    if (date != '')
    {
      cy.get('#properties [data-property-term="dcterms:date"] .button.add-value[data-type="literal"]').click();
      cy.get('#properties [data-property-term="dcterms:date"] .inputs textarea').click();
      cy.get('#properties [data-property-term="dcterms:date"] .inputs textarea').type(date);
    }
    if (authors.length > 0)
    {
        for (let i = 0; i < authors.length; i++)
        {
          cy.get('#properties [data-property-term="dcterms:creator"] .button.add-value[data-type="literal"]').click();
          cy.get('#properties [data-property-term="dcterms:creator"] .inputs textarea').eq(i).click();
          cy.get('#properties [data-property-term="dcterms:creator"] .inputs textarea').eq(i).type(authors[i])
        }
    }
    if (hidden)
    {
      cy.get('.button[title="Make private"]').click();
    }
    cy.get('#page-actions [name="add-item-submit"]').click();
}

function verifyItemsInOrder(itemNames, strict, orderMatters)
{
  for (let i = 0; i < itemNames.length; i++)
  {
    if (orderMatters)
    {
      cy.get('.items.resource').eq(i).contains(itemNames[i]).should('exist');
    }
    else
    {
      cy.get('.items.resource').contains(itemNames[i]).should('exist');
    }
  }
  if (strict)
  {
     cy.get('.items.resource').should('have.length', itemNames.length);
  }
}

function registerAndLogin()
{
    cy.get('[name="user[email]"]').click();
    cy.get('[name="user[email]"]').clear();
    cy.get('[name="user[email]"]').type('cypress@admin.com');
    cy.get('[name="user[email-confirm]"]').click();
    cy.get('[name="user[email-confirm]"]').clear();
    cy.get('[name="user[email-confirm]"]').type('cypress@admin.com');
    cy.get('[name="user[name]"]').click();
    cy.get('[name="user[name]"]').clear();
    cy.get('[name="user[name]"]').type('cypress');
    cy.get('[name="user[password-confirm][password]"]').click();
    cy.get('[name="user[password-confirm][password]"]').clear();
    cy.get('[name="user[password-confirm][password]"]').type('cypress');
    cy.get('[name="user[password-confirm][password-confirm]"]').click();
    cy.get('[name="user[password-confirm][password-confirm]"]').clear();
    cy.get('[name="user[password-confirm][password-confirm]"]').type('cypress');
    cy.get('[name="settings[installation_title]"]').click();
    cy.get('[name="settings[installation_title]"]').clear();
    cy.get('[name="settings[installation_title]"]').type('cypress');
    cy.get('#installationform [name="submit"]').click();
    cy.get('[name="email"]').click();
    cy.get('[name="email"]').clear();
    cy.get('[name="email"').type('cypress@admin.com');
    cy.get('[name="password"]').clear();
    cy.get('[name="password"]').type('cypress');
    cy.get('#loginform [name="submit"]').click();
}

describe('Solr', () =>
{
  it('passes', function() {
    cy.on('uncaught:exception', (err, runnable) => {
        // omekas/install has a weird exception that will fail the test
        // https://docs.cypress.io/api/cypress-api/catalog-of-events#Uncaught-Exceptions

        // using mocha's async done callback to finish
        // this test so we prove that an uncaught exception
        // was thrown

        // return false to prevent the error from
        // failing this test
        return false;
    });

    cy.visit('http://omekas');

    registerAndLogin();

    cy.get('#menu .modules').click();
    cy.get('#modules [action="/admin/module/install?id=Solr"] [name="id"]').click();
    cy.get('#modules [action="/admin/module/deactivate?id=Solr"] [name="id"]').should('exist');
    cy.get('#menu [href="/admin/solr"]').click();
    cy.get('[title="Edit"]').click();
    cy.get('#content [name="o:uri"]').click();
    cy.get('#content [name="o:uri"]').clear();
    cy.get('#content [name="o:uri"]').type('http://127.0.0.1:8983/solr/default');
    cy.get('#content [name="o:settings[qf]"]').click();
    cy.get('#content [name="o:settings[qf]"]').clear();
    cy.get('#content [name="o:settings[qf]"]').type('dcterms_title_txt dcterms_date_txt dcterms_creator_ss');
    cy.get('#page-actions button').click();
    cy.contains('.tablesaw-cell-content', 'OK').should('exist');
    cy.get('[title="Configure indexation fields"]').click();
    cy.contains('.tablesaw-cell-content', 'Item').find('[title="Rules"]').click();
    cy.get('#page-actions .button').click();
    cy.contains('.field', 'Source').within(() => {
      cy.root().get('.chosen-single').click();
      cy.root().get('.chosen-search-input').click();
      cy.root().get('.chosen-search-input').clear();
      cy.root().get('.chosen-search-input').type('dcterms:creator');
    });
    cy.contains('.active-result', 'dcterms:creator').click();
    cy.contains('.field', 'Solr field').within(() => {
      cy.root().get('#field_selector_chosen').click();
      cy.root().get('.chosen-search-input').click();
      cy.root().get('.chosen-search-input').clear();
      cy.root().get('.chosen-search-input').type('ss (strings)');
      cy.root().get('.active-result').click();
    });
    cy.get('#page-actions button').click();
    cy.get('#menu [href="/admin/search"]').click();
    cy.contains('.button', "Add new index").click();
    cy.get('[name="o:name"]').click();
    cy.get('[name="o:name"]').clear();
    cy.get('[name="o:name"]').type('Solr');
    cy.get('[name="o:adapter"]').select('solr');
    cy.get('#page-actions button').click();
    cy.get('#page-actions button').click();
    cy.get('#menu .sites').click();
    cy.get('#page-actions .button').click();
    cy.get('[name="o:title"]').click();
    cy.get('[name="o:title"]').clear();
    cy.get('[name="o:title"]').type('test');
    cy.get('[name="o:slug"]').click();
    cy.get('[name="o:slug"]').clear();
    cy.get('[name="o:slug"]').type('test');
    cy.get('#page-actions button').click();

    cy.get('#menu [href="/admin/search"]').click();
    cy.get('#page-actions [href="/admin/search/page/add"]').click();
    cy.get('#content [name="o:name"]').click();
    cy.get('#content [name="o:name"]').clear();
    cy.get('#content [name="o:name"]').type('solr');
    cy.get('#content [name="o:path"]').clear();
    cy.get('#content [name="o:path"]').type('solr');
    cy.get('#content [name="o:index_id"]').select('1');
    cy.get('#content [name="o:form"]').select('standard');
    cy.get('#page-actions button').click();
    cy.get('#content [name="o:settings[facet_limit]"]').click();
    cy.get('#content [name="o:settings[facet_limit]"]').clear();
    cy.get('#content [name="o:settings[facet_limit]"]').type('2');
    cy.get('#content [name="o:settings"] > div:nth-child(3) > .inputs').click();
    cy.get('#content [data-field-list-url="/admin/search/facets/field-list?search_page_id=1"] [name="field_name_select"]').select('creator');
    cy.get('#content [data-field-list-url="/admin/search/facets/field-list?search_page_id=1"] .fields-field-add-button').click();
    cy.get('#fields-field-set-button').click();
    cy.get('#content [data-field-list-url="/admin/search/sort-fields/field-list?search_page_id=1"] [name="field_name_select"]').select('date asc');
    cy.get('#content [data-field-list-url="/admin/search/sort-fields/field-list?search_page_id=1"] .fields-field-add-button').click();
    cy.get('#fields-field-set-button').click();
    cy.get('#content [data-field-list-url="/admin/search/sort-fields/field-list?search_page_id=1"] [name="field_name_select"]').select('date desc');
    cy.get('#content [data-field-list-url="/admin/search/sort-fields/field-list?search_page_id=1"] .fields-field-add-button').click();
    cy.get('#fields-field-set-button').click();
    cy.get('#content [data-field-list-url="/admin/search/search-fields/field-list?search_page_id=1"] [name="field_name_select"]').select('title');
    cy.get('#content [data-field-list-url="/admin/search/search-fields/field-list?search_page_id=1"] .fields-field-add-button').click();
    cy.get('#fields-field-set-button').click();
    cy.get('#page-actions button').click();  

    createItem('Bonjour', '2001-01-02', ['A']);
    createItem('Bonjour2', '2001-01-01', []);
    createItem('Au Revoir', '2001-01-03', []);
    createItem('Au Revoir 2', '', ['B']);
    createItem('Auteurs', '', ['A', 'B']);
    createItem('Hidden', '', [], true);

    cy.get('#menu [href="/admin/search"]').click();
    cy.get('.o-icon-[title="Rebuild index"]').click();
    cy.contains('.field', 'Clear index').get('input[type="checkbox"]').click();
    cy.get("#page-actions button").click();
    cy.wait(5000);
    cy.get('li.success a').click();
    cy.contains('.meta-group', 'Status').contains('Completed');

    cy.visit('http://omekas/s/test/solr');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    // Date Asc by default, then alphabetical order
    verifyItemsInOrder(['Bonjour2', 'Bonjour', 'Au Revoir'], false);
    verifyItemsInOrder(['Bonjour2', 'Bonjour', 'Au Revoir', 'Auteurs', 'Au Revoir 2', 'Hidden'], true, false);
  
    cy.get('select[name="sort"]').select('Date Desc');
    verifyItemsInOrder(['Au Revoir', 'Bonjour', 'Bonjour2'], false);

    cy.visit('http://omekas/s/test/solr');
    cy.get('select.search-form-standard-filter-field').eq(0).select('Title');
    cy.get('select.search-form-standard-filter-operator').eq(0).select('contains any word');
    cy.get('input.search-form-standard-filter-term').eq(0).click();
    cy.get('input.search-form-standard-filter-term').eq(0).clear();
    cy.get('input.search-form-standard-filter-term').eq(0).type('Bonjour');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder(['Bonjour'], true);

    cy.visit('http://omekas/s/test/solr');
    cy.get('select.search-form-standard-filter-field').eq(0).select('Title');
    cy.get('select.search-form-standard-filter-operator').eq(0).select('contains any word');
    cy.get('input.search-form-standard-filter-term').eq(0).click();
    cy.get('input.search-form-standard-filter-term').eq(0).clear();
    cy.get('input.search-form-standard-filter-term').eq(0).type('Au Revoir');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder(['Au Revoir', 'Au Revoir 2'], true, false);

    cy.visit('http://omekas/s/test/solr');
    cy.get('select.search-form-standard-filter-field').eq(0).select('Title');
    cy.get('select.search-form-standard-filter-operator').eq(0).select('matches pattern');
    cy.get('input.search-form-standard-filter-term').eq(0).click();
    cy.get('input.search-form-standard-filter-term').eq(0).clear();
    cy.get('input.search-form-standard-filter-term').eq(0).type('Bonjour*');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder(['Bonjour', 'Bonjour2'], true, false);

    cy.visit('http://omekas/s/test/solr');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    cy.contains('li.search-facet-item', 'A').contains('A').click();

    verifyItemsInOrder(['Bonjour', 'Auteurs'], true, false);

    cy.visit('http://omekas/s/test/solr');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    cy.contains('li.search-facet-item', 'B').contains('B').click();

    verifyItemsInOrder(['Au Revoir 2', 'Auteurs'], true, false);

    cy.contains('li.search-facet-item', 'A').contains('A').click();

    verifyItemsInOrder(['Auteurs'], true, false);

    cy.get('[href="/logout"]').eq(0).click();

    cy.visit('http://omekas/s/test/solr');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder(['Bonjour2', 'Bonjour', 'Au Revoir', 'Auteurs', 'Au Revoir 2'], true, false);
  });
});
