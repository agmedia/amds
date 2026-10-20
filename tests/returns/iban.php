<?php
// Run with: php tests/returns/iban.php
class Controller { public $language; }
require __DIR__ . '/../../upload/catalog/controller/account/return.php';
class ReturnIbanTest extends ControllerAccountReturn {
    public function prepare($data) { return $this->prepareReturnData($data); }
    public function email($data) { return $this->buildReturnEmailDetails(1, $data, "Reason"); }
    public function check($value) {
        return $this->isValidRefundIban($this->normalizeRefundIban($value));
    }
}
$controller = new ReturnIbanTest();
foreach (array(
    'HR1210010051863000160' => true,
    '1210010051863000160' => true,
    'hr12 1001 0051 8630 0016 0' => true,
    'HR1210010051863000161' => false,
    'HR121001005186300016' => false,
    'HR12100100518630001600' => false,
    'DE1210010051863000160' => false,
    'HR121001005186300016x' => false,
    '' => false
) as $value => $expected) {
    if ($controller->check($value) !== $expected) {
        throw new RuntimeException('Unexpected validation result: ' . $value);
    }
}
echo "IBAN validation: 9 cases passed\n";

$controller->language = new class {
    public function get($key) { return $key; }
};
$data = $controller->prepare(array(
    'refund_account_holder' => '  Ana Horvat  ',
    'refund_iban' => '1210010051863000160',
    'return_products' => array(array('code' => 'SKU', 'quantity' => '1', 'price' => '10'))
));
if ($data['refund_iban'] !== 'HR1210010051863000160'
    || strpos($data['comment'], 'entry_refund_account_holder: Ana Horvat') === false
    || strpos($controller->email($data), 'entry_refund_account_holder: Ana Horvat') === false) {
    throw new RuntimeException('Account holder or normalized IBAN missing from return details');
}
echo "Account holder persistence and email: passed\n";
