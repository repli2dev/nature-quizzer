-- Structure for user identification (with possible multiple external matching to one account)
CREATE TABLE "user" (
  id_user BIGSERIAL PRIMARY KEY,
  name text NULL,
  email text NULL UNIQUE,
  password text NULL,
  anonymous BOOL NOT NULL DEFAULT FALSE,
  inserted timestamp NOT NULL DEFAULT now()
);
COMMENT ON TABLE "user" IS 'User accounts';


CREATE TABLE user_external (
  id_user bigint NOT NULL REFERENCES "user" (id_user) ON UPDATE CASCADE ON DELETE CASCADE,
  token text NOT NULL UNIQUE,
  inserted timestamp NOT NULL DEFAULT now()
);
CREATE INDEX ON user_external (id_user);
COMMENT ON TABLE user_external IS 'Pairing of user external accounts (Facebook, Google, ...) with internal user accounts.';

-- Structure for languages
CREATE TABLE language (
  id_language BIGSERIAL PRIMARY KEY,
  name text NOT NULL,
  local_name text NOT NULL,
  code character(3) NOT NULL UNIQUE ,
  is_default boolean NOT NULL
);
COMMENT ON TABLE language IS 'List of available languages';
COMMENT ON COLUMN language.name IS 'Language name (in English)';
COMMENT ON COLUMN language.local_name IS 'Language name in that language';
COMMENT ON COLUMN language.code IS 'Code according to ISO 639-1 (or ISO 639-2 when 1 not set)';
COMMENT ON COLUMN language.is_default IS 'True means usage as a default language';

INSERT INTO language (name, local_name, code, is_default) VALUES ('Czech', 'Čeština', 'cs', TRUE);
INSERT INTO language (name, local_name, code, is_default) VALUES ('English', 'English', 'en', FALSE);

-- Structure for groups of concepts
CREATE TABLE "group" (
  "id_group" BIGSERIAL NOT NULL PRIMARY KEY,
  code_name text UNIQUE
);
COMMENT ON TABLE "group" IS 'Table with groups of concepts';

CREATE TABLE "group_info" (
  "id_group" bigint NOT NULL REFERENCES "group" ("id_group") ON UPDATE CASCADE ON DELETE CASCADE,
  "id_language" bigint NOT NULL REFERENCES language ("id_language") ON UPDATE CASCADE ON DELETE CASCADE,
  "name" text NOT NULL,
  "inserted" timestamp NOT NULL DEFAULT now(),
  "updated" timestamp NOT NULL DEFAULT now()
);
COMMENT ON TABLE "group_info" IS 'Language dependent information about groups.';

-- Structure for concepts
CREATE TABLE concept (
  id_concept BIGSERIAL PRIMARY KEY,
  code_name text UNIQUE,
  id_group BIGINT REFERENCES "group" ("id_group") ON UPDATE CASCADE ON DELETE SET NULL
);
COMMENT ON TABLE concept IS 'Quizzable concepts (e.g. Czech forest, Animals, Safari...)';

CREATE TABLE concept_info (
  id_concept bigint NOT NULL REFERENCES concept (id_concept) ON UPDATE CASCADE ON DELETE CASCADE,
  id_language bigint NOT NULL REFERENCES language (id_language) ON UPDATE CASCADE ON DELETE CASCADE,
  name text NOT NULL,
  description text NOT NULL,
  inserted timestamp NOT NULL DEFAULT now(),
  updated timestamp NOT NULL DEFAULT now(),
  PRIMARY KEY (id_concept, id_language)
);
CREATE INDEX ON concept_info (id_language);
COMMENT ON TABLE concept_info IS 'Localized information about concepts';

-- Structure for organisms

CREATE TABLE organism (
  id_organism BIGSERIAL NOT NULL PRIMARY KEY,
  latin_name text NOT NULL,
  inserted timestamp NOT NULL DEFAULT now(),
  updated timestamp NOT NULL DEFAULT now()
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
  source text NOT NULL,
  inserted timestamp NOT NULL DEFAULT now(),
  updated timestamp NOT NULL DEFAULT now()
);
CREATE INDEX ON organism_representation (id_organism);
COMMENT ON TABLE organism_representation IS 'Organism representations (e.g. their images,...)';
COMMENT ON COLUMN organism_representation.source IS 'Source from where this represenation comes from.';

CREATE TABLE organism_concept (
  id_organism bigint NOT NULL REFERENCES organism (id_organism) ON DELETE CASCADE ON UPDATE CASCADE ,
  id_concept bigint NOT NULL REFERENCES concept (id_concept) ON DELETE CASCADE ON UPDATE CASCADE,
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
CREATE INDEX idx_round_id_user ON round (id_user);


-- Structure for user answers
CREATE TABLE answer (
  id_answer BIGSERIAL PRIMARY KEY,
  id_round BIGINT NOT NULL REFERENCES round (id_round) ON UPDATE CASCADE ON DELETE CASCADE,
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
CREATE INDEX idx_answer_id_organism ON answer (id_organism);

COMMENT ON TABLE answer IS 'Stores all answers from persons (note: contains answer on other options as well).';
COMMENT ON COLUMN answer.correct IS 'Says whether the answer was considered correct.';
COMMENT ON COLUMN answer.main IS 'Says whether this was the organism used in question.';
COMMENT ON COLUMN answer.question_seq_num IS 'Serial number of question in the round.';
COMMENT ON COLUMN answer.option_seq_num IS 'Serial number of option in the question.';


-- Tables for models
CREATE TABLE organism_difficulty (
  id_organism BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  value DOUBLE PRECISION NOT NULL,
  UNIQUE (id_organism)
);
COMMENT ON TABLE organism_difficulty IS 'Table containing general difficulties for each organism.';

CREATE TABLE prior_knowledge (
  id_user BIGINT NOT NULL REFERENCES "user" (id_user) ON UPDATE CASCADE ON DELETE CASCADE,
  value DOUBLE PRECISION NOT NULL,
  UNIQUE (id_user)
);
COMMENT ON TABLE prior_knowledge IS 'Estimated skill of the user U of organism O without previously facing it.';

CREATE TABLE current_knowledge (
  id_user BIGINT NOT NULL REFERENCES "user" (id_user) ON UPDATE CASCADE ON DELETE CASCADE,
  id_organism BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  value DOUBLE PRECISION NOT NULL,
  UNIQUE (id_user, id_organism)
);
CREATE INDEX idx_current_knowledge_id_organism ON current_knowledge (id_organism);
COMMENT ON TABLE prior_knowledge IS 'Skill of user U of organism O after already facing it.';