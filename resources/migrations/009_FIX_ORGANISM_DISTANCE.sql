-- Fix problem with cross kingdom organism distance computation and make organism_itis_name accept also accepted name_usage

CREATE OR REPLACE FUNCTION compute_organism_distance(o1 TEXT, o2 TEXT) RETURNS INT AS
  $BODY$
  DECLARE
    o1_path BIGINT[];
    o2_path BIGINT[];
    shared BIGINT[];
    differing BIGINT[];
  BEGIN
    -- Find path for o1
    WITH RECURSIVE rec(tsn, parent_tsn, level) AS (
      SELECT tsn, parent_tsn, 0 FROM itis.taxonomic_units WHERE tsn = organism_itis_tsn(o1)
      UNION ALL
      SELECT tu.tsn, tu.parent_tsn, (r.level + 1) FROM rec AS r JOIN itis.taxonomic_units AS tu ON tu.tsn = r.parent_tsn AND r.parent_tsn != 0
    )
    SELECT ARRAY_AGG(rec.tsn) INTO o1_path FROM rec;

    -- Find path for o2
    WITH RECURSIVE rec(tsn, parent_tsn, level) AS (
      SELECT tsn, parent_tsn, 0 FROM itis.taxonomic_units WHERE tsn = organism_itis_tsn(o2)
      UNION ALL
      SELECT tu.tsn, tu.parent_tsn, (r.level + 1) FROM rec AS r JOIN itis.taxonomic_units AS tu ON tu.tsn = r.parent_tsn AND r.parent_tsn != 0
    )
    SELECT ARRAY_AGG(rec.tsn) INTO o2_path FROM rec;

    -- Check if there is overlap of paths (if not, then the organisms are in different kingdoms)
    shared := array_intersection(o1_path, o2_path);
    IF array_length(o1_path, 1) <= 1 OR array_length(o2_path, 1) <= 1 OR array_length(shared, 1) IS NULL THEN
      RETURN NULL;
    END IF;

    -- Count number of differing nodes, their sum is the desired distance.
    differing = array_minus((o1_path || o2_path), shared); -- Array unique ommited as its expected due to operations
    RETURN array_length(differing, 1);
  END;
  $BODY$
LANGUAGE plpgsql STABLE;

CREATE OR REPLACE FUNCTION organism_itis_tsn(latin_name TEXT) RETURNS INT AS
  $BODY$
  DECLARE
    tu itis.taxonomic_units%ROWTYPE;
    tsn INT;
  BEGIN
    SELECT * INTO tu FROM itis.taxonomic_units WHERE complete_name = capitalize_first_only(latin_name) LIMIT 1;
    tsn := tu.tsn;
    IF tu.name_usage != 'valid' AND tu.name_usage != 'accepted' THEN
      SELECT tsn_accepted INTO tsn FROM itis.synonym_links AS sl WHERE sl.tsn = tu.tsn;
    END IF;
    RETURN tsn;
  END;
  $BODY$
LANGUAGE plpgsql STABLE /* assumption that mapping doesn't change during one transaction */;