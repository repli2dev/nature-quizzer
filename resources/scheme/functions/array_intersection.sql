CREATE FUNCTION array_intersection(anyarray, anyarray) RETURNS anyarray AS
  $BODY$
  SELECT ARRAY(
    SELECT UNNEST($1)
    INTERSECT
    SELECT UNNEST($2)
  );
  $BODY$
LANGUAGE sql;