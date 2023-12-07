<?php
namespace Solr\Form\Element;

use Laminas\Filter\Callback;
use Laminas\Form\Element;
use Laminas\InputFilter\InputProviderInterface;

class Transformations extends Element implements InputProviderInterface
{
    protected $attributes = [
        'class' => 'transformations-transformations-data',
    ];

    public function getInputSpecification()
    {
        return [
            'required' => false,
            'filters' => [
                // Decode JSON into a PHP array so data can be stored properly.
                new Callback(function ($json) {
                    return isset($json) ? json_decode($json, true) : [];
                }),
            ],
        ];
    }
}
