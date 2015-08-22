CREATE FUNCTION array_minus(anyarray, anyarray) RETURNS anyarray AS
  $BODY$
  SELECT ARRAY(
    SELECT UNNEST($1)
    EXCEPT
    SELECT UNNEST($2)
  );
  $BODY$
LANGUAGE sql;