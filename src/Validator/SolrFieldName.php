<?php

namespace Solr\Validator;

use Laminas\Validator\AbstractValidator;
use Laminas\Stdlib\ErrorHandler;

class SolrFieldName extends AbstractValidator
{
    const INVALID   = 'solrFieldNameInvalid';
    const NOT_MATCH = 'solrFieldNameNotMatch';
    const RESERVED  = 'solrFieldNameReserved';

    /**
     * @var array
     */
    protected $messageTemplates = [
        self::INVALID   => 'Invalid type given. String expected',
        self::NOT_MATCH => 'Solr field name must consist of alphanumeric or underscore characters only and not start with a digit',
        self::RESERVED  => 'Solr field name must not have both leading and trailing underscores',
    ];

    /**
     * Returns true if and only if $value matches against the pattern option
     *
     * @param  string $value
     * @return bool
     */
    public function isValid($value)
    {
        if (!is_string($value)) {
            $this->error(self::INVALID);
            return false;
        }

        $this->setValue($value);

        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $value)) {
            $this->error(self::NOT_MATCH);
            return false;
        }

        if (0 === strncmp($value, '_', 1) && false !== strpos($value, '_', -1)) {
            $this->error(self::RESERVED);
            return false;
        }

        return true;
    }
}
