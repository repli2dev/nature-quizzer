-- Add id_representation to answer table

ALTER TABLE answer ADD COLUMN id_persistence_model BIGINT NULL REFERENCES model (id_model) ON DELETE CASCADE ON UPDATE CASCADE;
CREATE INDEX ON answer (id_persistence_model);

UPDATE answer SET id_persistence_model = id_model WHERE id_persistence_model IS NULL;

ALTER TABLE answer ALTER COLUMN id_persistence_model SET NOT NULL;