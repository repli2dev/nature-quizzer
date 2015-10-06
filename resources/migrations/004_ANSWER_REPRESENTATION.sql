-- Add id_representation to answer table

ALTER TABLE answer ADD COLUMN id_representation BIGINT NULL REFERENCES organism_representation (id_representation) ON DELETE CASCADE ON UPDATE CASCADE;
CREATE INDEX ON answer (id_representation);