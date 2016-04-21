SET schema 'web_nature_quizzer';

-- List of concepts
CREATE TEMPORARY VIEW concepts AS
  SELECT
    concept.*,
    cs_names.name AS cs_name,
    en_names.name AS en_name
  FROM "concept"
    JOIN "concept_info" AS cs_names ON concept.id_concept = cs_names.id_concept AND cs_names.id_language = 1
    JOIN "concept_info" AS en_names ON concept.id_concept = en_names.id_concept AND en_names.id_language = 2;

\copy (SELECT * FROM concepts) TO concepts.csv DELIMITER ',' CSV HEADER;


-- List of organisms
CREATE TEMPORARY VIEW organisms AS
  SELECT
    organism.id_organism,
    organism.latin_name,
    cs_names.name AS cs_name,
    en_names.name AS en_name
  FROM "organism"
    JOIN "organism_name" AS cs_names ON organism.id_organism = cs_names.id_organism AND cs_names.id_language = 1
    JOIN "organism_name" AS en_names ON organism.id_organism = en_names.id_organism AND en_names.id_language = 2;

\copy (SELECT * FROM organisms) TO organisms.csv DELIMITER ',' CSV HEADER;

-- Commonness flag of organisms
\copy (SELECT * FROM organism_commonness) TO organisms_commonness.csv DELIMITER ',' CSV HEADER;

-- Organisms mapping to concepts
\copy (SELECT * FROM organism_concept) TO organisms_concepts.csv DELIMITER ',' CSV HEADER;

-- Organisms distances
\copy (SELECT * FROM organism_distance) TO organisms_distances.csv DELIMITER ',' CSV HEADER;

-- Organisms representations
\copy (SELECT * FROM organism_representation) TO organisms_representations.csv DELIMITER ',' CSV HEADER;

-- Answers
CREATE TEMPORARY VIEW answers AS
  SELECT
    id_round,
    id_user,
    inserted,
    question_type,
    question_seq_num,
    bool_and(correct) AS overall_correct,
    json_agg(correct) AS answers,
    json_agg(id_organism) FILTER (WHERE main = TRUE) AS main_organism,
    json_agg(id_organism ORDER BY option_seq_num) AS organisms,
    json_agg(id_representation ORDER BY option_seq_num) AS representations
  FROM (
         SELECT
           id_round,
           round.id_user,
           answer.inserted,
           answer.question_seq_num,
           answer.option_seq_num,
           answer.id_organism,
           answer.id_representation,
           answer.correct,
           answer.main,
           answer.question_type
         FROM round
           JOIN answer USING (id_round)
         ORDER BY id_round ASC, question_seq_num ASC
       ) AS t
  GROUP BY id_round, id_user, inserted, question_seq_num, question_type;

\copy (SELECT * FROM answers) TO answers.csv DELIMITER ',' CSV HEADER;