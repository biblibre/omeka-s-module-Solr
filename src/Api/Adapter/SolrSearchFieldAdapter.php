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

namespace Solr\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Solr\Api\Representation\SolrSearchFieldRepresentation;
use Solr\Entity\SolrSearchField;

class SolrSearchFieldAdapter extends AbstractEntityAdapter
{
    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'name' => 'name',
        'label' => 'label',
    ];

    /**
     * {@inheritDoc}
     */
    public function getResourceName()
    {
        return 'solr_search_fields';
    }

    /**
     * {@inheritDoc}
     */
    public function getRepresentationClass()
    {
        return SolrSearchFieldRepresentation::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntityClass()
    {
        return SolrSearchField::class;
    }

    /**
     * {@inheritDoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if ($this->shouldHydrate($request, 'o:name')) {
            $entity->setName($request->getValue('o:name'));
        }
        if ($this->shouldHydrate($request, 'o:label')) {
            $entity->setLabel($request->getValue('o:label'));
        }
        if ($this->shouldHydrate($request, 'o:text_fields')) {
            $entity->setTextFields($request->getValue('o:text_fields'));
        }
        if ($this->shouldHydrate($request, 'o:string_fields')) {
            $entity->setStringFields($request->getValue('o:string_fields'));
        }
        if ($this->shouldHydrate($request, 'o:facet_field')) {
            $entity->setFacetField($request->getValue('o:facet_field'));
        }
        if ($this->shouldHydrate($request, 'o:sort_field')) {
            $entity->setSortField($request->getValue('o:sort_field'));
        }

        $this->hydrateSolrNode($request, $entity);
    }

    /**
     * {@inheritDoc}
     */
    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['solr_node_id'])) {
            $solrNodeAlias = $this->createAlias();
            $qb->innerJoin('omeka_root.solrNode', $solrNodeAlias);
            $qb->andWhere($qb->expr()->eq(
                "$solrNodeAlias.id",
                $this->createNamedParameter($qb, $query['solr_node_id'])
            ));
        }

        if (isset($query['name'])) {
            $solrNodeAlias = $this->createAlias();
            $qb->andWhere($qb->expr()->eq(
                'omeka_root.name',
                $this->createNamedParameter($qb, $query['name'])
            ));
        }

        if (isset($query['facetable'])) {
            if ($query['facetable']) {
                $qb->andWhere($qb->expr()->isNotNull('omeka_root.facetField'));
            } else {
                $qb->andWhere($qb->expr()->isNull('omeka_root.facetField'));
            }
        }

        if (isset($query['sortable'])) {
            if ($query['sortable']) {
                $qb->andWhere($qb->expr()->isNotNull('omeka_root.sortField'));
            } else {
                $qb->andWhere($qb->expr()->isNull('omeka_root.sortField'));
            }
        }

        if (isset($query['searchable'])) {
            if ($query['searchable']) {
                $qb->andWhere($qb->expr()->orX(
                    $qb->expr()->isNotNull('omeka_root.textFields'),
                    $qb->expr()->isNotNull('omeka_root.stringFields')
                ));
            } else {
                $qb->andWhere($qb->expr()->andX(
                    $qb->expr()->isNull('omeka_root.textFields'),
                    $qb->expr()->isNull('omeka_root.stringFields')
                ));
            }
        }
    }

    protected function hydrateSolrNode(Request $request, EntityInterface $entity)
    {
        if ($this->shouldHydrate($request, 'o:solr_node')) {
            $data = $request->getContent();
            if (isset($data['o:solr_node']['o:id'])
                && is_numeric($data['o:solr_node']['o:id'])
            ) {
                $node = $this->getAdapter('solr_nodes')
                    ->findEntity($data['o:solr_node']['o:id']);
            } else {
                $node = null;
            }
            $entity->setSolrNode($node);
        }
    }
}
