-- Add column for tracking what concept has user selected

ALTER TABLE round ADD COLUMN id_concept BIGINT NULL REFERENCES concept(id_concept);

COMMENT ON COLUMN round.id_concept IS 'Concept which user selected for practising. Null means mix mode. Beware that this value is valid only certain date.';