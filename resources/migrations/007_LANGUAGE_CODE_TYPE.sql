ALTER TABLE "language" ALTER "code" TYPE character varying, ALTER "code" DROP DEFAULT, ALTER "code" SET NOT NULL;

COMMENT ON COLUMN "language"."code" IS 'Code according to ISO 639-1 (or ISO 639-2 when 1 is not set)';