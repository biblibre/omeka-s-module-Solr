<?php

/*
 * Copyright BibLibre, 2017-2020
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

namespace Solr;

use Solr\Schema\Field;

class Schema
{
    protected $hostname;
    protected $port;
    protected $path;

    protected $schema;
    protected $fieldsByName;
    protected $dynamicFieldsMap;
    protected $typesByName;

    protected $fields = [];

    public function __construct($hostname, $port, $path)
    {
        $this->hostname = $hostname;
        $this->port = $port;
        $this->path = $path;
    }

    public function getSchema()
    {
        if (!isset($this->schema)) {
            $url = "http://{$this->hostname}:{$this->port}/{$this->path}/schema";
            $response = json_decode(file_get_contents($url), true);
            $this->schema = $response['schema'];
        }

        return $this->schema;
    }

    public function setSchema($schema)
    {
        $this->schema = $schema;
    }

    public function getField($name)
    {
        if (!isset($this->fields[$name])) {
            $fieldsByName = $this->getFieldsByName();
            $field = null;
            if (isset($fieldsByName[$name])) {
                $field = $fieldsByName[$name];
            } else {
                $field = $this->getDynamicFieldFor($name);
            }

            if (isset($field)) {
                $type = $this->getType($field['type']);
                $field = new Field($name, $field, $type);
            }
            $this->fields[$name] = $field;
        }

        return $this->fields[$name];
    }

    public function getFieldsByName()
    {
        if (!isset($this->fieldsByName)) {
            $schema = $this->getSchema();
            $this->fieldsByName = [];
            foreach ($schema['fields'] as $field) {
                $this->fieldsByName[$field['name']] = $field;
            }
        }

        return $this->fieldsByName;
    }

    public function getDynamicFieldFor($name)
    {
        $dynamicFieldsMap = $this->getDynamicFieldsMap();

        $firstChar = $name[0];
        if (isset($dynamicFieldsMap['prefix'][$firstChar])) {
            foreach ($dynamicFieldsMap['prefix'][$firstChar] as $field) {
                $prefix = substr($field['name'], 0, strlen($field['name']) - 1);
                if (0 === substr_compare($name, $prefix, 0, strlen($prefix))) {
                    return $field;
                }
            }
        }

        $lastChar = $name[strlen($name) - 1];
        if (isset($dynamicFieldsMap['suffix'][$lastChar])) {
            foreach ($dynamicFieldsMap['suffix'][$lastChar] as $field) {
                $suffix = substr($field['name'], 1);
                $suffixLen = strlen($suffix);
                $offset = strlen($name) - $suffixLen;
                if ($offset <= 0) {
                    continue;
                }
                if (0 === substr_compare($name, $suffix, $offset, $suffixLen)) {
                    return $field;
                }
            }
        }
    }

    public function getDynamicFieldsMap()
    {
        if (!isset($this->dynamicFieldsMap)) {
            $schema = $this->getSchema();
            $this->dynamicFieldsMap = [];
            foreach ($schema['dynamicFields'] as $field) {
                $name = $field['name'];
                $char = $name[0];
                $key = 'prefix';
                if ($char === '*') {
                    $char = $name[strlen($name) - 1];
                    $key = 'suffix';
                }

                $this->dynamicFieldsMap[$key][$char][] = $field;
            }
        }

        return $this->dynamicFieldsMap;
    }

    public function getType($name)
    {
        $typesByName = $this->getTypesByName();
        if (isset($typesByName[$name])) {
            return $typesByName[$name];
        }
    }

    public function getTypesByName()
    {
        if (!isset($this->typesByName)) {
            $schema = $this->getSchema();
            foreach ($schema['fieldTypes'] as $type) {
                $this->typesByName[$type['name']] = $type;
            }
        }

        return $this->typesByName;
    }
}
