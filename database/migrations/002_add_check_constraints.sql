-- Run these two read-only checks first. Run the ALTER statements below only
-- if both checks return zero rows.
-- SELECT harvest_id, quantity_kg FROM public.harvest WHERE quantity_kg <= 0;
-- SELECT farmer_id, contact_number FROM public.farmer WHERE contact_number IS NOT NULL AND contact_number !~ '^[0-9+() -]{7,20}$';
-- Migration 001 (audit fields) was already applied and must not be run again.

BEGIN;

ALTER TABLE public.harvest
    DROP CONSTRAINT harvest_quantity_kg_check;

ALTER TABLE public.harvest
    ADD CONSTRAINT harvest_quantity_kg_check CHECK (quantity_kg > 0);

ALTER TABLE public.farmer
    ADD CONSTRAINT farmer_contact_number_format_check
    CHECK (contact_number IS NULL OR contact_number ~ '^[0-9+() -]{7,20}$');

COMMIT;
