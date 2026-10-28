<?php
class ControllerExtensionModuleLuceedSync {
    private $registry;
    public function __construct($registry) { $this->registry = $registry; }
    public function updatePricesAndQuantities() {
        $result = getenv('LUCEED_CLI_FIXTURE_RESULT');
        if ($result === 'throw') { throw new RuntimeException('Fixture exception'); }
        if ($result === 'exit') { exit(0); }
        $this->registry->get('response')->setOutput($result);
    }
}
