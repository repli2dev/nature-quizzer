------------------------------------------------------------------------------------------------------------------------
-- Create table for available models
------------------------------------------------------------------------------------------------------------------------
CREATE TABLE model (
  id_model BIGSERIAL NOT NULL PRIMARY KEY,
  name CHARACTER VARYING(255) NOT NULL,
  ratio BIGINT NOT NULL,
  inserted TIMESTAMPTZ NOT NULL,
  updated TIMESTAMPTZ NOT NULL
);

ALTER TABLE model ADD CONSTRAINT model_name UNIQUE (name);
COMMENT ON COLUMN model.name IS 'Identifier of model from source code';
COMMENT ON COLUMN model.ratio IS 'Probability of assigning this setting to new user';
COMMENT ON TABLE model IS 'Table with settings';

-- Add currently available models
INSERT INTO model (id_model, name, ratio, inserted, updated) VALUES (1, 'ELO_RANDOM_DISTRACTORS', 1, NOW(), NOW());
INSERT INTO model (id_model, name, ratio, inserted, updated) VALUES (2, 'ELO_TAXONOMY_DISTRACTORS', 1, NOW(), NOW());

------------------------------------------------------------------------------------------------------------------------
-- Structure for languages
------------------------------------------------------------------------------------------------------------------------
CREATE TABLE language (
  id_language BIGSERIAL PRIMARY KEY,
  name text NOT NULL,
  local_name text NOT NULL,
  code CHARACTER VARYING(3) NOT NULL UNIQUE ,
  is_default boolean NOT NULL
);
COMMENT ON TABLE language IS 'List of available languages';
COMMENT ON COLUMN language.name IS 'Language name (in English)';
COMMENT ON COLUMN language.local_name IS 'Language name in that language';
COMMENT ON COLUMN language.code IS 'Code according to ISO 639-1 (or ISO 639-2 when 1 not set)';
COMMENT ON COLUMN language.is_default IS 'True means usage as a default language';

-- Add currently supported languages
INSERT INTO language (name, local_name, code, is_default) VALUES ('Czech', 'Čeština', 'cs', TRUE);
INSERT INTO language (name, local_name, code, is_default) VALUES ('English', 'English', 'en', FALSE);

------------------------------------------------------------------------------------------------------------------------
-- Structure for user identification (with possible multiple external matching to one account)
------------------------------------------------------------------------------------------------------------------------
CREATE TABLE "user" (
  id_user BIGSERIAL PRIMARY KEY,
  id_model BIGINT NOT NULL REFERENCES model (id_model) ON UPDATE RESTRICT ON DELETE RESTRICT,
  name TEXT NULL,
  email TEXT NULL UNIQUE,
  password TEXT NULL,
  anonymous BOOL NOT NULL DEFAULT FALSE,
  inserted TIMESTAMP NOT NULL DEFAULT NOW()
);
CREATE INDEX ON "user" (id_model);
COMMENT ON TABLE "user" IS 'User accounts';
COMMENT ON COLUMN "user".id_model IS 'Model assigned to this user';

CREATE TABLE user_external (
  id_user BIGINT NOT NULL REFERENCES "user" (id_user) ON UPDATE CASCADE ON DELETE CASCADE,
  token TEXT NOT NULL UNIQUE,
  inserted TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX ON user_external (id_user);
COMMENT ON TABLE user_external IS 'Pairing of user external accounts (Facebook, Google, ...) with internal user accounts.';


------------------------------------------------------------------------------------------------------------------------
-- Structure for groups of concepts
------------------------------------------------------------------------------------------------------------------------
CREATE TABLE "group" (
  id_group BIGSERIAL NOT NULL PRIMARY KEY,
  code_name TEXT UNIQUE
);
COMMENT ON TABLE "group" IS 'Table with groups of concepts';

CREATE TABLE group_info (
  id_group BIGINT NOT NULL REFERENCES "group" (id_group) ON UPDATE CASCADE ON DELETE CASCADE,
  id_language BIGINT NOT NULL REFERENCES language (id_language) ON UPDATE CASCADE ON DELETE CASCADE,
  name TEXT NOT NULL,
  inserted TIMESTAMP NOT NULL DEFAULT now(),
  updated TIMESTAMP NOT NULL DEFAULT now(),
  PRIMARY KEY (id_group, id_language)
);
COMMENT ON TABLE group_info IS 'Language dependent information about groups.';

-- Structure for concepts
CREATE TABLE concept (
  id_concept BIGSERIAL PRIMARY KEY,
  id_group BIGINT REFERENCES "group" (id_group) ON UPDATE CASCADE ON DELETE SET NULL,
  code_name TEXT UNIQUE,
  quick BOOLEAN NOT NULL DEFAULT FALSE
);
CREATE INDEX ON concept (id_group);
COMMENT ON TABLE concept IS 'Quizzable concepts (e.g. Czech forest, Animals, Safari...)';

CREATE TABLE concept_info (
  id_concept BIGINT NOT NULL REFERENCES concept (id_concept) ON UPDATE CASCADE ON DELETE CASCADE,
  id_language BIGINT NOT NULL REFERENCES language (id_language) ON UPDATE CASCADE ON DELETE CASCADE,
  name TEXT NOT NULL,
  description TEXT NOT NULL,
  inserted TIMESTAMP NOT NULL DEFAULT now(),
  updated TIMESTAMP NOT NULL DEFAULT now(),
  PRIMARY KEY (id_concept, id_language)
);
CREATE INDEX ON concept_info (id_language);
COMMENT ON TABLE concept_info IS 'Localized information about concepts';

------------------------------------------------------------------------------------------------------------------------
-- Structure for organisms
------------------------------------------------------------------------------------------------------------------------
CREATE TABLE organism (
  id_organism BIGSERIAL NOT NULL PRIMARY KEY,
  latin_name TEXT NOT NULL,
  inserted TIMESTAMP NOT NULL DEFAULT now(),
  updated TIMESTAMP NOT NULL DEFAULT now()
);
COMMENT ON TABLE organism IS 'All available organisms (e.g. vulpes vulpes, felis silvestris...)';

CREATE TABLE organism_name
(
  id_organism bigint NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  id_language bigint NOT NULL REFERENCES language (id_language) ON UPDATE CASCADE ON DELETE CASCADE,
  name text NOT NULL,
  inserted timestamp NOT NULL DEFAULT now(),
  updated timestamp NOT NULL DEFAULT now(),
  PRIMARY KEY (id_organism, id_language)
);
CREATE INDEX ON organism_name (id_language);
COMMENT ON TABLE organism_name IS 'Localized organism names';


CREATE TABLE organism_representation
(
  id_representation BIGSERIAL NOT NULL PRIMARY KEY,
  id_organism bigint NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  hash CHARACTER VARYING(128) NOT NULL UNIQUE,
  source TEXT NULL,
  url TEXT NULL,
  license TEXT NULL,
  rights_holder TEXT NULL,
  inserted TIMESTAMP NOT NULL DEFAULT now(),
  updated TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX ON organism_representation (id_organism);
COMMENT ON TABLE organism_representation IS 'Organism representations (e.g. their images,...)';
COMMENT ON COLUMN organism_representation.hash IS 'Hash of image for duplicity prevention';
COMMENT ON COLUMN organism_representation.source IS 'Source from where this represenation comes from.';
COMMENT ON COLUMN organism_representation.license IS 'License of the obtained representation.';
COMMENT ON COLUMN organism_representation.rights_holder IS 'Describes who is rights holders of this organism representation.';

CREATE TABLE organism_concept (
  id_organism BIGINT NOT NULL REFERENCES organism (id_organism) ON DELETE CASCADE ON UPDATE CASCADE ,
  id_concept BIGINT NOT NULL REFERENCES concept (id_concept) ON DELETE CASCADE ON UPDATE CASCADE,
  UNIQUE (id_concept, id_organism)
);
CREATE INDEX ON organism_concept (id_organism);
COMMENT ON TABLE organism_concept IS 'Pairing organism to concepts.';

-- Structure for rounds
CREATE TABLE round (
  id_round BIGSERIAL PRIMARY KEY,
  id_user bigint NOT NULL REFERENCES "user" (id_user) ON UPDATE CASCADE ON DELETE CASCADE,
  inserted timestamp NOT NULL DEFAULT now(),
  client json NOT NULL
);
CREATE INDEX ON round (id_user);

------------------------------------------------------------------------------------------------------------------------
-- Structure for user answers
------------------------------------------------------------------------------------------------------------------------
CREATE TABLE answer (
  id_answer BIGSERIAL PRIMARY KEY,
  id_round BIGINT NOT NULL REFERENCES round (id_round) ON UPDATE CASCADE ON DELETE CASCADE,
  id_model BIGINT NOT NULL REFERENCES model (id_model) ON UPDATE RESTRICT  ON DELETE RESTRICT,
  question_seq_num INT NOT NULL,
  option_seq_num INT NOT NULL,
  id_organism BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  correct BOOL NOT NULL,
  "main" BOOL NOT NULL,
  inserted timestamp NOT NULL DEFAULT now(),
  extra json NOT NULL,
  question_type BIGINT NOT NULL,
  UNIQUE (id_round, question_seq_num, option_seq_num)
);
CREATE INDEX ON answer (id_organism);
CREATE INDEX ON answer (id_model);

COMMENT ON TABLE answer IS 'Stores all answers from persons (note: contains answer on other options as well).';
COMMENT ON COLUMN answer.id_model IS 'Says model was used when this answer was collected and processed.';
COMMENT ON COLUMN answer.correct IS 'Says whether the answer was considered correct.';
COMMENT ON COLUMN answer.main IS 'Says whether this was the organism used in question.';
COMMENT ON COLUMN answer.question_seq_num IS 'Serial number of question in the round.';
COMMENT ON COLUMN answer.option_seq_num IS 'Serial number of option in the question.';

------------------------------------------------------------------------------------------------------------------------
-- Tables for models data
------------------------------------------------------------------------------------------------------------------------

CREATE TABLE organism_difficulty (
  id_organism BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  id_model BIGINT NOT NULL REFERENCES model (id_model) ON UPDATE RESTRICT ON DELETE RESTRICT,
  value DOUBLE PRECISION NOT NULL,
  UNIQUE (id_organism, id_model)
);
COMMENT ON TABLE organism_difficulty IS 'Table containing general difficulties for each organism.';

CREATE TABLE prior_knowledge (
  id_user BIGINT NOT NULL REFERENCES "user" (id_user) ON UPDATE CASCADE ON DELETE CASCADE,
  id_model BIGINT NOT NULL REFERENCES model (id_model) ON UPDATE RESTRICT ON DELETE RESTRICT,
  value DOUBLE PRECISION NOT NULL,
  UNIQUE (id_user, id_model)
);
COMMENT ON TABLE prior_knowledge IS 'Estimated skill of the user U of organism O without previously facing it.';

CREATE TABLE current_knowledge (
  id_user BIGINT NOT NULL REFERENCES "user" (id_user) ON UPDATE CASCADE ON DELETE CASCADE,
  id_model BIGINT NOT NULL REFERENCES model (id_model) ON UPDATE RESTRICT ON DELETE RESTRICT,
  id_organism BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  value DOUBLE PRECISION NOT NULL,
  UNIQUE (id_user, id_organism, id_model)
);
CREATE INDEX ON current_knowledge (id_organism);
COMMENT ON TABLE current_knowledge IS 'Skill of user U of organism O after already facing it.';

------------------------------------------------------------------------------------------------------------------------
-- Tables for distractors selection
------------------------------------------------------------------------------------------------------------------------

CREATE TABLE organism_distance (
  id_organism_from BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  id_organism_to BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  distance INT NOT NULL CHECK (distance > 0),
  PRIMARY KEY (id_organism_from, id_organism_to)
);

COMMENT ON TABLE organism_distance IS 'Captures distance between two organisms';