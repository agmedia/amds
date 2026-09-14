# Klaviyo regression harness

This directory contains a no-framework regression harness for the Klaviyo
client and welcome-coupon state machine. It uses namespaced cURL doubles and
in-memory database fakes only: it never connects to Klaviyo or to a database.

Run it from the repository root:

```sh
php tests/klaviyo/run.php
```

The harness covers:

- event confirmation using both `welcome_code` and `source_event_id`, including
  one pagination cursor and the confirmation-time filter;
- stable Create Event `unique_id`, properties, and headers across retries;
- non-empty `KLAV_KEY` environment override of a legacy constant;
- independent profile consent, target-list membership, and suppression states;
- no immediate retry of failed asynchronous consent bulk requests;
- asynchronous subscribe confirmation followed by a newer unsubscribe;
- stale state-token protection, timed-out superseded-operation handling, and
  saturation of the wait-attempt counter at `65535`;
- refusal to process consent against a double-opt-in list;
- explicit Basel unsubscribe without requiring a local newsletter row;
- rejection of a deleted/revoked mapped coupon without recreating it;
- repair of a genuinely incomplete coupon reservation.
