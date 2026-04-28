<?php

require_once DIR_APPLICATION . 'controller/extension/module/luceed_sync.php';

class ControllerExtensionModuleLuceedSyncUpdatePricesAndQuantities extends ControllerExtensionModuleLuceedSync
{
    public function index()
    {
        return $this->updatePricesAndQuantities();
    }
}
