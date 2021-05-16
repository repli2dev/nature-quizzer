CREATE TABLE organism_taxonomy_fallback (
  name TEXT NOT NULL,
  fallback_name TEXT NOT NULL,
  inserted TIMESTAMP NOT NULL DEFAULT now()
);
CREATE UNIQUE INDEX ON organism_taxonomy_fallback(name);
