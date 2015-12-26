CREATE OR REPLACE FUNCTION organism_tree(latin_name TEXT) RETURNS TABLE(tsn INT, parent_tsn INT, level INT) AS
  $BODY$
  BEGIN
    RETURN QUERY
    WITH RECURSIVE rec(tsn, parent_tsn, level) AS (
      SELECT tuu.tsn, tuu.parent_tsn, 0 FROM itis.taxonomic_units as tuu WHERE tuu.tsn = organism_itis_tsn(latin_name)
      UNION ALL
      SELECT tu.tsn, tu.parent_tsn, (r.level + 1) FROM rec AS r JOIN itis.taxonomic_units AS tu ON tu.tsn = r.parent_tsn AND r.parent_tsn != 0
    )
    SELECT * FROM rec;
  END;
  $BODY$
LANGUAGE plpgsql STABLE;