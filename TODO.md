# Known issues and deferred work

Problems that are understood but not yet fixed, with the reasoning for leaving
them. Both entries below were found on 3 September 2026 while fixing the
`emailsubscribe` Boolean bug released in v1.2.4.

## Field values are reformatted with `ucwords(strtolower())`

**Where:** `get_field_value()` in `includes/class-gravity-forms.php`

Every non-checkbox field value passes through `ucwords(strtolower($value))`
before it is sent to KulaHub. The intent was to tidy up names typed in all caps,
but it applies to every field type, so it corrupts data that is not a name:

| Submitted | Sent to KulaHub |
| --- | --- |
| `HG1 1JX` | `Hg1 1jx` |
| `james@blumilk.com` | `James@blumilk.com` |
| `ABC Ltd` | `Abc Ltd` |

Postcodes are the clearest damage: KulaHub receives them in a case no address
lookup expects.

**The fix:** format per field rather than globally. Apply title casing only to
name-shaped contact fields (`title`, `firstname`, `lastname`, `town`, `county`)
and pass everything else through untouched, or drop the formatting entirely and
let KulaHub normalise on ingest.

**Why it is deferred:** the change alters the data every existing install sends,
including fields that are not obviously broken today, so it needs a decision
about which fields should be normalised rather than a unilateral fix. Worth
confirming with KulaHub whether the API normalises on its side first, in which
case the formatting can simply be removed.

## `is_contact_field()` matches field IDs by substring

**Where:** `is_contact_field()` in `includes/class-gravity-forms.php`

The check uses `stripos($field_key, $contact_field) !== false`, so any field ID
that merely contains a contact field name is nested under `Contact` in the
payload. Adding `title` to the list in v1.2.4 widened this: a custom field mapped
to `coursetitle` now routes into `Contact` instead of the top level, where the
KulaHub API will ignore it.

**The fix:** compare the lower-cased field ID against the list exactly, using
`in_array(strtolower($field_key), $contact_fields, true)`.

**Why it is deferred:** an install that relies on the loose behaviour, for
example a field ID of `email_address` or `firstname2`, would silently stop
sending that value into `Contact` after the change. Check the field IDs in use
across the live integrations (Access Training via Blumilk is the most recent)
before tightening it, and note it as a breaking change in the release.
