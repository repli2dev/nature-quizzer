-- Add structure for tracking packages content

CREATE TABLE package_group (
  name TEXT NOT NULL,
  id_group BIGINT NOT NULL REFERENCES "group" (id_group) ON UPDATE CASCADE ON DELETE CASCADE,
  inserted TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX ON package_group(id_group);
CREATE UNIQUE INDEX ON package_group(name, id_group);


CREATE TABLE package_concept (
  name TEXT NOT NULL,
  id_concept BIGINT NOT NULL REFERENCES "concept" (id_concept) ON UPDATE CASCADE ON DELETE CASCADE,
  inserted TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX ON package_concept(id_concept);
CREATE UNIQUE INDEX ON package_concept(name, id_concept);


CREATE TABLE package_organism (
  name TEXT NOT NULL,
  id_organism BIGINT NOT NULL REFERENCES "organism" (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  inserted TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX ON package_organism(id_organism);
CREATE UNIQUE INDEX ON package_organism(name, id_organism);

CREATE TABLE package_organism_representation (
  name TEXT NOT NULL,
  id_representation BIGINT NOT NULL REFERENCES "organism_representation" (id_representation) ON UPDATE CASCADE ON DELETE CASCADE,
  inserted TIMESTAMP NOT NULL DEFAULT now()
);
CREATE INDEX ON package_organism_representation(id_representation);
CREATE UNIQUE INDEX ON package_organism_representation(name, id_representation);