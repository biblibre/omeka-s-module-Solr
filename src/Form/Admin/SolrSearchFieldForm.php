<?php

/*
 * Copyright BibLibre, 2020
 *
 * This software is governed by the CeCILL license under French law and abiding
 * by the rules of distribution of free software.  You can use, modify and/ or
 * redistribute the software under the terms of the CeCILL license as circulated
 * by CEA, CNRS and INRIA at the following URL "http://www.cecill.info".
 *
 * As a counterpart to the access to the source code and rights to copy, modify
 * and redistribute granted by the license, users are provided only with a
 * limited warranty and the software's author, the holder of the economic
 * rights, and the successive licensors have only limited liability.
 *
 * In this respect, the user's attention is drawn to the risks associated with
 * loading, using, modifying and/or developing or reproducing the software by
 * the user in light of its specific status of free software, that may mean that
 * it is complicated to manipulate, and that also therefore means that it is
 * reserved for developers and experienced professionals having in-depth
 * computer knowledge. Users are therefore encouraged to load and test the
 * software's suitability as regards their requirements in conditions enabling
 * the security of their systems and/or data to be ensured and, more generally,
 * to use and operate it in the same conditions as regards security.
 *
 * The fact that you are presently reading this means that you have had
 * knowledge of the CeCILL license and that you accept its terms.
 */

namespace Solr\Form\Admin;

use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorAwareTrait;

class SolrSearchFieldForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    protected $apiManager;

    public function init()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:name',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Name'),
                'info' => $translator->translate('The name will be used internally before being translated to the Solr field name. It will also be available in queries to search on this specific field. It should contain only alphanumeric characters and underscore, and should not start with a digit.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:label',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Label'),
                'info' => $translator->translate('The label is the human-friendly version of the name'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:text_fields',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Text fields'),
                'info' => $translator->translate('A space-separated list of Solr fields which will be used when a search needs to be performed on text fields. Leave empty to forbid text search on this field.'),
            ],
        ]);

        $this->add([
            'name' => 'o:string_fields',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('String fields'),
                'info' => $translator->translate('A space-separated list of Solr fields which will be used when a search needs to be performed on string fields. Leave empty to forbid string search on this field.'),
            ],
        ]);

        $this->add([
            'name' => 'o:facet_field',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Facet field'),
                'info' => $translator->translate('The Solr field which will be used for faceting. Leave empty to forbid faceting on this field.'),
            ],
        ]);

        $this->add([
            'name' => 'o:sort_field',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Sort field'),
                'info' => $translator->translate('The Solr field which will be used for sorting. Leave empty to forbid sorting on this field.'),
            ],
        ]);
    }

    public function setApiManager($apiManager)
    {
        $this->apiManager = $apiManager;
    }

    public function getApiManager()
    {
        return $this->apiManager;
    }
}
