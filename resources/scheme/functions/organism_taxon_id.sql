CREATE OR REPLACE FUNCTION organism_taxon_id(latin_name TEXT) RETURNS INT AS
  $BODY$
  DECLARE
    taxon col.taxon_exported%ROWTYPE;
    output INT;
  BEGIN
    -- Prefer accepted names as there are synonyms refering to different organism species
    SELECT * INTO taxon FROM col.taxon_exported WHERE "completeName" = capitalize_first_only(latin_name) ORDER BY "taxonomicStatus" = 'accepted name' DESC LIMIT 1;
    IF taxon."taxonomicStatus" != 'accepted name' THEN
      output := taxon."acceptedNameUsageID";
    END IF;
    output := taxon."taxonID";

    -- Fallback when not found
    IF output IS NULL THEN
        SELECT fallback_name INTO latin_name FROM organism_taxonomy_fallback WHERE name = latin_name;
        SELECT * INTO taxon FROM col.taxon_exported WHERE "completeName" = capitalize_first_only(latin_name) ORDER BY "taxonomicStatus" = 'accepted name' DESC LIMIT 1;
        IF taxon."taxonomicStatus" != 'accepted name' THEN
            output := taxon."acceptedNameUsageID";
        END IF;
        output := taxon."taxonID";
    END IF;

    RETURN output;
  END;
  $BODY$
LANGUAGE plpgsql STABLE /* assumption that mapping doesn't change during one transaction */;
