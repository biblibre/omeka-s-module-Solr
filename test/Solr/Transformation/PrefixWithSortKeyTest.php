<?php

namespace Solr\Test\Transformation;

use Omeka\Entity\Value;
use Omeka\Api\Representation\ValueRepresentation;
use Solr\SortKey;
use Solr\Test\TestCase;
use Solr\Transformation\PrefixWithSortKey;

class PrefixWithSortKeyTest extends TestCase
{
    public function testTransformation()
    {
        $transformedValues = $this->transform([
            $this->createValueRepresentation('  Œuvres   d\'Émile '),
            '',
            '   ',
            'a||b',
        ]);

        $this->assertCount(2, $transformedValues);
        $this->assertEquals('oeuvres d\'emile||Œuvres   d\'Émile', $transformedValues[0]);
        $this->assertEquals('a b||a||b', $transformedValues[1]);
        $this->assertEquals('a||b', SortKey::strip($transformedValues[1]));
    }

    public function testInvisibleCharacters()
    {
        $transformedValues = $this->transform([
            "\u{00A0}Arbre",
            "\u{200B}Banane",
            "\u{FEFF}Cerise\u{00A0}",
            "\u{00AD}\tDatte",
            "\u{200B}\u{00A0}",
        ]);

        $this->assertEquals([
            'arbre||Arbre',
            'banane||Banane',
            'cerise||Cerise',
            'datte||Datte',
        ], $transformedValues);
    }

    public function testSortOrder()
    {
        $transformedValues = $this->transform(['Zoé', 'zèbre', 'École', 'Ecole', 'abeille', 'Éléphant', 'Œuvres']);

        sort($transformedValues, SORT_STRING);

        $this->assertEquals(
            ['abeille', 'Ecole', 'École', 'Éléphant', 'Œuvres', 'zèbre', 'Zoé'],
            array_map([SortKey::class, 'strip'], $transformedValues)
        );
    }

    public function testStripWithoutSeparator()
    {
        $this->assertEquals('Éléphant', SortKey::strip('Éléphant'));
    }

    protected function transform(array $values): array
    {
        $transformationManager = $this->getServiceLocator()->get('Solr\TransformationManager');
        $transformation = $transformationManager->get(PrefixWithSortKey::class);

        return $transformation->transform($values, []);
    }

    protected function createValueRepresentation(string $text): ValueRepresentation
    {
        $value = new Value();
        $value->setType('literal');
        $value->setValue($text);

        return new ValueRepresentation($value, $this->getServiceLocator());
    }
}
