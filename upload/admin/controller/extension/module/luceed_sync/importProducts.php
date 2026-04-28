<?php

require_once DIR_APPLICATION . 'controller/extension/module/luceed_sync.php';

class ControllerExtensionModuleLuceedSyncImportProducts extends ControllerExtensionModuleLuceedSync
{
    public function index()
    {
        return $this->importProducts();
    }
}
