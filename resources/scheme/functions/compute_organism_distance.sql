CREATE OR REPLACE FUNCTION compute_organism_distance(o1 TEXT, o2 TEXT) RETURNS INT AS
  $BODY$
  DECLARE
    o1_path BIGINT[];
    o2_path BIGINT[];
    shared BIGINT[];
    differing BIGINT[];
  BEGIN
    -- Find path for o1
    WITH RECURSIVE rec("taxonID", "parentNameUsageID", level) AS (
      SELECT "taxonID", "parentNameUsageID", 0 FROM col.taxon_exported WHERE "taxonID" = organism_taxon_id(o1)
      UNION ALL
      SELECT tu."taxonID", tu."parentNameUsageID", (r.level + 1) FROM rec AS r JOIN col.taxon_exported AS tu ON tu."taxonID" = r."parentNameUsageID"
    )
    SELECT ARRAY_AGG(rec."taxonID") INTO o1_path FROM rec;

    -- Find path for o2
    WITH RECURSIVE rec("taxonID", "parentNameUsageID", level) AS (
      SELECT "taxonID", "parentNameUsageID", 0 FROM col.taxon_exported WHERE "taxonID" = organism_taxon_id(o2)
      UNION ALL
      SELECT tu."taxonID", tu."parentNameUsageID", (r.level + 1) FROM rec AS r JOIN col.taxon_exported AS tu ON tu."taxonID" = r."parentNameUsageID"
    )
    SELECT ARRAY_AGG(rec."taxonID") INTO o2_path FROM rec;

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