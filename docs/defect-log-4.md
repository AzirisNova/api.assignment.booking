# §4 Defect-Analysis Log — Booking Overlap Check Silently Fails for Non-UTC Request Offsets

This is a real defect found during end-to-end manual verification after the
booking API was built and the automated suite was already green — not the
illustrative exact-start-time-equality bug sketched as an example in §4 of
the assignment plan. It's a distinct, independently discovered issue in the
same method (`BookingRepository::hasOverlap()`), so it makes a stronger
portfolio artefact: it wasn't seeded, it was found.

## 4.1 Context

**`POST /api/bookings` returned `500 Internal Server Error` instead of the
required `409 Conflict` (FR4) when a request genuinely overlapped an
existing confirmed booking on the same slot and the request body's
`start`/`end` used a non-UTC ISO-8601 offset** — e.g. `+01:00`, which is
exactly what a real client in the UK would send for a booking made during
BST, or any client not in UTC.

It was found by manually exercising the running API (list slots → book →
attempt a conflicting booking → view → cancel) rather than by reading code,
per the "test the golden path in a browser/curl before calling it done"
practice — the automated suite alone did not catch it (see §4.3 for why).

## 4.2 Failing scenario / observed failure

```bash
# Existing confirmed booking already created for slot 1: 2026-10-05T10:00:00+01:00 → 10:30+01:00
curl -H "Authorization: Bearer $TOKEN" -H "Content-Type: application/json" \
  -d '{"slotId":1,"start":"2026-10-05T10:15:00+01:00","end":"2026-10-05T10:45:00+01:00"}' \
  http://127.0.0.1:8099/api/bookings
```

```
HTTP/1.1 500 Internal Server Error
...
An exception occurred while executing a query: SQLSTATE[23P01]: <<Unknown error>>: 7
ERROR: conflicting key value violates exclusion constraint "bookings_slot_id_during_excl"
DETAIL: Key (slot_id, during)=(1, ["2026-10-05 09:15:00+00","2026-10-05 09:45:00+00"))
        conflicts with existing key (slot_id, during)=(1, ["2026-10-05 09:00:00+00","2026-10-05 09:30:00+00")).
```

Two things are visible in that one response:

1. The application-level pre-check (`BookingValidator` → `BookingRepository::hasOverlap()`)
   **failed to detect a real overlap** and let the request through to `flush()`.
2. Postgres's exclusion constraint (§2.2/§2.3's "belt and braces" layer) then
   correctly rejected the write — but the code only caught
   `Doctrine\DBAL\Exception\UniqueConstraintViolationException` (SQLSTATE
   `23505`), and an exclusion-constraint violation is a different DBAL
   exception (`DriverException`, SQLSTATE `23P01`), so it wasn't converted to
   `OverlappingBookingException` and surfaced as a raw 500 instead of a clean
   409. **Two bugs stacked on the same request path.**

Isolated, direct reproduction of bug (1) against the repository method
(bypassing HTTP entirely), confirming it wasn't an artefact of the web layer.
Existing confirmed booking is stored as `09:00–09:30 UTC` (per the DB dump
above); the new request's *true* UTC instant is `09:15–09:45`, which
genuinely overlaps it:

```php
$repo->hasOverlap(
    $slot,
    new \DateTimeImmutable('2026-10-05T10:15:00+01:00'), // = 09:15 UTC
    new \DateTimeImmutable('2026-10-05T10:45:00+01:00'), // = 09:45 UTC
);
// => false   (WRONG — 09:15–09:45 UTC genuinely overlaps the existing 09:00–09:30 UTC booking)
```

## 4.3 Diagnosis

`hasOverlap()`'s query parameters were bound without an explicit Doctrine
type:

```php
// BUGGY VERSION
->setParameter('start', $start)
->setParameter('end', $end)
```

`$start`/`$end` are `\DateTimeImmutable` instances, and `Booking::$startDatetime`/
`$endDatetime` are mapped as `datetimetz_immutable` (timezone-aware, per
§2.2's note on matching Postgres's `timestamptz`). Without an explicit
parameter type, Doctrine has to *infer* one from the PHP value, and the
inferred type does not correctly preserve the object's UTC offset when
binding it — it behaves as though the offset were dropped and the
**wall-clock digits** were bound as if they were already UTC.

That only produces a wrong answer when the offset is non-zero, which is why
the pre-existing automated tests didn't catch it:

- The unit tests (`BookingValidatorTest`) mock the repository, so they never
  exercise the real query at all.
- The integration tests (`BookingRepositoryOverlapTest`) construct all their
  `DateTimeImmutable`s from offset-less strings (e.g. `'2026-09-10 10:30'`),
  which PHP parses in the default timezone (UTC on this box) — so dropping a
  UTC offset that was already zero is a no-op, and those tests pass
  regardless of the bug.
- The one functional test that *does* send a `+01:00` offset
  (`testOverlappingBookingReturns409NotCreated`) happened to pick times where
  the correct answer and the "offset dropped" answer both land on
  "overlaps" — the 1-hour shift wasn't large enough relative to the chosen
  booking window to flip the result, so the bug was invisible to it too.

I confirmed the mechanism directly by running the exact same query twice
against the same database state, once with the implicit (buggy) parameter
type and once with an explicit one:

```php
// existing confirmed booking, stored correctly as 10:00–11:00 UTC
$start = new \DateTimeImmutable('2026-09-10T11:15:00+01:00'); // = 10:15 UTC — inside the booking
$end   = new \DateTimeImmutable('2026-09-10T11:45:00+01:00'); // = 10:45 UTC — inside the booking

// implicit type (buggy):
->setParameter('start', $start)->setParameter('end', $end)
// => hasOverlap() returns false   (WRONG — this genuinely overlaps)

// explicit type (fixed):
->setParameter('start', $start, Types::DATETIMETZ_IMMUTABLE)
->setParameter('end', $end, Types::DATETIMETZ_IMMUTABLE)
// => hasOverlap() returns true    (correct)
```

This isolates the defect to parameter binding specifically — the DQL and the
interval-overlap logic (`startDatetime < :end AND endDatetime > :start`,
§3.1/§4.3 of the plan) were already correct; only the *type* of the bound
value was wrong.

## 4.4 Corrected code

**`src/Repository/BookingRepository.php`** — bind `$start`/`$end` with an
explicit type matching the mapped column type:

```php
public function hasOverlap(Slot $slot, \DateTimeImmutable $start, \DateTimeImmutable $end): bool
{
    return (bool) $this->createQueryBuilder('b')
        ->select('COUNT(b.id)')
        ->andWhere('b.slot = :slot')
        ->andWhere('b.status = :status')
        ->andWhere('b.startDatetime < :end')
        ->andWhere('b.endDatetime > :start')
        ->setParameter('slot', $slot)
        ->setParameter('status', Booking::STATUS_CONFIRMED)
        ->setParameter('start', $start, Types::DATETIMETZ_IMMUTABLE)
        ->setParameter('end', $end, Types::DATETIMETZ_IMMUTABLE)
        ->getQuery()
        ->getSingleScalarResult() > 0;
}
```

**`src/Service/BookingService.php`** — the "belt and braces" catch (§2.3) was
also too narrow: it only caught `UniqueConstraintViolationException`
(SQLSTATE `23505`), but Postgres's `EXCLUDE` constraint raises SQLSTATE
`23P01` (`exclusion_violation`), which DBAL surfaces as a plain
`DriverException`. Widened to check the SQLSTATE directly so *either*
violation converts to a clean 409 rather than only one of them:

```php
try {
    $this->em->flush();
} catch (\Doctrine\DBAL\Exception $e) {
    if (in_array($e->getSQLState(), ['23505', '23P01'], true)) {
        throw new OverlappingBookingException(previous: $e);
    }
    throw $e;
}
```

With the first fix in place, the pre-check now correctly rejects genuinely
overlapping requests before ever reaching `flush()`, so in normal operation
this second path is now only reachable via a true concurrent race (two
requests passing the pre-check for the same window before either commits) —
exactly the scenario §2.3 describes the exclusion constraint as the real
guarantee for. The widened catch means even that race now still comes back
as 409, not 500.

## 4.5 Regression test

The existing functional test (`testOverlappingBookingReturns409NotCreated`)
was not sensitive enough to catch this, so a new integration test was added
targeting the exact divergence between the correct and buggy behaviour —
deliberately chosen so a real overlap only shows up once the UTC offset is
correctly accounted for:

```php
// tests/Integration/Repository/BookingRepositoryOverlapTest.php
public function testOverlapIsDetectedWhenRequestCarriesNonUtcOffset(): void
{
    $overlap = $this->repository->hasOverlap(
        $this->slot,
        new \DateTimeImmutable('2026-09-10T11:15:00+01:00'), // = 10:15 UTC
        new \DateTimeImmutable('2026-09-10T11:45:00+01:00'), // = 10:45 UTC
    );
    self::assertTrue($overlap);
}
```

Verified failing against the pre-fix code:

```
1) App\Tests\Integration\Repository\BookingRepositoryOverlapTest::testOverlapIsDetectedWhenRequestCarriesNonUtcOffset
Failed asserting that false is true.
Tests: 1, Assertions: 1, Failures: 1.
```

And passing against the fix, alongside the full suite:

```
$ php bin/phpunit
..................                                                18 / 18 (100%)
Time: 00:02.385, Memory: 50.50 MB
OK (18 tests, 19 assertions)
```

## 4.6 Wider implication worth a line in the release recommendation (§6)

Any other place in the codebase that binds a `\DateTimeImmutable` as a
Doctrine query parameter *without* an explicit type is at risk of the same
silent-mismatch class of bug against `datetimetz_immutable`/`timestamptz`
columns — worth a quick `grep -rn "setParameter.*Immutable\|new \\\\DateTimeImmutable" src/` sweep
before sign-off to confirm `hasOverlap()` was the only offender.
