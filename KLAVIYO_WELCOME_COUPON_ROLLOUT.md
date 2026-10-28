# Klaviyo welcome coupon deployment and cutover

## What is prepared

- API revision: `2026-07-15`
- Metric: `Welcome Coupon Ready` (`R6Fxmf`)
- Existing signup metric: `Subscribed to List` (`UBgnRd`)
- Draft flow: `Newsletter Signup - Welcome Coupon Ready v1` (`UiZgQ8`)
- Source template: `AMDS Welcome Coupon - Event v1` (`SSwaPn`)
- Flow template copy: `XTR3R6`
- Existing flow: `Newsletter Signup` (`TVD7vF`)
- Existing webhook action, which must remain enabled: `91868065`
- Existing email action to retire during cutover: `91873884`

The new template reads `{{ event.welcome_code }}` and has Smart Sending disabled. The existing live flow remains unchanged until the application has been deployed and tested.

## Secrets and Klaviyo API key

The following are different credentials and must not be reused:

- `KLAV_KEY`: Klaviyo private API key used by the application, configured as an environment variable or PHP constant.
- `KLAV_ID`: Klaviyo newsletter list ID. The currently verified value is `SfJt6c` (`Newsletter`, `single_opt_in`).
- `WELCOME_COUPON_WEBHOOK_TOKEN`: random shared secret used only to authenticate Klaviyo action `91868065` against the OpenCart coupon endpoint.

Create a new scoped Klaviyo private API key with these permissions:

- `events:write`
- `events:read`
- `lists:read`
- `profiles:read`
- `profiles:write`
- `lists:write`
- `subscriptions:write`

Keep the old Klaviyo key active during deployment as a rollback credential. Switch `KLAV_KEY` only after the new code is present, then run the smoke test. Revoke the old key only after the email cutover and 48-hour stabilization window have both succeeded.

Generate a long random webhook token and configure it as the `WELCOME_COUPON_WEBHOOK_TOKEN` server environment variable or PHP constant. Never commit either secret.

The web runtime and CLI cron do not necessarily inherit the same environment variables. Put `KLAV_KEY` and `KLAV_ID` in the protected OpenCart configuration loaded by both `upload/index.php` runtimes, or explicitly provide them to both PHP-FPM/Apache and cron. The webhook token is required by the web endpoint. A non-empty `KLAV_KEY` environment variable intentionally overrides an older PHP constant; the constant is the fallback. Verify the effective credential through a harmless API read from both runtimes before cutover, without printing the key.

## Deployment order

1. Back up the database and record the current application revision.
2. Confirm that production is a single OpenCart storefront using one shared newsletter list:

   ```sql
   SELECT store_id, name, url, ssl FROM oc_store;
   ```

   This release intentionally uses one queue entry per email and has no `store_id`. Stop the deployment if production has additional stores that use different Klaviyo lists.
3. Confirm the production PHP CLI is version 7.3 or newer and has the cURL plus the same database extension/configuration as the web runtime.
4. Create the new scoped Klaviyo private API key, but do not switch `KLAV_KEY` yet.
5. Verify that `KLAV_ID=SfJt6c` still resolves to `Newsletter` with `opt_in_process=single_opt_in`. Also verify that existing flow `TVD7vF` is metric-triggered by `Subscribed to List` (`UBgnRd`) and that its trigger filter is scoped to this Newsletter list. The list itself has no direct flow-trigger relationship. The worker refuses a double-opt-in list because asynchronous confirmation would depend on a recipient action.
6. Generate and configure `WELCOME_COUPON_WEBHOOK_TOKEN`, then reload PHP-FPM/Apache so the application can read it. Also make `KLAV_KEY` and `KLAV_ID` available to the CLI runtime as described above.
7. Run these migrations before publishing the PHP changes:
   - `storage/migrations/35_create_welcome_coupon_table.sql`
   - `storage/migrations/36_create_klaviyo_consent_queue.sql`
8. In Klaviyo webhook action `91868065`, add the new token as the `X-Webhook-Token` header. Keep the old `Authorization` header temporarily. The old controller prefers `Authorization`; the new controller prefers `X-Webhook-Token`, so both versions work during this step.
9. Deploy the contents of `upload/` using the project's normal release procedure.
10. In OpenCart Admin, refresh **Extensions > Modifications** and clear the theme/template cache. This is required because the generated modification copy otherwise shadows `catalog/model/account/customer.php`.
11. Install the newsletter consent worker cron described below and run it manually once.
12. Switch `KLAV_KEY` to the new scoped private key in both web and CLI runtimes and reload PHP.
13. Complete the controlled smoke test while the new event flow is still non-sending. Keep the old private key active as the rollback credential.
14. Perform the Klaviyo email cutover in the sequence below.
15. Monitor the cutover for 48 hours. Only after that stabilization window succeeds, revoke the old private key and remove the old `Authorization` header from action `91868065`.

The welcome mapping is written before the OpenCart coupon is created. This is intentional because the stock OpenCart coupon table may use MyISAM: if a request is interrupted before `coupon_id` is assigned, the next retry repairs the incomplete mapping and reuses the reserved code. A coupon that was later deleted from OpenCart is treated as revoked and is not recreated automatically.

## Newsletter consent worker

Run the worker every minute with the actual production PHP CLI binary. It must be PHP 7.3+ with cURL and the shop's database extension. The deployed `upload/` directory is the web root, so adapt `/path/to/web-root`, `/actual/php`, and the private log path:

```cron
* * * * * /actual/php /path/to/web-root/system/library/klaviyo/consent_cron.php www.amds.hr 443 >> /path/to/private-logs/klaviyo-consent.log 2>&1
```

Manual check:

```sh
/actual/php /path/to/web-root/system/library/klaviyo/consent_cron.php www.amds.hr 443
```

Create the private log directory first, give the cron user write access, and add log rotation. A redirection error prevents PHP from starting at all.

The worker is CLI-only, keeps running during OpenCart maintenance mode, and uses a database lock to prevent overlapping runs. It processes at most 50 due items per run. A bulk consent `202` is only recorded as submitted; a later run reads both profile consent and list membership before deleting the row. Subscription POST requests are not immediately repeated after an uncertain response. A newer subscribe/unsubscribe decision replaces the desired state but waits for any opposite in-flight operation to become observable, preventing out-of-order completion from restoring stale consent. Rows marked `superseded_operation_unconfirmed` intentionally remain queued and must trigger an operational alert.

Normal output is one JSON object per line. Exit code `0` means the run had no handled job failures or another worker owned the lock; `75` means one or more jobs failed and will be retried; unexpected worker failures return a non-zero code.

## Controlled smoke test

Use a real deliverable address that has never previously subscribed. Do not use `@example.com`, `@test.com`, or similar test domains because Klaviyo accepts those events with `202` and then silently drops them.

1. Confirm that `KLAV_ID=SfJt6c` resolves to the `Newsletter` list and is `single_opt_in`. Confirm separately that flow `TVD7vF` uses metric `Subscribed to List` (`UBgnRd`) and its trigger filter targets this list; prove the full path with this controlled subscription.
2. Submit one explicit newsletter opt-in through an OpenCart/Basel signup path.
3. Confirm one row appears in `oc_klaviyo_consent_queue`. Run the worker once and verify its JSON contains `submitted: 1`; the row must remain in-flight.
4. Wait at least 30 seconds, run the worker again (or let cron run), verify `confirmed: 1`, and then confirm the row is removed. If Klaviyo has not propagated the state yet, repeat the read-only confirmation run later.
5. Confirm the profile has email consent `SUBSCRIBED` and is a member of the expected Klaviyo list.
6. Let webhook action `91868065` run. Its first request intentionally returns `503 klaviyo_event_pending` after Klaviyo accepts the event. Confirm Klaviyo schedules a retry; the endpoint returns `200` only after the event is visible through the Events API.
7. Confirm exactly one row in `oc_welcome_coupon`, one matching row in `oc_coupon`, and one `Welcome Coupon Ready` event containing the same `welcome_code`.
8. Replay the webhook for the same address. It must return the same code and create no additional coupon or event.
9. Render/preview template `XTR3R6` for that event and confirm the code is visible.

An HTTP 202 response from Klaviyo means the event/job was accepted for asynchronous processing; wait for the profile/event/list state before declaring the test successful.

## Klaviyo cutover without automatic duplicate email

Perform this in a controlled low-traffic window. Temporarily pause public signup entry if practical, and record timestamps T0, T1, and T2.

1. In flow `UiZgQ8`, change its sole email action from **Draft** to **Manual** so incoming events enter **Needs Review**. Verify the action itself still says Manual. Do not use a bulk **Update all action statuses to Live** operation. Leave old email action `91873884` Live.
2. Send one controlled signup and confirm the new event email enters **Needs Review** with the correct code; do not send it. Confirm the old flow still sends its current email.
3. Inspect webhook action `91868065` analytics and resolve or record all **Waiting to Retry**, **Failed**, and **Skipped** items before proceeding.
4. At T0, set old email action `91873884` to **Manual**.
5. At T1, set the new event email action in flow `UiZgQ8` to **Live**.
6. At T2, set old email action `91873884` to **Draft**. Keep webhook action `91868065` live.
7. Reconcile the manual queues by timestamp:
   - Cancel new-flow manual items created before T0 because the old email was still live.
   - For T0-T1, send only the new event-template item and cancel the corresponding old item.
   - Cancel any old-flow manual items created from T1 onward because the new flow was live.
   - Never use **Send All**.
8. Resume public signup entry and perform one final never-before-subscribed test.

## Monitoring

Monitor the first 48 hours:

- OpenCart logs for `[welcome_coupon]` and `[klaviyo]`.
- Klaviyo webhook action `91868065` for non-2xx, Waiting to Retry, Failed, or Skipped executions.
- New flow delivery activity and the rendered coupon code.
- Pending consent jobs and failed coupon events:

```sql
SELECT COUNT(*) AS pending_consent, MIN(next_attempt_at) AS oldest_due
FROM oc_klaviyo_consent_queue;

SELECT klaviyo_consent_queue_id, desired_subscribed, in_flight_subscribed,
       attempts, submitted_at, next_attempt_at, last_http_status, last_error
FROM oc_klaviyo_consent_queue
ORDER BY next_attempt_at ASC;

SELECT welcome_coupon_id, code, event_sent_at, last_error, date_modified
FROM oc_welcome_coupon
WHERE event_sent_at IS NULL OR last_error IS NOT NULL
ORDER BY date_modified ASC;
```

## Rollback

1. Put the new event email action into **Manual** first; do not leave both emails Live.
2. Pause the consent cron. Preserve the worker files until queued changes have been inspected or drained; otherwise the scheduled command will fail every minute after a code rollback.
3. Restore and verify the old webhook `Authorization` header if it was removed.
4. Roll back the application release if required and refresh OpenCart Modifications/cache.
5. Set old email action `91873884` back to **Live** only after the legacy endpoint returns a body with `klaviyo_update=ok`, the matching OpenCart coupon exists, and the Klaviyo profile visibly contains the same `welcome_code`. HTTP 2xx alone is not sufficient. This requires the retained old private key (or another key verified to work with the legacy API).
6. Reconcile/cancel new-flow **Needs Review** items so no recipient receives both versions.

This rollback restores the previous behavior, including its known coupon/email timing race. Do not delete `oc_welcome_coupon` or `oc_klaviyo_consent_queue`; they preserve idempotency and pending consent changes for the next deployment attempt.

## Consent behavior and known exclusion

Bulk Klaviyo subscribe/unsubscribe is intentionally used as the store-wide marketing preference. The worker verifies both global email consent and membership in `KLAV_ID`; it does not treat list membership alone as consent. Confirm that `KLAV_ID` represents that same preference: subscribing can remove existing user/spam suppressions, while an explicit unsubscribe can unsubscribe the profile globally.

The optional Klarna `additional_checkbox` path is not included. Its model method has no caller in this codebase, so it is a dormant/incomplete acquisition path. If `klarna_checkout_additional_text_box` is enabled in production, disable that checkbox or scope a separate end-to-end Klarna consent implementation; never subscribe without receiving an explicit checked result.

The public Basel form retains the site's existing single-opt-in behavior. Before rollout, confirm that the endpoint is protected by the production edge/WAF rate limit or an equivalent bot control; otherwise a third party can submit another person's address. Do not deploy this single-opt-in path without that operational protection. Moving to double opt-in is a separate consent-flow change because this worker deliberately refuses double-opt-in lists.

## Official Klaviyo references

- https://developers.klaviyo.com/en/reference/create_event
- https://developers.klaviyo.com/en/reference/get_profiles
- https://developers.klaviyo.com/en/reference/get_profile
- https://developers.klaviyo.com/en/reference/get_events
- https://developers.klaviyo.com/en/reference/get_profiles_for_list
- https://developers.klaviyo.com/en/reference/bulk_subscribe_profiles
- https://developers.klaviyo.com/en/reference/bulk_unsubscribe_profiles
- https://developers.klaviyo.com/en/docs/how_to_add_a_webhook_action_to_a_flow
