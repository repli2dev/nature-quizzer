CREATE OR REPLACE FUNCTION organism_taxon_id(latin_name TEXT) RETURNS INT AS
  $BODY$
  DECLARE
    taxon col.taxon_exported%ROWTYPE;
  BEGIN
    -- Prefer accepted names as there are synonyms refering to different organism species
    SELECT * INTO taxon FROM col.taxon_exported WHERE "completeName" = capitalize_first_only(latin_name) ORDER BY "taxonomicStatus" = 'accepted name' DESC LIMIT 1;
    IF taxon."taxonomicStatus" != 'accepted name' THEN
      return taxon."acceptedNameUsageID";
    END IF;
    RETURN taxon."taxonID";
  END;
  $BODY$
LANGUAGE plpgsql STABLE /* assumption that mapping doesn't change during one transaction */;