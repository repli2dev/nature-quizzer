# Export SQL queries

The export contains
- list of concepts (with names) in `concepts.csv`,
- list of organisms (with names) in `organisms.csv`,
- commonness flag of organisms in `organisms_commonness.csv`,
- organisms mapping to concepts in `organisms_concepts.csv`,
- organisms distances in `organisms_distances.csv`,
- organisms representations in `organisms_representations.csv`,
- answers.

## Format notes


- Some columns are JSON type (arrays, objects).
- Columns `answers`, `organisms` or `representations` in `answers` data file are sorted from the first place to the fourth place in the question. The only exception is with question type 2 where there is only one ID of representation (there was only one representation of the main organism).
- Column `answer` contains evaluation of each answer (if the option should not be answered but it was, then it false). Not so obivously this leads to the state that right answer has always true. The correctly answered question is such that `overall_correct` is `true`.

## List of concepts

```
SELECT
  concept.*,
  cs_names.name AS cs_name,
  en_names.name AS en_name
FROM "concept"
JOIN "concept_info" AS cs_names ON concept.id_concept = cs_names.id_concept AND cs_names.id_language = 1
JOIN "concept_info" AS en_names ON concept.id_concept = en_names.id_concept AND en_names.id_language = 2
```

## List of organisms

```
SELECT
  organism.id_organism,
  organism.latin_name,
  cs_names.name AS cs_name,
  en_names.name AS en_name
FROM "organism"
JOIN "organism_name" AS cs_names ON organism.id_organism = cs_names.id_organism AND cs_names.id_language = 1
JOIN "organism_name" AS en_names ON organism.id_organism = en_names.id_organism AND en_names.id_language = 2
```

## Answers

```
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
GROUP BY id_round, id_user, inserted, question_seq_num, question_type
```