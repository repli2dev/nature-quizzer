CREATE OR REPLACE FUNCTION organism_tree(latin_name TEXT) RETURNS TABLE(tsn INT, parent_tsn INT, level INT) AS
  $BODY$
  BEGIN
    RETURN QUERY
    WITH RECURSIVE rec("taxonID", "parentNameUsageID", level) AS (
      SELECT "taxonID", "parentNameUsageID", 0 FROM col.taxon_exported WHERE "taxonID" = organism_taxon_id(latin_name)
      UNION ALL
      SELECT tu."taxonID", tu."parentNameUsageID", (r.level + 1) FROM rec AS r JOIN col.taxon_exported AS tu ON tu."taxonID" = r."parentNameUsageID"
    )
    SELECT * FROM rec;
  END;
  $BODY$
LANGUAGE plpgsql STABLE;