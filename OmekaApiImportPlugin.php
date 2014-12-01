<?php


class OmekaApiImportPlugin extends Omeka_Plugin_AbstractPlugin
{
    protected $_hooks = array(
        'install',
        'uninstall',
        'upgrade',
        'after_delete_record'
        );
    protected $_filters = array('admin_navigation_main');

    public function hookInstall()
    {
        $db = get_db();
        $sql = "
            CREATE TABLE IF NOT EXISTS `$db->OmekaApiImportRecordIdMap` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `local_id` int(11) NOT NULL,
              `record_type` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `external_id` int(11) NOT NULL,
              `endpoint_uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `external_id` (`record_type`,`external_id`,`endpoint_uri`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
        ";
        $db->query($sql);

        $sql = "
            CREATE TABLE IF NOT EXISTS `$db->OmekaApiImport` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `endpoint_uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
              `status` tinytext NOT NULL,
              `date` timestamp,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
        ";

        $db->query($sql);
    }

    public function hookUninstall()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `$db->OmekaApiImportRecordIdMap`";
        $db->query($sql);
        $sql = "DROP TABLE IF EXISTS `$db->OmekaApiImport`";
        $db->query($sql);
    }

    public function hookUpgrade($args)
    {
        $oldVersion = $args['old_version'];
        $newVersion = $args['new_version'];

        if (version_compare($oldVersion, '1.1', '<')) {
            $db = get_db();
            $sql = "
                CREATE TABLE IF NOT EXISTS `$db->OmekaApiImport` (
                  `id` int(11) NOT NULL AUTO_INCREMENT,
                  `endpoint_uri` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `status` tinytext NOT NULL,
                  `date` timestamp,
                  PRIMARY KEY (`id`)
                ) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1 ;
            ";

            $db->query($sql);

            $sql = "
                SELECT DISTINCT `endpoint_uri`
                FROM `$db->OmekaApiImportRecordIdMap`
                WHERE 1
            ";
            $uris = $db->fetchCol($sql);
            if (! empty($uris)) {
                $values = array();
                foreach ($uris as $uri) {
                    $values[] = "(NULL, '$uri', '', CURRENT_TIMESTAMP)";
                }
                $sql = "
                    INSERT INTO `$db->OmekaApiImport` (`id`, `endpoint_uri`, `status`, `date`) VALUES 
                " . join(", ",  $values);
                $db->query($sql);
            }
        }
    }

    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array('label' => __('Omeka Api Import'),
                       'uri'   => url('omeka-api-import/index/index')
                );
        return $nav;
    }

    public function hookAfterDeleteRecord($args)
    {
        $record = $args['record'];
        $apiRecordMap = get_db()->getTable('OmekaApiImportRecordIdMap')->findBy(array('local_id' => $record->id,
                                                                           'record_type' => get_class($record)));
        if(!empty($apiRecordMap)) {
            $apiRecordMap = $apiRecordMap[0];
            $apiRecordMap->delete();
        }
    }
}