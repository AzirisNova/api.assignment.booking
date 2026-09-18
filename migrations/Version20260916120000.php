<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260916120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users, slots, bookings tables with the no-overlap exclusion constraint';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE EXTENSION IF NOT EXISTS btree_gist');

        $this->addSql(<<<'SQL'
            CREATE TABLE users (
                id             serial PRIMARY KEY,
                email          varchar(180) UNIQUE NOT NULL,
                password_hash  varchar(255) NOT NULL,
                full_name      varchar(120) NOT NULL,
                role           varchar(20) NOT NULL DEFAULT 'client',
                created_at     timestamptz NOT NULL DEFAULT now(),
                api_token      varchar(255) UNIQUE
            )
        SQL);

        $this->addSql(<<<'SQL'
            CREATE TABLE slots (
                id           serial PRIMARY KEY,
                date         date NOT NULL,
                start_time   time NOT NULL,
                end_time     time NOT NULL,
                is_active    boolean NOT NULL DEFAULT true,
                created_at   timestamptz NOT NULL DEFAULT now()
            )
        SQL);
        $this->addSql('CREATE INDEX idx_slots_date_active ON slots (date, is_active)');

        $this->addSql(<<<'SQL'
            CREATE TABLE bookings (
                id               serial PRIMARY KEY,
                user_id          integer NOT NULL REFERENCES users(id),
                slot_id          integer NOT NULL REFERENCES slots(id),
                start_datetime   timestamptz NOT NULL,
                end_datetime     timestamptz NOT NULL,
                status           varchar(20) NOT NULL DEFAULT 'confirmed',
                created_at       timestamptz NOT NULL DEFAULT now(),
                CHECK (end_datetime > start_datetime),
                during           tstzrange GENERATED ALWAYS AS (tstzrange(start_datetime, end_datetime, '[)')) STORED,
                EXCLUDE USING gist (
                    slot_id WITH =,
                    during WITH &&
                ) WHERE (status = 'confirmed')
            )
        SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE bookings');
        $this->addSql('DROP TABLE slots');
        $this->addSql('DROP TABLE users');
    }
}
