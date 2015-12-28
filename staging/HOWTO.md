How to prepare new topic (package)
==================================

The process of creating new topic involves extensive usage of system classes etc. For that reason the creation process
is done in /staging/ folder. There you prepare the lists, fetch the images and check the topics.

Finished topics goes into own repository: https://github.com/repli2dev/nature-quizzer-packages from which is used with
packaging manager (see README.md).

Each package is a folder with following structure:

 - package.json describing the content, see /utils/package.php for detailed structure,
 - files folder with all referenced images.

1. Collect organism desired in the new package (~ topic), such as Czech animals, ideally in czech with latin names.
   For this step, good list is needed. Wikipedia and BioLib or similar are particularly good.
   Tip: when necessary use regex parsing via NatureQuizzer\Utils\WebProcessor.

2. Canonize names using EOL, fetch EOL IDs and english names.
   (The system uses normalized names of lowercase names, see NatureQuizzer\Utils\Normalizator.)
   Tip: see script unification.php from previously done topics as it fetchs EOL IDs and canonizes names...

3. Fetch and process images and compose draft of package.
   Tip: see script prepare-package.php from previously done topics as it does exactly this.
        better fetch all available and vetted pictures then only a subset as it tends to be highly similar pictures.

4. Check representation to remove not usable images (skulls, dead bodies, too vague images,...)
   Tip: use /utils/image-overview.php to generate simple page with all representations included.
        use /utils/remove-representation.php to batch remove representations by their hashs.

5. Check the package for well-formness (referenced representations match, no others are present)
   Tip: use /utils/image-checker.php to check this.

6. Do test import to test the package and run /utils/update-organism-distances.php to prepare distractors relationships.

7. Check that each organism has valid latin name usable for taxonomy distractors.
   Tip: after using /utils/update-organism-distances.php run following query:
   
   SELECT id_organism, latin_name, COUNT(id_organism_to) FROM organism
   LEFT JOIN organism_distance ON id_organism=id_organism_from
   GROUP BY id_organism
   HAVING COUNT(id_organism_to) = 0
   ORDER BY id_organism


Maintaining topic (package)
==================================

Due to complicated process and voluntary work in all used services the packages are not perfect and they can contain
various of errors: from not pretty pictures for determining animals to badly determined organism...

To deal with it the feedback button and form was introduced and is used. To check complain there are two pages
in administration (Representation/organism checker) where the sequence from feedback mail can be inserted.

All deleted representations/organism should be deleted from system and package! Removing from package and later import
with garbage collection will ensure the same with as deleting from both. DO NOT forget to remove image itself from the
topic. (See: /utils/remove-presentations.php)

When there is too few representations for some images, new should be introduced. For manual approach there is script
/utils/fetch-representation.php which fetch and precess image from given URL and generate JSON stub to be inserted into
package.json. Information about source, rights and licences, however, have to be filled manually.

When adding new concepts into existing topics, you can inspire in extend-concepts.php script.


Others
==================================

For extracting some rank of organisms from taxonomy, there is database function extract_organism_rank.
For whole taxonomy path from root to one particular organism, there is database function organism_tree.

Commit only extracting and processing scripts to this repository (and manually prepared lists of animals).