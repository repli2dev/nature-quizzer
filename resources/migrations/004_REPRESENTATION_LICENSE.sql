ALTER TABLE "organism_representation" ADD "license" text NOT NULL;
COMMENT ON COLUMN "organism_representation"."license" IS 'License of the obtained representation.';