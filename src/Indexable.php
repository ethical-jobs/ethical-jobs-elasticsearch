<?php

namespace EthicalJobs\Elasticsearch;

use Illuminate\Database\Eloquent\Builder;

/**
 * Indexable within elasticsearch interface
 *
 * @author Andrew McLagan <andrew@ethicaljobs.com.au>
 */

interface Indexable
{
    /**
     * Return the document id
     *
     * @return String
     */
    public function getDocumentKey();

    /**
     * Return the document type
     *
     * @return String
     */
    public function getDocumentType();

    /**
     * Return the documents field data
     *
     * @return Array
     */
    public function getDocumentBody();

    /**
     * Return the documents field map
     *
     * @return Array
     */
    public function getDocumentMappings();

    /**
     * Return the documents relations
     *
     * @return Array
     */
    public function getDocumentRelations();

    /**
     * Returns indexing query
     *
     * @return Illuminate\Database\Query\Builder
     */
    public function getIndexingQuery(): Builder;
}
