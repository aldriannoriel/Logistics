# Pickup handoff for repository integration

The sorting-center pages can be tested without seller, buyer, admin, or rider UI pages. Feature tests create temporary users and parcels in an in-memory database; they do not create accounts in the development database.

## Contract to agree on before merging

- Authentication uses Laravel Sanctum. Sorting-center APIs require an approved sorting-center account; rider APIs require an approved rider account.
- A seller's pickup request has status `pending` and references a parcel and seller. The parcel's initial status is `PLACED` (or `PREPARING`).
- Sorting-center confirmation sets the request to `confirmed` and assigns both the request and parcel to that center. It does not mark the parcel as physically picked up.
- Legacy parcels with status `PENDING_PICKUP` are converted to `PLACED` when their pending request is confirmed.
- The rider accepts the confirmed pickup and then confirms physical pickup, setting the parcel to `PICKED_UP`.
- Order Management records arrival, setting the parcel to `AT_SORTING_CENTER`.
- Delivery Management assigns a sorting area; Rider Assignment assigns a delivery rider. Both retain `AT_SORTING_CENTER`.
- The rider starts delivery (`OUT_FOR_DELIVERY`), uploads proof, then completes delivery (`DELIVERED`) or records failure (`FAILED_RETURNED`).

Existing IDs, model relationships, authentication, and API payloads must be reconciled with the teammates' repository before merging. The seller endpoint now requires `buyer_id` referencing an existing user with role `buyer`; it accepts optional `delivery_street` and `delivery_house_number`. The merged seller application must derive the buyer from an order owned by the authenticated seller. This repository has no order model to verify that association. Delivery address fields are the recipient's address; the seller/rider integration must supply the actual collection location separately.

Apply the `2026_10_11_000001_add_delivered_at_to_parcels_table.php` migration. New completed deliveries record `delivered_at`; historical completion times remain null. Transaction reports use this timestamp instead of the delivery-start time. Reports group parcels by sorting date, not completion date.

Delivery-proof uploads use the private `local` disk. The API returns a relative `file_url` for an authenticated sorting-center file endpoint; fetch it with the Sanctum bearer token. `ProtectedProofImage.vue` converts authenticated responses to temporary blob URLs and releases them when unmounted. Proof files must never be placed in public storage. If importing existing proofs from another repository, move their files to the private disk before enabling this API.

Riders can list and accept pickups only for their registered sorting center. Existing confirmed requests and parcels must have matching center IDs. Monitoring accepts only `AT_SORTING_CENTER`, `OUT_FOR_DELIVERY`, `DELIVERED`, and `FAILED_RETURNED`; pickup and arrival are handled on their respective pages.

The sorting-center pickup list is paginated at 25 requests per page. Its response is `{ data: { data: [...], current_page, last_page, total, ... } }`; use `page` and `status=pending|confirmed`. The frontend refreshes the list after confirmation and moves back a page if the final page becomes empty.

## Verification

Run `php artisan test` and `npm.cmd run test:frontend`. `RiderPickupFlowTest` covers a legacy pending request through sorting-center approval, rider acceptance and pickup, arrival, and visibility in the sorting queue. Other feature tests cover sorting, assignment, private proof files, delivery completion, reports, monitoring, and messages. Frontend tests cover out-of-order responses, missing parcel data, concurrent confirmation prevention, and pagination.

Without real or seeded requests and approved riders, the corresponding pages may be empty. No role UI or development authentication bypass is required for the automated tests. A browser walkthrough with the teammates' applications is still required after integration.
