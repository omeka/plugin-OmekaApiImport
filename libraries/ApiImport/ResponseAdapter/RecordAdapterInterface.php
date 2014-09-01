<?php

/**
 * Interface for updating and inserting records in Omeka via data
 * obtained via an external API.
 * RecordAdapterAbstract takes a Response in constructor, so classes
 * implementing this interface should use internal methods to work through
 * that response, whatever it may be.
 *
 * The insert method should make use of the addOmekaApiImportRecordIdMap method in RecordAdapterAbstract (or similar)
 * to maintain a connection between local and external record ids
 *
 */
interface ApiImport_ResponseAdapter_RecordAdapterInterface
{
    /**
     * Create a new record or update an existing one from the external API response data
     *
     * @return Omeka_Record_AbstractRecord
     */
    function import();

    /**
     * Lookup the external Id from the response data received
     *
     * @return mixed
     */
    function externalId();

}