ALTER TABLE "organism_representation" ADD "rights_holder" text NOT NULL;
COMMENT ON COLUMN "organism_representation"."rights_holder" IS 'Describes who is rights holders of this organism representation.';

ALTER TABLE "organism_representation" ADD "hash" character varying(128) NOT NULL;
COMMENT ON COLUMN "organism_representation"."hash" IS 'Hash of image for duplicity prevention';

ALTER TABLE "organism_representation" ADD CONSTRAINT "organism_representation_hash" UNIQUE ("hash");