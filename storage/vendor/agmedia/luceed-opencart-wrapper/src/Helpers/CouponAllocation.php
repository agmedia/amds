<?php

namespace Agmedia\LuceedOpencartWrapper\Helpers;

/**
 * Immutable checkout coupon amounts and cent-exact Luceed rebate lines.
 *
 * This class deliberately has no database or framework dependencies. Prices in a
 * snapshot use the same tax basis as the checkout calculation that created it.
 */
class CouponAllocation
{
    /**
     * Identify a cart/order line without relying on its mutable display labels.
     */
    public static function productKey(array $product): string
    {
        $productId = self::integer(self::required($product, 'product_id'), 'product_id', 1);
        $quantity = self::integer(self::required($product, 'quantity'), 'quantity', 1);
        $price = self::number(self::required($product, 'price'), 'price');
        $total = isset($product['total'])
            ? self::number($product['total'], 'total')
            : $price * $quantity;
        $options = isset($product['option']) ? $product['option'] : array();

        if (!is_array($options)) {
            throw new \InvalidArgumentException('Product options must be an array.');
        }

        $identityOptions = array();

        foreach ($options as $option) {
            if (!is_array($option)) {
                throw new \InvalidArgumentException('Each product option must be an array.');
            }

            $value = isset($option['value']) ? $option['value'] : '';

            if (!is_scalar($value)) {
                throw new \InvalidArgumentException('Product option value must be scalar.');
            }

            $identityOptions[] = array(
                self::integer(isset($option['product_option_id']) ? $option['product_option_id'] : 0, 'product_option_id', 0),
                self::integer(isset($option['product_option_value_id']) ? $option['product_option_value_id'] : 0, 'product_option_value_id', 0),
                (string) $value,
            );
        }

        usort($identityOptions, function (array $left, array $right): int {
            return strcmp(json_encode($left), json_encode($right));
        });

        $identity = array(
            $productId,
            $quantity,
            number_format($price, 4, '.', ''),
            number_format($total, 4, '.', ''),
            $identityOptions,
        );
        $encoded = json_encode($identity);

        if ($encoded === false) {
            throw new \InvalidArgumentException('Product identity cannot be encoded.');
        }

        return hash('sha256', $encoded);
    }

    /**
     * Return the snapshot with each product bound to its inserted order line.
     * Identical lines consume separate order_product_id values in input order.
     */
    public static function bindProducts(array $snapshot, array $orderProducts): array
    {
        self::validateSnapshot($snapshot);
        $queues = array();
        $usedIds = array();

        foreach ($orderProducts as $product) {
            if (!is_array($product)) {
                throw new \InvalidArgumentException('Order products must be arrays.');
            }

            $id = self::integer(self::required($product, 'order_product_id'), 'order_product_id', 1);

            if (isset($usedIds[$id])) {
                throw new \LogicException('Duplicate order_product_id in coupon binding.');
            }

            $usedIds[$id] = true;
            $key = self::productKey($product);

            if (!isset($queues[$key])) {
                $queues[$key] = array();
            }

            $queues[$key][] = $id;
        }

        foreach ($snapshot['products'] as $index => $product) {
            if (!is_array($product) || !isset($product['key']) || !is_string($product['key'])) {
                throw new \InvalidArgumentException('Snapshot product is missing its identity.');
            }

            $key = $product['key'];

            if (empty($queues[$key])) {
                throw new \LogicException('Coupon snapshot does not match inserted order products.');
            }

            $snapshot['products'][$index]['order_product_id'] = array_shift($queues[$key]);
        }

        foreach ($queues as $queue) {
            if ($queue) {
                throw new \LogicException('Inserted order products are missing from coupon snapshot.');
            }
        }

        return $snapshot;
    }

    /**
     * Round the final paid amounts first, then distribute the coupon cents.
     * This preserves the checkout's paid total at half-cent boundaries instead
     * of independently rounding the coupon and changing what the buyer paid.
     */
    public static function finalize(array $snapshot): array
    {
        self::validateSnapshot($snapshot);
        $amount = self::number(self::required($snapshot, 'amount'), 'amount');
        $shipping = self::number(self::required($snapshot, 'shipping_discount'), 'shipping_discount');
        $discounts = array();
        $charged = array();
        $capacities = array();
        $indexes = array();

        foreach ($snapshot['products'] as $index => $product) {
            if (!is_array($product) || empty($product['key']) || !is_string($product['key'])) {
                throw new \InvalidArgumentException('Snapshot product is missing its identity.');
            }

            self::integer(self::required($product, 'product_id'), 'product_id', 1);
            $quantity = self::integer(self::required($product, 'quantity'), 'quantity', 1);
            $price = self::number(self::required($product, 'price'), 'price');
            self::number(self::required($product, 'base_price'), 'base_price');
            $discounts[] = self::number(self::required($product, 'discount'), 'discount');
            $charged[] = $price * $quantity;
            $capacities[] = self::ceilCents($price * $quantity);
            $indexes[] = $index;
        }

        $shippingPrice = isset($snapshot['shipping_price'])
            ? self::number($snapshot['shipping_price'], 'shipping_price')
            : $shipping;
        $discounts[] = $shipping;
        $charged[] = $shippingPrice;
        $capacities[] = self::ceilCents($shippingPrice);
        $rawSum = array_sum($discounts);
        $chargedSum = array_sum($charged);

        // A checkout total cap may reduce the raw discounts. Allow only the
        // half-unit difference introduced by order_total's four-decimal storage.
        if (!is_finite($rawSum) || $amount > $rawSum + max(0.0000501, $rawSum * 0.0000000001)) {
            throw new \LogicException('Coupon amount exceeds its recorded component discounts.');
        }
        if (!is_finite($chargedSum) || $amount > $chargedSum + max(0.0000501, $chargedSum * 0.0000000001)) {
            throw new \LogicException('Coupon amount exceeds the charged eligible amount.');
        }

        $scale = $rawSum > 0 ? min(1, $amount / $rawSum) : 0;
        $rawPaid = array();
        foreach ($charged as $index => $value) {
            $rawPaid[] = max(0, $value - $discounts[$index] * $scale);
        }
        // Order totals are stored as DECIMAL(15,4) before payment rounds to
        // currency precision, including the raw tax values in gross snapshots.
        $chargedTarget = self::cents(round($chargedSum, 4, PHP_ROUND_HALF_UP));
        $paidTarget = self::cents(round(max(0, $chargedSum - $amount), 4, PHP_ROUND_HALF_UP));
        // On equal remainders, assign the extra paid cent to the later line so
        // the corresponding coupon cent remains on the first eligible line.
        $paid = self::allocate($paidTarget, $rawPaid, $capacities, true);
        $discountTarget = $chargedTarget - $paidTarget;
        $discountCapacities = array();
        $eligibleCapacity = 0;
        foreach ($capacities as $index => $capacity) {
            $discountCapacities[$index] = $discounts[$index] > 0 ? $capacity - $paid[$index] : 0;
            $eligibleCapacity += $discountCapacities[$index];
        }

        // A paid line rounded up can occupy the last cent needed for its coupon.
        // Move that cent to a non-discounted line which rounded down, keeping
        // free delivery/free products at zero and both global totals unchanged.
        while ($eligibleCapacity < $discountTarget) {
            $source = null;
            $destination = null;
            $sourceError = -INF;
            $destinationError = INF;
            foreach ($paid as $index => $value) {
                $error = $value - $rawPaid[$index] * 100;
                if ($discounts[$index] > 0 && $value > 0 && $error > $sourceError) {
                    $source = $index;
                    $sourceError = $error;
                }
                if ($discounts[$index] == 0 && $rawPaid[$index] > 0 && $value < $capacities[$index] && $error < $destinationError) {
                    $destination = $index;
                    $destinationError = $error;
                }
            }
            if ($source === null || $destination === null) {
                throw new \LogicException('Coupon rounding cannot preserve the charged and paid totals.');
            }
            $paid[$source]--;
            $paid[$destination]++;
            $discountCapacities[$source]++;
            $eligibleCapacity++;
        }

        $allocation = self::allocate($discountTarget, $discounts, $discountCapacities);

        // Independently assigning a paid remainder and a coupon remainder can
        // leave one charged line two cents below its nearest raw amount. Move
        // a paid/coupon cent from another line, preserving all three totals and
        // leaving both zero-paid components and excluded coupon rows untouched.
        foreach ($charged as $recipient => $rawCharge) {
            $minimum = max(0, self::cents($rawCharge) - 1);
            while ($paid[$recipient] + $allocation[$recipient] < $minimum) {
                $moves = array();
                foreach ($charged as $donor => $donorCharge) {
                    $donorTotal = $paid[$donor] + $allocation[$donor];
                    if ($donor === $recipient || $donorTotal <= max(0, self::cents($donorCharge) - 1)) {
                        continue;
                    }
                    $error = $donorTotal - $donorCharge * 100;
                    if ($rawPaid[$recipient] > 0 && $paid[$recipient] < self::cents($rawPaid[$recipient]) + 1
                        && $paid[$donor] > max(0, self::cents($rawPaid[$donor]) - 1)) {
                        $moves[] = array('donor' => $donor, 'kind' => 0, 'error' => $error);
                    }
                    if ($discounts[$recipient] > 0 && $allocation[$donor] > 0) {
                        $moves[] = array('donor' => $donor, 'kind' => 1, 'error' => $error);
                    }
                }
                usort($moves, function (array $left, array $right): int {
                    if ($left['kind'] !== $right['kind']) {
                        return $left['kind'] <=> $right['kind'];
                    }
                    if (abs($left['error'] - $right['error']) > 0.000000001) {
                        return $left['error'] > $right['error'] ? -1 : 1;
                    }
                    return $left['donor'] <=> $right['donor'];
                });
                if (!$moves) {
                    throw new \LogicException('Coupon rounding cannot reconcile an individual charged amount.');
                }
                $move = $moves[0];
                if ($move['kind'] === 0) {
                    $paid[$move['donor']]--;
                    $paid[$recipient]++;
                } else {
                    $allocation[$move['donor']]--;
                    $allocation[$recipient]++;
                }
            }
        }

        foreach ($indexes as $position => $index) {
            $snapshot['products'][$index]['coupon_type'] = $snapshot['type'];
            $snapshot['products'][$index]['charged_amount'] = ($paid[$position] + $allocation[$position]) / 100;
            $snapshot['products'][$index]['discount_amount'] = $allocation[$position] / 100;
            $snapshot['products'][$index]['net_total'] = $paid[$position] / 100;
            $rawTotal = (float) $snapshot['products'][$index]['price'] * (int) $snapshot['products'][$index]['quantity'];
            $snapshot['products'][$index]['discount_percent'] = $rawTotal > 0
                ? round(($allocation[$position] / 100) / $rawTotal * 100, 4)
                : 0.0;
        }

        $snapshot['shipping_discount_amount'] = $allocation[count($indexes)] / 100;
        $snapshot['shipping_net_total'] = $paid[count($indexes)] / 100;
        $snapshot['shipping_amount'] = ($paid[count($indexes)] + $allocation[count($indexes)]) / 100;
        $snapshot['allocated_amount'] = $discountTarget / 100;
        $snapshot['paid_total'] = $paidTarget / 100;
        $snapshot['charged_total'] = $chargedTarget / 100;

        return $snapshot;
    }

    /** Return a finalized gross-price copy without changing the saved net data. */
    public static function taxInclusive(array $snapshot): array
    {
        self::validateSnapshot($snapshot);
        $taxDiscount = 0;
        foreach ($snapshot['products'] as &$product) {
            $priceTax = isset($product['price_tax']) ? self::number($product['price_tax'], 'price_tax') : 0;
            $baseTax = isset($product['base_price_tax']) ? self::number($product['base_price_tax'], 'base_price_tax') : 0;
            $discountTax = isset($product['discount_tax']) ? self::number($product['discount_tax'], 'discount_tax') : 0;
            $product['price'] = self::number(self::required($product, 'price'), 'price') + $priceTax;
            $product['base_price'] = self::number(self::required($product, 'base_price'), 'base_price') + $baseTax;
            $product['discount'] = self::number(self::required($product, 'discount'), 'discount') + $discountTax;
            $taxDiscount += $discountTax;
        }
        unset($product);
        $shipping = self::number(self::required($snapshot, 'shipping_discount'), 'shipping_discount');
        $shippingTax = isset($snapshot['shipping_tax']) ? self::number($snapshot['shipping_tax'], 'shipping_tax') : 0;
        $shippingDiscountTax = isset($snapshot['shipping_discount_tax']) ? self::number($snapshot['shipping_discount_tax'], 'shipping_discount_tax') : 0;
        $snapshot['shipping_price'] = (isset($snapshot['shipping_price']) ? self::number($snapshot['shipping_price'], 'shipping_price') : $shipping) + $shippingTax;
        $snapshot['shipping_discount'] = $shipping + $shippingDiscountTax;
        $snapshot['amount'] = self::number(self::required($snapshot, 'amount'), 'amount') + $taxDiscount + $shippingDiscountTax;

        return self::finalize($snapshot);
    }

    /**
     * Recover an applied percentage from immutable checkout data. A saved
     * nominal rate is authoritative only while it agrees with the raw amount;
     * a checkout cap must retain the effective, cent-exact rebate instead.
     */
    public static function couponPercentage(array $product, string $type): ?float
    {
        if ($type !== 'P') {
            return null;
        }

        $quantity = self::integer(self::required($product, 'quantity'), 'quantity', 1);
        $price = self::number(self::required($product, 'price'), 'price');
        $discount = self::number(self::required($product, 'discount'), 'discount');
        $total = $price * $quantity;

        if (!is_finite($total)) {
            throw new \InvalidArgumentException('Coupon product amount is out of range.');
        }

        if ($total <= 0 || $discount <= 0) {
            return null;
        }

        if (array_key_exists('coupon_percentage', $product)) {
            $percentage = self::number($product['coupon_percentage'], 'coupon_percentage');
            if ($percentage > 100) {
                throw new \InvalidArgumentException('Coupon percentage must not exceed 100.');
            }

            $expected = $total * $percentage / 100;
            $tolerance = max(0.0000001, $expected * 0.000000001);

            return abs($discount - $expected) <= $tolerance ? $percentage : null;
        }

        // Historical snapshots predate the nominal field. Their raw discount
        // still records the applied rate before currency-cent allocation.
        $percentage = round($discount / $total * 100, 4);

        return $percentage <= 100 ? $percentage : null;
    }

    /**
     * Build rebate groups when rounding assigns different unit cents. Large
     * quantities are chunked so unit rounding and line rounding give the same
     * cents, despite Luceed's four-decimal percentage precision.
     */
    public static function buildLines(array $product): array
    {
        $quantity = self::integer(self::required($product, 'quantity'), 'quantity', 1);
        $price = self::number(self::required($product, 'price'), 'price');
        $rawBase = self::number(self::required($product, 'base_price'), 'base_price');
        $base = self::cents($rawBase) / 100;
        $discount = self::number(self::required($product, 'discount_amount'), 'discount_amount');
        $net = self::number(self::required($product, 'net_total'), 'net_total');
        $rawChargedCents = self::cents($price * $quantity);
        $chargedAmount = isset($product['charged_amount'])
            ? self::number($product['charged_amount'], 'charged_amount')
            : $rawChargedCents / 100;
        $chargedCents = self::cents($chargedAmount);
        $discountCents = self::cents($discount);
        $netCents = self::cents($net);

        if (abs($chargedAmount - $chargedCents / 100) > 0.0000001 || abs($discount - $discountCents / 100) > 0.0000001 || abs($net - $netCents / 100) > 0.0000001) {
            throw new \InvalidArgumentException('Finalized coupon amounts must contain whole cents.');
        }

        if (abs($chargedCents - $rawChargedCents) > 1 || $discountCents > $chargedCents || $netCents !== $chargedCents - $discountCents) {
            throw new \LogicException('Finalized coupon product totals are inconsistent.');
        }

        $unitCents = intdiv($netCents, $quantity);
        $higherQuantity = $netCents % $quantity;
        // A four-decimal checkout unit may round up on only some units. In that
        // case the rounded original price must cover the highest paid unit.
        $base = max($base, ($unitCents + ($higherQuantity ? 1 : 0)) / 100);
        $groups = array();

        if ($quantity > $higherQuantity) {
            $groups[] = array($quantity - $higherQuantity, $unitCents);
        }

        if ($higherQuantity) {
            $groups[] = array($higherQuantity, $unitCents + 1);
        }

        $lines = array();
        $exportedCents = 0;
        $type = isset($product['coupon_type']) ? $product['coupon_type'] : '';
        if (!is_string($type)) {
            throw new \InvalidArgumentException('Coupon product type must be a string.');
        }
        $percentage = self::couponPercentage($product, $type);
        $nominalRebate = $percentage !== null && $rawBase > 0
            ? round(100 * (1 - ($price / $rawBase) * (1 - $percentage / 100)), 4)
            : null;

        foreach ($groups as $group) {
            $rebate = null;
            if ($nominalRebate !== null && $nominalRebate >= 0 && $nominalRebate <= 100) {
                $nominalUnit = $base * (1 - $nominalRebate / 100);
                // Preserve both currency-first and multiplication-first rounding
                // at half-cent boundaries, for the unit and its whole group.
                if (self::cents($nominalUnit) === $group[1]
                    && (int) round($nominalUnit * 100, 0, PHP_ROUND_HALF_UP) === $group[1]
                    && self::cents($nominalUnit * $group[0]) === $group[1] * $group[0]
                    && (int) round($nominalUnit * $group[0] * 100, 0, PHP_ROUND_HALF_UP) === $group[1] * $group[0]) {
                    $rebate = $nominalRebate;
                }
            }
            if ($rebate === null) {
                $rebate = self::rebateForUnit($base, $group[1]);
            }
            $rawUnit = $base * (1 - $rebate / 100);
            $unitError = abs($rawUnit - $group[1] / 100);
            $chunkLimit = $unitError > 0
                ? max(1, min($group[0], (int) floor(0.004999999 / $unitError)))
                : $group[0];
            $remainingQuantity = $group[0];

            while ($remainingQuantity) {
                $chunk = min($remainingQuantity, $chunkLimit);

                // Also check the multiplication itself to cover floating-point
                // boundaries when an otherwise exact unit is multiplied.
                while ($chunk > 1 && self::cents($rawUnit * $chunk) !== $group[1] * $chunk) {
                    $chunk--;
                }
                if (self::cents($rawUnit * $chunk) !== $group[1] * $chunk) {
                    throw new \LogicException('Luceed line rounding cannot reproduce the paid amount.');
                }

                $lines[] = array(
                    'kolicina' => $chunk,
                    'cijena' => $base,
                    'rabat' => $rebate,
                );
                $exportedCents += self::cents($rawUnit) * $chunk;
                $remainingQuantity -= $chunk;
            }
        }

        if ($exportedCents !== $netCents) {
            throw new \LogicException('Luceed rebate lines do not reproduce the paid amount.');
        }

        return $lines;
    }

    private static function validateSnapshot(array $snapshot): void
    {
        if (!isset($snapshot['version']) || $snapshot['version'] !== 1) {
            throw new \InvalidArgumentException('Unsupported coupon snapshot version.');
        }

        if (!isset($snapshot['code']) || !is_string($snapshot['code']) || trim($snapshot['code']) === '') {
            throw new \InvalidArgumentException('Coupon snapshot is missing its code.');
        }

        if (!isset($snapshot['type']) || !in_array($snapshot['type'], array('P', 'F'), true)) {
            throw new \InvalidArgumentException('Coupon snapshot has an invalid type.');
        }

        if (!isset($snapshot['products']) || !is_array($snapshot['products']) || !$snapshot['products']) {
            throw new \InvalidArgumentException('Coupon snapshot must contain all order products.');
        }
    }

    /**
     * Capped proportional quotas followed by stable largest-remainder rounding.
     */
    private static function allocate(int $target, array $weights, array $capacities, bool $reverseTies = false): array
    {
        $allocation = array_fill(0, count($weights), 0);
        $active = array();
        $capacity = 0;

        foreach ($weights as $index => $weight) {
            if ($weight > 0 && $capacities[$index] > 0) {
                $active[$index] = $weight;
                $capacity += $capacities[$index];
            }
        }

        if ($target > $capacity) {
            throw new \LogicException('Coupon amount exceeds the charged eligible amount.');
        }

        $remaining = $target;

        while ($remaining && $active) {
            $weightSum = array_sum($active);
            $capped = array();

            foreach ($active as $index => $weight) {
                if ($remaining * ($weight / $weightSum) > $capacities[$index]) {
                    $capped[] = $index;
                }
            }

            if ($capped) {
                foreach ($capped as $index) {
                    $allocation[$index] = $capacities[$index];
                    $remaining -= $allocation[$index];
                    unset($active[$index]);
                }

                continue;
            }

            $remainders = array();
            $floorSum = 0;

            foreach ($active as $index => $weight) {
                $quota = $remaining * ($weight / $weightSum);
                $floor = min($capacities[$index], (int) floor($quota));
                $allocation[$index] = $floor;
                $floorSum += $floor;
                $remainders[] = array('index' => $index, 'fraction' => $quota - $floor);
            }

            usort($remainders, function (array $left, array $right) use ($reverseTies): int {
                if (abs($left['fraction'] - $right['fraction']) < 0.000000001) {
                    return $reverseTies ? $right['index'] <=> $left['index'] : $left['index'] <=> $right['index'];
                }

                return $left['fraction'] > $right['fraction'] ? -1 : 1;
            });

            $remaining -= $floorSum;

            foreach ($remainders as $remainder) {
                $index = $remainder['index'];

                if ($remaining && $allocation[$index] < $capacities[$index]) {
                    $allocation[$index]++;
                    $remaining--;
                }
            }

            break;
        }

        if ($remaining || array_sum($allocation) !== $target) {
            throw new \LogicException('Coupon cent allocation could not be reconciled.');
        }

        return $allocation;
    }

    private static function rebateForUnit(float $base, int $unitCents): float
    {
        if ($unitCents > self::cents($base)) {
            throw new \LogicException('Paid unit price exceeds its original checkout price.');
        }

        if ($base == 0.0) {
            return 0.0;
        }

        $rebate = round(100 * (1 - ($unitCents / 100) / $base), 4);

        // Adjacent percentages also cover floating-point ties at half a cent.
        foreach (array(0, -1, 1, -2, 2) as $adjustment) {
            $candidate = round($rebate + $adjustment / 10000, 4);

            if ($candidate < 0 || $candidate > 100) {
                continue;
            }

            if (self::cents($base * (1 - $candidate / 100)) === $unitCents) {
                return $candidate;
            }
        }

        throw new \LogicException('Four-decimal Luceed rebate cannot reproduce the paid unit amount.');
    }

    private static function required(array $data, string $key)
    {
        if (!array_key_exists($key, $data)) {
            throw new \InvalidArgumentException('Missing coupon allocation field: ' . $key . '.');
        }

        return $data[$key];
    }

    private static function number($value, string $name): float
    {
        if (!is_numeric($value) || !is_finite((float) $value) || (float) $value < 0) {
            throw new \InvalidArgumentException('Invalid non-negative coupon allocation number: ' . $name . '.');
        }

        return (float) $value;
    }

    private static function integer($value, string $name, int $minimum): int
    {
        $number = self::number($value, $name);

        if ($number < $minimum || $number >= PHP_INT_MAX || floor($number) !== $number) {
            throw new \InvalidArgumentException('Invalid coupon allocation integer: ' . $name . '.');
        }

        return (int) $number;
    }

    private static function cents(float $amount): int
    {
        if (!is_finite($amount) || $amount < 0 || $amount * 100 >= PHP_INT_MAX - 1024) {
            throw new \InvalidArgumentException('Coupon allocation money value is out of range.');
        }

        // Currency round first: multiplying before rounding can turn 17.955
        // into 1795.499999999 and disagree with the checkout's displayed 17.96.
        return (int) round(round($amount, 2, PHP_ROUND_HALF_UP) * 100, 0, PHP_ROUND_HALF_UP);
    }

    private static function ceilCents(float $amount): int
    {
        self::cents($amount);
        // 0.07 * 100 can be 7.00000000000000089. Remove binary dust rather
        // than inventing an eighth cent of capacity for an exact-cent charge.
        return (int) ceil(round($amount * 100, 8));
    }
}
