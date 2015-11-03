-- Add support for general organism commonness

CREATE TABLE organism_commonness (
  id_organism BIGINT NOT NULL REFERENCES organism (id_organism) ON UPDATE CASCADE ON DELETE CASCADE,
  value INT NOT NULL
);

CREATE INDEX ON organism_commonness (id_organism);