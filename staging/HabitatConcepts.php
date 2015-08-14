<?php
/**
 * The purpose of this file is to define basic habitat concepts as there is too many habitats in the EOL database
 * (respective EOL Environments ontology).
 *
 * This files defines desired concepts (aka habitat groups) and its mapping from EOL habitat keywords.
 * There is ontology behind the habitats, however for the lack of clarity of structure and level of detail we are interested in
 * this mapping is hardcoded (and therefore sometimes ignore habitats we are not interested in, such as 'cosmetic products' or
 * very specific ones as there is assumption that these will have also some general habitat present).
 *
 * Desired habitat concepts are:
 *  1. Forest: all types of forests, woodlands, shrubs
 *  2. Water: all related to waters (saline, fresh) and its immediate surroundings as coasts, banks, swamps etc.
 *  3. Cultural: all related cultural landscape defined and managed by human, especially in city, farm, fields, dump waste etc.
 *  4. Plain: all related to large open planes (with not so many trees) such as moors, prairies, grassland etc.
 *  5. Desert: all related to hostile environments such as deserts or volcano surroundings etc.
 *  6. Mountain: all related to environments with harder access such as mountains, caves, glaciers, abyss etc.
 *
 * For obvious reason some organisms can be part of more then one desired habitat.
 *
 * Inspired by: http://www.sci.muni.cz/botany/chytry/Katalog.pdf
 * Data source: http://eol.org/
 */

class HabitatConcepts
{
	const CONCEPTS = ['forest', 'water', 'mountain', 'desert', 'plain', 'cultural'];

	const MAPPING = [
		'forest' => ['forest', 'woodland', 'soil', 'mud', 'wood', 'arboreal habitat', 'oak woodland', 'juniper woodland', 'open woodland', 'forest soil', 'planted forest', 'wood fall', 'upland forest', 'dry woodland'],
		'water' => ['sea', 'marine biome', 'fresh water', 'freshwater habitat', 'river', 'stream', 'ocean', 'Large river biome', 'lake', 'reef', 'marine habitat', 'bay', 'aquatic habitat', 'pond', 'estuary', 'coral reef', 'pelagic zone', 'aquarium', 'river bank', 'lagoon', 'stream bank', 'intertidal zone', 'water body', 'canal', 'reservoir', 'saline water', 'coastal water body', 'strait', 'brackish water', 'watershed', 'freshwater lake', 'river valley', 'waterfall', 'headwater', 'sea floor', 'atoll', 'riffle', 'rapids', 'channel', 'ground water', 'delta', 'backwater', 'stream bed', 'surface water', 'sea water', 'inlet', 'saline lake', 'Small lake biome', 'drainage ditch', 'ocean floor', 'fresh water aquarium', 'marine terrace', 'irrigation ditch', 'kelp forest', 'fen', 'irrigation canal', 'crater lake', 'aquifer', 'crater lake', 'ice sheet', 'ocean water', 'cold seep', 'lake bed', 'fishpond', 'tidal creek', 'ocean trench', 'brackish estuary', 'sea ice', 'drainage canal', 'ocean current', 'ocean basin', 'cove', 'coastal water', 'brackish lake', 'dam', 'pond bed', 'river bottom'],
		'water_by' => ['coast', 'shore', 'mud', 'meander', 'moor', 'muddy water', 'coastal scrubland', 'freshwater wetland', 'sphagnum bog', 'coastal wetland', 'waterhole', 'sea beach', 'sea cliff', 'sea shore', 'sandy beach', 'peatland', 'lake shore', 'mangrove swamp', 'wadi', 'saline marsh', 'coastal plain', 'shoreline', 'freshwater marsh', 'beach', 'marsh', 'swamp', 'wetland'],
		'mountain' => ['slope', 'mountain', 'plateau', 'rock', 'mud', 'hill', 'mountain range', 'cliff', 'ridge', 'crevasse', 'cave', 'peak', 'scree', 'mount', 'massif', 'escarpment', 'karst', 'glacier', 'cave system', 'moraine', 'abyssal feature', 'bajada', 'karst cave', 'caves', 'cave entrance', 'hill range', 'snow field', 'cave wall', 'cave floor'],
		'desert' => ['sand', 'desert', 'rock', 'savanna', 'dune', 'sandstone', 'volcanic field', 'desert scrubland', 'volcano', 'dry soil', 'lava', 'sandy desert', 'lava field', 'volcanic soil', 'stony desert', 'permafrost', 'rocky desert', 'desert oasis', 'desert biome'],
		'plain' => ['scrubland', 'grassland', 'wetland', 'mud', 'meadow', 'marsh', 'plain', 'prairie', 'steppe', 'coastal plain', 'tundra', 'rangeland biome', 'peatland', 'desert scrubland', 'sphagnum bog', 'fen', 'alluvial plain', 'bajada', 'permafrost', 'coastal scrubland', 'moor', 'savanna biome', 'grassland biome', 'grassland soil', 'savanna'],
		'cultural' => ['mud', 'garden', 'pond', 'city', 'plantation', 'cultivated habitat', 'pasture', 'ditch', 'agricultural feature', 'farm', 'canal', 'anthropogenic habitat', 'waste', 'rice field', 'hedge', 'road', 'orchard', 'railway', 'tunnel', 'paddy field', 'cobble', 'quarry', 'sewage', 'levee', 'harbor', 'cultured habitat', 'populated place', 'anthropogenic geographic feature', 'drainage ditch', 'well', 'field', 'vineyard', 'irrigation ditch', 'irrigation canal', 'tea plantation', 'contaminated water', 'compost', 'banana plantation', 'mine', 'fishpond', 'drainage canal', 'hospital', 'coal mine', 'hydroelectric dam', 'waste water', 'textile', 'water well', 'contaminated soil', 'artificial lake', 'dam', 'industrial waste', 'warehouse', 'agricultural soil', 'organic waste', 'bridge', 'brewery', 'bakery', 'agricultural terrace', 'agricultural waste']
	];

	private static $temp;

	public static function simplify($original)
	{
		if (!self::$temp) {
			self::prepare();
		}

		$output = [];
		$original = (array) $original;
		foreach ($original as $keyword) {
			if (isset(self::$temp[$keyword])) {
				$output = array_merge($output, self::$temp[$keyword]);
			}
		}

		return array_values(array_unique($output));
	}

	private static function prepare()
	{
		$reversed = [];
		foreach (self::MAPPING as $target => $starts) {
			foreach ($starts as $item) {
				if (!isset($reversed[$item])) {
					$reversed[$item] = [];
				}
				$reversed[$item][] = $target;
			}
		}
		self::$temp = $reversed;
	}
}