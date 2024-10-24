<?php

/*
 * Copyright BibLibre, 2016-2020
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

use Laminas\Form\Element\Text;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;
use Laminas\I18n\Translator\TranslatorAwareInterface;
use Laminas\I18n\Translator\TranslatorAwareTrait;

class SolrNodeForm extends Form implements TranslatorAwareInterface
{
    use TranslatorAwareTrait;

    public function init()
    {
        $translator = $this->getTranslator();

        $this->add([
            'name' => 'o:name',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Name'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:uri',
            'type' => 'Text',
            'options' => [
                'label' => 'URI', // @translate
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $this->add([
            'name' => 'o:user',
            'type' => 'Text',
            'options' => [
                'label' => 'Username', // @translate
                'info' => 'The username used for HTTP Authentication, if any', // @translate
            ],
        ]);

        $this->add([
            'name' => 'o:password',
            'type' => 'Text',
            'options' => [
                'label' => 'Password', // @translate
                'info' => 'The HTTP Authentication password', // @translate
            ],
        ]);

        $settingsFieldset = new Fieldset('o:settings');

        $settingsFieldset->add([
            'name' => 'resource_name_field',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Resource name field'),
                'info' => $translator->translate('Name of Solr field that will contain the resource name (or resource type, e.g. "items", "item_sets", ...). It must be a single-valued, string-based field. WARNING: Changing this will require a complete reindexation.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $settingsFieldset->add([
            'name' => 'sites_field',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Sites field'),
                'info' => $translator->translate('Name of Solr field that will contain the sites ids. It must be a single-valued, integer-based field. WARNING: Changing this will require a complete reindexation.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $settingsFieldset->add([
            'name' => 'is_public_field',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Is Public field'),
                'info' => $translator->translate('Name of the Solr field that will contain the isPublic flag. It must be a single-valued, boolean-based field. WARNING: Changing this will require a complete reindexation.'),
            ],
            'attributes' => [
                'required' => true,
            ],
        ]);

        $settingsFieldset->add([
            'name' => 'groups_field',
            'type' => 'Text',
            'options' => [
                'label' => 'Groups field', // @translate
                'info' => 'Name of the Solr field that will contain the groups ids. It must be a multi-valued, integer-based field.<br>Only useful if the module Group is enabled<br><strong>WARNING: Changing this will require a complete reindexation</strong>', // @translate
                'escape_info' => false,
            ],
        ]);

        $settingsFieldset->add([
            'name' => 'qf',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Query fields') . ' (qf)',
                'info' => $translator->translate('qf parameter that will be added to the query'),
            ],
        ]);

        $settingsFieldset->add([
            'name' => 'mm',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Minimum should match') . ' (mm)',
                'info' => $translator->translate('mm parameter that will be added to the query'),
            ],
        ]);

        $settingsFieldset->add([
            'name' => 'highlight',
            'type' => Fieldset::class,
            'options' => [
                'label' => 'Highlighting', // @translate
            ],
        ]);
        $highlightFieldset = $settingsFieldset->get('highlight');

        $highlightFieldset->add([
            'name' => 'highlighting',
            'type' => 'checkbox',
            'options' => [
                'label' => $translator->translate('Highlighting'),
                'info' => $translator->translate('Enable extract retrieval in relation to search terms'),
            ],
        ]);

        $highlightFieldset->add([
            'name' => 'fields',
            'type' => 'Text',
            'options' => [
                'label' => $translator->translate('Highlight fields'),
                'info' => $translator->translate('Fields used for highligthing feature (use "*" for all fields).'),
            ],
        ]);

        $highlightFieldset->add([
            'name' => 'fragsize',
            'type' => 'Number',
            'options' => [
                'label' => $translator->translate('Highlight fragment size'),
                'info' => $translator->translate('Define number of caracters for the fragment size of highlight, 0 will show the entire field value.'),
            ],
        ]);

        $highlightFieldset->add([
            'name' => 'snippets',
            'type' => 'Number',
            'options' => [
                'label' => $translator->translate('Highlight snippets'),
                'info' => $translator->translate('Define the number of fragments where the search terms were found in the same field.'),
            ],
        ]);

        $highlightFieldset->add([
            'name' => 'maxAnalyzedChars',
            'type' => Text::class,
            'options' => [
                'label' => 'Maximum characters analyzed', // @translate
                'info' => 'Set the value of hl.maxAnalyzedChars parameter. Great values can have impact on performance', // @translate
            ],
        ]);

        $this->add($settingsFieldset);

        $inputFilter = $this->getInputFilter();
        $settingsInputFilter = $inputFilter->get('o:settings');
        $highlightInputFilter = $settingsInputFilter->get('highlight');

        $highlightInputFilter->add([
            'name' => 'highlighting',
            'required' => false,
        ]);
        $highlightInputFilter->add([
            'name' => 'fragsize',
            'required' => false,
        ]);
        $highlightInputFilter->add([
            'name' => 'snippets',
            'required' => false,
        ]);
    }
}
