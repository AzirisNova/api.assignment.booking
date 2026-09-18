# Mantle Booking API

Symfony 7.4 / PHP 8.3+ / PostgreSQL 16 booking API.

## Setup

```bash
composer install
```

Create the local database and confirm the credentials in `.env.local` match
your Postgres 16 instance:

```bash
createdb mantle_booking
```

## Running tests

```bash
php bin/phpunit
```

Integration and functional tests need a reachable Postgres database — DAMA
DoctrineTestBundle wraps each test in a transaction and rolls it back
afterwards, so they won't leave junk data.

