<?php
namespace NatureQuizzer\Tools;

/**
 * Class for obtaining habitats of particular organism from TraitBank at http://eol.org
 * Due to not working API nor batch download this query webpage and parse it by regex.
 *
 * @see http://eol.org/data_search
 */
class EOLHabitats extends EOLAPI
{
	const API_URL = 'http://eol.org/data_search?utf8=%E2%9C%93&taxon_concept_id=__ID__&attribute=__ATTRIBUTE__&required_equivalent_attributes%5B%5D=1448&taxon_name=__NAME__&q=&min=&max=&unit=&sort=desc&commit=Search';


	private function prepareUrl($id, $name)
	{
		$temp = str_replace('__ATTRIBUTE__', urlencode('http://rs.tdwg.org/dwc/terms/habitat'), self::API_URL);
		$temp = str_replace('__NAME__', urlencode($name), $temp);
		return str_replace('__ID__', urlencode($id), $temp);
	}

	public function getData($id, $name = NULL)
	{
		$url = $this->prepareUrl($id, $name);
		$result = $this->fetch($url);

		$matches = [];
		preg_match_all('#<li>\n<a .*?>([\w\s]*?)</a>\n<span class=\'count\'>([\d]*?)</span>\n</li>#su', $result, $matches);
		$matches = $matches[1];
		return $matches;
	}
}