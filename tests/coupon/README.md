# Offline coupon regression tests

From the repository root, run:

```sh
php tests/coupon/run.php
```

The suite requires PHP 7.1 or newer and no added dependencies. It loads the real
coupon calculator, allocation helper, order writer and Luceed order exporter. Small in-memory
fixtures replace the cart, tax service and Eloquent models; the exporter is
created without its network client. It never bootstraps the shop, accesses its
database, reads credentials, creates real orders or contacts Luceed.

Coverage includes the supplied receipt examples (29.15 EUR allocated across seven
items, and 21.00 EUR at 20 percent), fixed and percentage coupons, eligibility,
existing sale exclusions, free delivery, mixed tax rates, total caps, fractional
percentages, quantities, cent rounding, product variants, duplicate and reordered
lines, invalid metadata, immutable prices and deleted coupons. Four hundred
repeatable generated baskets, including four-decimal prices, check money
conservation, quantity conservation and eligibility. Eighty additional mixed-tax
baskets compare the exported amounts with the actual gross payment. Both unit
rounding and extended-line rounding are verified, including large quantities.
Another 150 tiny-value baskets cover sub-cent prices, excluded items, fully
discounted lines and discounts whose effective cent amount is zero.

The allocation preserves the paid total after four-decimal database storage and
currency rounding. The raw coupon total remains unchanged; its allocated cent
amount can differ from independently rounding that total. For example, 19.95 EUR
at 10 percent stores a raw 1.9950 EUR discount, pays 17.96 EUR and assigns 1.99 EUR
of effective item discount. Net and gross allocations are captured separately.

Percentage coupons retain their saved nominal percentage when that percentage
reproduces the already allocated paid cents. The supplied 24 percent examples
cover all six prices (17.85, 12.60, 13.20, 75.99, 29.99 and 34.99 EUR) with a
140.31 EUR payment, and the five-line PDF with a 130.28 EUR payment. Historical
percentage metadata can recover the rate from its raw discount and price. Fixed
coupons keep their exact effective rebate. Tests also cover fractional rates,
caps, excluded items, combined sale discounts, tax, quantity chunks and coupon
changes/deletion. A rounding-sensitive pair of 10.02 EUR products verifies that
the nominal rate yields to exact cents when necessary to preserve the 15.23 EUR
payment.

It also exercises actual order creation/edit persistence, records new product IDs
in the stored JSON, rejects stale metadata, and reapplies the two coupon hooks
observed in this shop's local OCMOD cache against the updated source.

Deployment: apply database migration **37 before deploying the PHP changes**,
then refresh OpenCart **Extensions → Modifications** to rebuild all modification
caches (coupon, cart, order writer and admin order view). Existing orders without allocation metadata use
their saved promotional-code total; new orders retain their immutable per-item
allocation. Review the resulting sample invoice in Luceed before rolling out to
all sales channels.

The script exits with status 1 on any failure and prints the scenario and reason.
These offline checks validate exported payloads and calculations. A real Luceed
invoice/PDF, fiscal receipt or refund is outside their scope.

An additional integration check can run against a configured local MySQL server:

```sh
php tests/coupon/database.php
```

It requires a loopback database, clones fixture tables under a random prefix and
restricts application writes to those fixtures. It drops the fixture tables on
completion and never creates production orders or sends messages.
