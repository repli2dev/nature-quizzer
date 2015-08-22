CREATE OR REPLACE FUNCTION organism_itis_tsn(latin_name TEXT) RETURNS INT AS
  $BODY$
  DECLARE
    tu itis.taxonomic_units%ROWTYPE;
    tsn INT;
  BEGIN
    SELECT * INTO tu FROM itis.taxonomic_units WHERE complete_name = capitalize_first_only(latin_name) LIMIT 1;
    tsn := tu.tsn;
    IF tu.name_usage != 'valid' THEN
      SELECT tsn_accepted INTO tsn FROM itis.synonym_links AS sl WHERE sl.tsn = tu.tsn;
    END IF;
    RETURN tsn;
  END;
  $BODY$
LANGUAGE plpgsql STABLE /* assumption that mapping doesn't change during one transaction */;