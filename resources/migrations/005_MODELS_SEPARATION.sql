-- Create table for settings (available models)
CREATE TABLE "setting" (
  "id_setting" bigserial NOT NULL PRIMARY KEY,
  "name" character varying(255) NOT NULL,
  "ratio" bigint NOT NULL,
  "inserted" timestamptz NOT NULL,
  "updated" timestamptz NOT NULL
);

ALTER TABLE "setting" ADD CONSTRAINT "setting_name" UNIQUE ("name");
COMMENT ON COLUMN "setting"."name" IS 'Identifier of model from source code';
COMMENT ON COLUMN "setting"."ratio" IS 'Probability of assigning this setting to new user';
COMMENT ON TABLE "setting" IS 'Table with settings';

-- Add currently available models
INSERT INTO setting (id_setting, name, ratio, inserted, updated) VALUES (1, 'SIMPLE_ELO_RANDOM_DISTRACTORS', 1, NOW(), NOW());
INSERT INTO setting (id_setting, name, ratio, inserted, updated) VALUES (2, 'SIMPLE_ELO_TAXONOMY_DISTRACTORS', 1, NOW(), NOW());

-- Add and prefill user settings
ALTER TABLE "user" ADD "id_setting" bigint NULL;
ALTER TABLE "user" ADD FOREIGN KEY ("id_setting") REFERENCES "setting" ("id_setting") ON UPDATE RESTRICT ON DELETE RESTRICT;
COMMENT ON COLUMN "user"."id_setting" IS 'Setting assigned to this user';

UPDATE "user" SET "id_setting" = 1;

ALTER TABLE "user" ALTER COLUMN "id_setting" SET NOT NULL;