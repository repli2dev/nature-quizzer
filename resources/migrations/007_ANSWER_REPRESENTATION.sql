-- Do not cascade when representation is deleted.

ALTER TABLE answer DROP CONSTRAINT answer_id_representation_fkey, ADD FOREIGN KEY (id_representation) REFERENCES organism_representation (id_representation) ON DELETE SET NULL ON UPDATE CASCADE;