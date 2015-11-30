Preparing new package
=====================

1. Collect organism desired in the new package (~ topic), such as Czech animals, ideally czech with latin names.

2. Canonize names using EOL.

3. Fetch EOL IDs and english names.

4. Fetch and process images and compose package.

5. Check representation to remove not usable images (skulls, dead bodies, too vague images,...)
   Tip: use /utils/image-overview.php to generate simple page with all representations included.
        use /utils/remove-representation.php to batch remove representations by their hashs.

6. Check the package for well-formness (referenced representations match, no others are present)
   Tip: use /utils/image-checker.php to check this.

7. Do test import to test the package.

8. Check that each organism has valid latin name usable for taxonomy distractors.
   Tip: use /utils/update-organism-distances.php and then run following query:
   
   SELECT id_organism, latin_name, COUNT(id_organism_to) FROM organism
   LEFT JOIN organism_distance ON id_organism=id_organism_from
   GROUP BY id_organism
   HAVING COUNT(id_organism_to) = 0
   ORDER BY id_organism