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

namespace Solr\Entity;

use Omeka\Entity\AbstractEntity;

/**
 * @Entity
 */
class SolrSearchField extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * @ManyToOne(targetEntity="Solr\Entity\SolrNode")
     * @JoinColumn(nullable=false, onDelete="CASCADE")
     */
    protected $solrNode;

    /**
     * @Column(type="string", length=255, unique=true)
     */
    protected $name;

    /**
     * @Column(type="string", length=255)
     */
    protected $label;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $textFields;

    /**
     * @Column(type="text", nullable=true)
     */
    protected $stringFields;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $facetField;

    /**
     * @Column(type="string", length=255, nullable=true)
     */
    protected $sortField;

    public function getId()
    {
        return $this->id;
    }

    public function setSolrNode(SolrNode $solrNode)
    {
        $this->solrNode = $solrNode;
    }

    public function getSolrNode()
    {
        return $this->solrNode;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setTextFields($textFields)
    {
        if (empty($textFields)) {
            $textFields = null;
        }

        $this->textFields = $textFields;
    }

    public function getTextFields()
    {
        return $this->textFields;
    }

    public function setStringFields($stringFields)
    {
        if (empty($stringFields)) {
            $stringFields = null;
        }

        $this->stringFields = $stringFields;
    }

    public function getStringFields()
    {
        return $this->stringFields;
    }

    public function setFacetField($facetField)
    {
        if (empty($facetField)) {
            $facetField = null;
        }

        $this->facetField = $facetField;
    }

    public function getFacetField()
    {
        return $this->facetField;
    }

    public function setSortField($sortField)
    {
        if (empty($sortField)) {
            $sortField = null;
        }

        $this->sortField = $sortField;
    }

    public function getSortField()
    {
        return $this->sortField;
    }
}
