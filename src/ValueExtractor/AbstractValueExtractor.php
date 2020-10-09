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

namespace Solr\ValueExtractor;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Laminas\EventManager\Event;
use Laminas\EventManager\EventManagerAwareTrait;

abstract class AbstractValueExtractor implements ValueExtractorInterface
{
    use EventManagerAwareTrait;

    protected function triggerEvent($name, $target = null, array $args = [])
    {
        $events = $this->getEventManager();
        $params = $events->prepareArgs($args);
        $event = new Event($name, $target, $params);
        $events->triggerEvent($event);

        return $params;
    }

    /**
     * Extract the values of the given property of the given resource
     * If the value is a resource, then its title is used.
     * @param AbstractResourceEntityRepresentation $representation Item
     * @param string $field Property (RDF term).
     * @return string[] Human-readable values.
     */
    protected function extractPropertyValue(AbstractResourceEntityRepresentation $representation, $field)
    {
        $extractedValue = [];
        $values = $representation->value($field, ['all' => true, 'default' => []]);
        foreach ($values as $i => $value) {
            $type = $value->type();
            if ($type === 'literal' || $type == 'uri') {
                $extractedValue[] = (string) $value;
            } elseif ('resource' === explode(':', $type)[0]) {
                $resourceTitle = $value->valueResource()->displayTitle('');
                if (!empty($resourceTitle)) {
                    $extractedValue[] = $resourceTitle;
                }
            }
        }

        return $extractedValue;
    }
}
