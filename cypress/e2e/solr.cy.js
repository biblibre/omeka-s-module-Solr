function createItem(title, date, authors, hidden = false)
{
    cy.get('#menu .items').click();
    cy.get('#page-actions .button').click();

    cy.get('#properties [data-property-term="dcterms:title"] .inputs textarea').click();
    if (!Array.isArray(title))
    {
      cy.get('#properties [data-property-term="dcterms:title"] .inputs textarea').type(title);
    }

    if (Array.isArray(title) && title.length > 0)
    {
      cy.get('#properties [data-property-term="dcterms:title"] .inputs textarea').type(title[0]);
        for (let i = 1; i < title.length; i++)
        {
          cy.get('#properties [data-property-term="dcterms:title"] .button.add-value[data-type="literal"]').click();
          cy.get('#properties [data-property-term="dcterms:title"] .inputs textarea').eq(i).click();
          cy.get('#properties [data-property-term="dcterms:title"] .inputs textarea').eq(i).type(title[i])
        }
    }
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
    cy.get('#modules [action="/admin/module/install?id=Search"] [name="id"]').click();
    cy.get('#modules [action="/admin/module/deactivate?id=Search"] [name="id"]').should('exist');
    cy.get('#modules [action="/admin/module/install?id=Solr"] [name="id"]').click();
    cy.get('#modules [action="/admin/module/deactivate?id=Solr"] [name="id"]').should('exist');
    cy.get('#menu [href="/admin/solr"]').click();
    cy.get('[title="Edit"]').click();
    cy.get('#content [name="o:uri"]').click();
    cy.get('#content [name="o:uri"]').clear();
    cy.get('#content [name="o:uri"]').type('http://solr:8983/solr/biblibre');
    cy.get('#content [name="o:settings[qf]"]').click();
    cy.get('#content [name="o:settings[qf]"]').clear();
    cy.get('#content [name="o:settings[qf]"]').type('dcterms_title_txt dcterms_date_txt dcterms_creator_ss');
    cy.get('#content [name="o:settings[highlight][fields]"]').click();
    cy.get('#content [name="o:settings[highlight][fields]"]').clear();
    cy.get('#content [name="o:settings[highlight][fields]"]').type('dcterms_title_txt');
    cy.get('#content [type="checkbox"][name="o:settings[highlight][highlighting]"]').click();
    cy.get('#content [name="o:settings[highlight][fragsize]"]').click();
    cy.get('#content [name="o:settings[highlight][fragsize]"]').clear();
    cy.get('#content [name="o:settings[highlight][fragsize]"]').type('30');
    cy.get('#content [name="o:settings[highlight][snippets]"]').click();
    cy.get('#content [name="o:settings[highlight][snippets]"]').clear();
    cy.get('#content [name="o:settings[highlight][snippets]"]').type('2');
    cy.get('#page-actions button').click();
    cy.wait(5000);
    cy.reload(true);
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
    cy.wait(100);
    cy.get('.messages .success').should('exist');

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

    cy.contains('.field', 'Facet limit').within(() => { cy.root().get('input').click() });
    cy.contains('.field', 'Facet limit').within(() => { cy.root().get('input').clear() });
    cy.contains('.field', 'Facet limit').within(() => { cy.root().get('input').type('2') });

    cy.contains('.field', 'Facets').within(() => { cy.root().get('select').select('Creator') });
    cy.contains('.field', 'Facets').within(() => { cy.root().get('button[title="Add"]').click() });
    cy.get('#fields-field-set-button').click();

    cy.contains('.field', 'Sort fields').within(() => { cy.root().get('select').select('Date Asc') });
    cy.contains('.field', 'Sort fields').within(() => { cy.root().get('button[title="Add"]').click() });
    cy.get('#fields-field-set-button').click();

    cy.contains('.field', 'Sort fields').within(() => { cy.root().get('select').select('Date Desc') });
    cy.contains('.field', 'Sort fields').within(() => { cy.root().get('button[title="Add"]').click() });
    cy.get('#fields-field-set-button').click();

    cy.contains('.field', 'Search fields').within(() => { cy.root().get('select').select('Title') });
    cy.contains('.field', 'Search fields').within(() => { cy.root().get('button[title="Add"]').click() });
    cy.get('#fields-field-set-button').click();

    cy.contains('.field', 'Search fields').within(() => { cy.root().get('select').select('Creator') });
    cy.contains('.field', 'Search fields').within(() => { cy.root().get('button[title="Add"]').click() });
    cy.get('#fields-field-set-button').click();

    cy.get('#content [type="checkbox"][name="o:settings[form][proximity]"]').click();

    cy.get('#page-actions button').click();  

    var longName = 'alpha bravo charlie delta echo foxtrot golf hotel india juliet kilo lima mike november oscar papa quebec romeo sierra tango uniform victor whiskey x-ray yankee zulu golf hotel india juliet kilo lima mike november oscar papa quebec romeo sierra tango uniform victor whiskey x-ray yankee zulu golf hotel india juliet kilo lima mike november oscar papa quebec romeo sierra tango uniform victor whiskey x-ray yankee zulu';
    createItem([longName, longName, longName], '', ['the creator 3'], true);
    createItem('Bonjour', '2001-01-02', ['A']);
    createItem('Bonjour2', '2001-01-01', []);
    createItem('Au Revoir', '2001-01-03', []);
    createItem('Au Revoir 2', '', ['B']);
    createItem('Auteurs', '', ['A', 'B']);
    createItem('Hidden', '', [], true);
    createItem('charlie la chocolaterie', '', ['the creator 1'], true);
    createItem('charlie la chocolaterie', '', ['the creator 2'], true);

    cy.get('#menu [href="/admin/search"]').click();
    cy.get('.o-icon-[title="Rebuild index"]').click();
    cy.contains('.field', 'Clear index').within(() => { cy.root().get('input[type="checkbox"]').click() });
    cy.get("#page-actions button").click();
    cy.wait(5000);
    cy.get('li.success a').click();
    cy.contains('.meta-group', 'Status').contains('Completed');

    cy.visit('http://omekas/s/test/solr');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    // Date Asc by default, then alphabetical order
    verifyItemsInOrder(['Bonjour2', 'Bonjour', 'Au Revoir'], false);
    verifyItemsInOrder(['Bonjour2', 'Bonjour', 'Au Revoir', longName, 'Auteurs', 'Au Revoir 2',
      'charlie la chocolaterie', 'charlie la chocolaterie', 'Hidden'], true, false);
  
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

    cy.visit('http://omekas/s/test/solr');
    cy.get('select.search-form-standard-filter-field').eq(0).select('Title');
    cy.get('select.search-form-standard-filter-operator').eq(0).select('contains all words');
    cy.get('input.search-form-standard-filter-proximity').eq(0).click();
    cy.get('input.search-form-standard-filter-proximity').eq(0).clear();
    cy.get('input.search-form-standard-filter-proximity').eq(0).type('3');
    cy.get('input.search-form-standard-filter-term').eq(0).click();
    cy.get('input.search-form-standard-filter-term').eq(0).clear();
    cy.get('input.search-form-standard-filter-term').eq(0).type('alpha charlie');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder([longName], true);

    cy.get('.items.resource .search-highlight').eq(0).within(() => { cy.root().get('mark').eq(0).should('have.text', 'alpha bravo charlie').and('exist'); });
    cy.get('.items.resource .search-highlight').eq(1).within(() => { cy.root().get('mark').eq(0).should('have.text', 'alpha bravo charlie').and('exist'); });
    cy.get('.items.resource .search-highlight').should('have.length', 2);

    cy.visit('http://omekas/s/test/solr');
    cy.get('select.search-form-standard-filter-field').eq(0).select('Title');
    cy.get('select.search-form-standard-filter-operator').eq(0).select('contains all words');
    cy.get('input.search-form-standard-filter-proximity').eq(0).click();
    cy.get('input.search-form-standard-filter-proximity').eq(0).clear();
    cy.get('input.search-form-standard-filter-proximity').eq(0).type('3');
    cy.get('input.search-form-standard-filter-term').eq(0).click();
    cy.get('input.search-form-standard-filter-term').eq(0).clear();
    cy.get('input.search-form-standard-filter-term').eq(0).type('alpha foxtrot');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder([], true);

    cy.visit('http://omekas/s/test/solr');
    cy.get('input[name="q"]').click();
    cy.get('input[name="q"]').clear();
    cy.get('input[name="q"]').type('charlie');
    cy.get('select.search-form-standard-filter-field').eq(0).select('Title');
    cy.get('select.search-form-standard-filter-operator').eq(0).select('contains any word');
    cy.get('input.search-form-standard-filter-term').eq(0).click();
    cy.get('input.search-form-standard-filter-term').eq(0).clear();
    cy.get('input.search-form-standard-filter-term').eq(0).type('chocolaterie x-ray');
    cy.get('select.search-form-standard-filter-field').eq(1).select('Creator');
    cy.get('select.search-form-standard-filter-operator').eq(1).select('contains all words');
    cy.get('input.search-form-standard-filter-term').eq(1).click();
    cy.get('input.search-form-standard-filter-term').eq(1).clear();
    cy.get('input.search-form-standard-filter-term').eq(1).type('the creator');
    cy.get('.button.search-add-group').click();
    cy.get('select[name="filters[queries][2][match]"]').select('any');
    cy.get('select.search-form-standard-filter-field').eq(2).select('Title');
    cy.get('select.search-form-standard-filter-operator').eq(2).select('contains any word');
    cy.get('input.search-form-standard-filter-term').eq(2).click();
    cy.get('input.search-form-standard-filter-term').eq(2).clear();
    cy.get('input.search-form-standard-filter-term').eq(2).type('foxtrot');
    cy.get('select.search-form-standard-filter-field').eq(3).select('Creator');
    cy.get('select.search-form-standard-filter-operator').eq(3).select('contains all words');
    cy.get('input.search-form-standard-filter-term').eq(3).click();
    cy.get('input.search-form-standard-filter-term').eq(3).clear();
    cy.get('input.search-form-standard-filter-term').eq(3).type('the creator 2');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder([longName, 'charlie la chocolaterie'], true, false);

    cy.get('[href="/logout"]').eq(0).click();

    cy.visit('http://omekas/s/test/solr');

    cy.get('form[action="/s/test/solr"] button[type="submit"]').click();

    verifyItemsInOrder(['Bonjour2', 'Bonjour', 'Au Revoir', 'Auteurs', 'Au Revoir 2'], true, false);
  });
});
