CREATE OR REPLACE FUNCTION extract_organism_rank(latin_name TEXT, kingdom_id2 INT, rank_id2 INT) RETURNS TEXT AS
  $BODY$
  DECLARE
    rank_name TEXT;
  BEGIN
    SELECT
      tu.unit_name1 INTO rank_name
    FROM organism_tree(latin_name) AS ot
      JOIN itis.taxonomic_units AS tu ON ot.tsn = tu.tsn
    WHERE tu.kingdom_id = kingdom_id2 AND tu.rank_id = rank_id2
    LIMIT 1;
    RETURN TRIM(BOTH FROM rank_name);
  END;
  $BODY$
LANGUAGE plpgsql STABLE;