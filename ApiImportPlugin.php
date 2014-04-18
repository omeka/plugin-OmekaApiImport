<?php


class ApiImportPlugin extends Omeka_Plugin_AbstractPlugin

{
    protected $_hooks = array('install', 'uninstall', 'after_delete_record');
    protected $_filters = array('admin_navigation_main');

    public function hookInstall()
    {
        $db = get_db();
        $sql = "
            CREATE TABLE IF NOT EXISTS `$db->ApiRecordIdMap` (
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
    }

    public function hookUninstall()
    {
        $db = get_db();
        $sql = "DROP TABLE IF EXISTS `$db->ApiRecordIdMap`";
        $db->query($sql);
    }

    public function filterAdminNavigationMain($nav)
    {
        $nav[] = array('label' => __('Omeka Import'),
                       'uri'   => url('api-import/index/index')
                );
        return $nav;
    }

    public function hookAfterDeleteRecord($args)
    {
        $record = $args['record'];
        $apiRecordMap = get_db()->getTable('ApiRecordIdMap')->findBy(array('local_id' => $record->id,
                                                                           'record_type' => get_class($record)));
        if(!empty($apiRecordMap)) {
            $apiRecordMap = $apiRecordMap[0];
            $apiRecordMap->delete();
        }

    }
}