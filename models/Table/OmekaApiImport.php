<?php
class Table_OmekaApiImport extends Omeka_Db_Table
{
    public function getImportedEndpoints()
    {

        $prefix = $this->getTablePrefix();
        $db = $this->getDb();
        $sql = "
            SELECT DISTINCT `endpoint_uri`
            FROM `$db->OmekaApiImport`
            WHERE 1
        ";
        return $this->getDb()->fetchCol($sql);
    }
}