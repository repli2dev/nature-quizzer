ALTER TABLE "organism_representation" ADD "license" text NULL;
COMMENT ON COLUMN "organism_representation"."license" IS 'License of the obtained representation.';