-- AP3 audit metadata migration
-- Scope: the five PHP CRUD tables only. This keeps the database at 12 tables.
-- Existing rows receive migration-time timestamps as an audit baseline; they are
-- not represented as the rows' historical creation or modification dates.
-- Run as one transaction. The PHP CRUD code should supply created_by/updated_by
-- from the authenticated session username on INSERT and updated_by on UPDATE.

BEGIN;

ALTER TABLE public.farmer
    ADD COLUMN created_at TIMESTAMPTZ,
    ADD COLUMN updated_at TIMESTAMPTZ,
    ADD COLUMN created_by TEXT,
    ADD COLUMN updated_by TEXT;

ALTER TABLE public.farm
    ADD COLUMN created_at TIMESTAMPTZ,
    ADD COLUMN updated_at TIMESTAMPTZ,
    ADD COLUMN created_by TEXT,
    ADD COLUMN updated_by TEXT;

ALTER TABLE public.crop
    ADD COLUMN created_at TIMESTAMPTZ,
    ADD COLUMN updated_at TIMESTAMPTZ,
    ADD COLUMN created_by TEXT,
    ADD COLUMN updated_by TEXT;

ALTER TABLE public.planting
    ADD COLUMN created_at TIMESTAMPTZ,
    ADD COLUMN updated_at TIMESTAMPTZ,
    ADD COLUMN created_by TEXT,
    ADD COLUMN updated_by TEXT;

ALTER TABLE public.harvest
    ADD COLUMN created_at TIMESTAMPTZ,
    ADD COLUMN updated_at TIMESTAMPTZ,
    ADD COLUMN created_by TEXT,
    ADD COLUMN updated_by TEXT;

-- These baselines use one transaction timestamp. No earlier audit timestamps
-- exist in the approved schema, so historical creation times cannot be inferred.
UPDATE public.farmer
SET created_at = transaction_timestamp(), updated_at = transaction_timestamp();
UPDATE public.farm
SET created_at = transaction_timestamp(), updated_at = transaction_timestamp();
UPDATE public.crop
SET created_at = transaction_timestamp(), updated_at = transaction_timestamp();
UPDATE public.planting
SET created_at = transaction_timestamp(), updated_at = transaction_timestamp();
UPDATE public.harvest
SET created_at = transaction_timestamp(), updated_at = transaction_timestamp();

ALTER TABLE public.farmer
    ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN created_at SET NOT NULL,
    ALTER COLUMN updated_at SET NOT NULL;

ALTER TABLE public.farm
    ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN created_at SET NOT NULL,
    ALTER COLUMN updated_at SET NOT NULL;

ALTER TABLE public.crop
    ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN created_at SET NOT NULL,
    ALTER COLUMN updated_at SET NOT NULL;

ALTER TABLE public.planting
    ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN created_at SET NOT NULL,
    ALTER COLUMN updated_at SET NOT NULL;

ALTER TABLE public.harvest
    ALTER COLUMN created_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN updated_at SET DEFAULT CURRENT_TIMESTAMP,
    ALTER COLUMN created_at SET NOT NULL,
    ALTER COLUMN updated_at SET NOT NULL;

CREATE FUNCTION public.ap3_set_audit_fields()
RETURNS TRIGGER
LANGUAGE plpgsql
AS $audit$
BEGIN
    IF TG_OP = 'INSERT' THEN
        NEW.created_at := CURRENT_TIMESTAMP;
        NEW.updated_at := NEW.created_at;
        NEW.updated_by := COALESCE(NEW.updated_by, NEW.created_by);
    ELSE
        NEW.created_at := OLD.created_at;
        NEW.created_by := OLD.created_by;
        NEW.updated_at := clock_timestamp();
    END IF;

    RETURN NEW;
END;
$audit$;

CREATE TRIGGER farmer_audit_fields_biu
BEFORE INSERT OR UPDATE ON public.farmer
FOR EACH ROW EXECUTE FUNCTION public.ap3_set_audit_fields();

CREATE TRIGGER farm_audit_fields_biu
BEFORE INSERT OR UPDATE ON public.farm
FOR EACH ROW EXECUTE FUNCTION public.ap3_set_audit_fields();

CREATE TRIGGER crop_audit_fields_biu
BEFORE INSERT OR UPDATE ON public.crop
FOR EACH ROW EXECUTE FUNCTION public.ap3_set_audit_fields();

CREATE TRIGGER planting_audit_fields_biu
BEFORE INSERT OR UPDATE ON public.planting
FOR EACH ROW EXECUTE FUNCTION public.ap3_set_audit_fields();

CREATE TRIGGER harvest_audit_fields_biu
BEFORE INSERT OR UPDATE ON public.harvest
FOR EACH ROW EXECUTE FUNCTION public.ap3_set_audit_fields();

COMMIT;
