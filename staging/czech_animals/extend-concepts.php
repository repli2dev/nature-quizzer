<?php
/**
 * Extends given package's concepts using taxonomy ranks
 */
use Nette\Utils\Json;
use Tracy\Debugger;

ini_set('memory_limit','1024M');

include_once __DIR__ . "/../../app/bootstrap.php";

Debugger::enable(Debugger::DEVELOPMENT);

$ranks = [
	"neovison vison" => "Mammalia",
	"mustela putorius" => "Mammalia",
	"mustela eversmanii" => "Mammalia",
	"martes martes" => "Mammalia",
	"martes foina" => "Mammalia",
	"mustela nivalis" => "Mammalia",
	"mustela erminea" => "Mammalia",
	"meles meles" => "Mammalia",
	"lutra lutra" => "Mammalia",
	"ursus arctos" => "Mammalia",
	"procyon lotor" => "Mammalia",
	"nyctereutes procyonoides" => "Mammalia",
	"vulpes vulpes" => "Mammalia",
	"canis lupus" => "Mammalia",
	"lynx lynx" => "Mammalia",
	"felis silvestris" => "Mammalia",
	"sus scrofa" => "Mammalia",
	"cervus elaphus" => "Mammalia",
	"dama dama" => "Mammalia",
	"cervus nippon" => "Mammalia",
	"odocoileus virginianus" => "Mammalia",
	"capreolus capreolus" => "Mammalia",
	"alces alces" => "Mammalia",
	"rupicapra rupicapra" => "Mammalia",
	"ovis aries" => "Mammalia",
	"ammotragus lervia" => "Mammalia",
	"lepus europaeus" => "Mammalia",
	"oryctolagus cuniculus" => "Mammalia",
	"sciurus vulgaris" => "Mammalia",
	"spermophilus citellus" => "Mammalia",
	"castor fiber" => "Mammalia",
	"myocastor coypus" => "Mammalia",
	"cricetus cricetus" => "Mammalia",
	"clethrionomys glareolus" => "Mammalia",
	"arvicola amphibius" => "Mammalia",
	"ondatra zibethicus" => "Mammalia",
	"microtus arvalis" => "Mammalia",
	"microtus agrestis" => "Mammalia",
	"microtus subterraneus" => "Mammalia",
	"rattus rattus" => "Mammalia",
	"rattus norvegicus" => "Mammalia",
	"mus musculus" => "Mammalia",
	"micromys minutus" => "Mammalia",
	"apodemus agrarius" => "Mammalia",
	"sicista betulina" => "Mammalia",
	"apodemus flavicollis" => "Mammalia",
	"apodemus sylvaticus" => "Mammalia",
	"apodemus uralensis" => "Mammalia",
	"glis glis" => "Mammalia",
	"muscardinus avellanarius" => "Mammalia",
	"eliomys quercinus" => "Mammalia",
	"dryomys nitedula" => "Mammalia",
	"erinaceus concolor" => "Mammalia",
	"erinaceus europaeus" => "Mammalia",
	"neomys anomalus" => "Mammalia",
	"neomys fodiens" => "Mammalia",
	"nectogale elegans" => "Mammalia",
	"sorex araneus" => "Mammalia",
	"sorex minutus" => "Mammalia",
	"crocidura leucodon" => "Mammalia",
	"crocidura suaveolens" => "Mammalia",
	"talpa europaea" => "Mammalia",
	"homo sapiens" => "Mammalia",
	"barbastella barbastellus" => "Mammalia",
	"eptesicus nilssoni" => "Mammalia",
	"eptesicus serotinus" => "Mammalia",
	"hypsugo savii" => "Mammalia",
	"myotis bechsteini" => "Mammalia",
	"myotis dasycneme" => "Mammalia",
	"myotis daubentoni" => "Mammalia",
	"myotis emarginatus" => "Mammalia",
	"myotis myotis" => "Mammalia",
	"myotis mystacinus" => "Mammalia",
	"myotis nattereri" => "Mammalia",
	"nyctalus lasiopterus" => "Mammalia",
	"nyctalus leisleri" => "Mammalia",
	"nyctalus noctula" => "Mammalia",
	"pipistrellus nathusii" => "Mammalia",
	"pipistrellus pipistrellus" => "Mammalia",
	"pipistrellus pygmaeus" => "Mammalia",
	"plecotus auritus" => "Mammalia",
	"plecotus austriacus" => "Mammalia",
	"vespertilio murinus" => "Mammalia",
	"myotis blythii" => "Mammalia",
	"rhinolophus hipposideros" => "Mammalia",
	"rhinolophus ferrumequinum" => "Mammalia",
	"alle alle" => "Aves",
	"cepphus grylle" => "Aves",
	"uria aalge" => "Aves",
	"gallinago gallinago" => "Aves",
	"gallinago media" => "Aves",
	"oenanthe hispanica" => "Aves",
	"oenanthe deserti" => "Aves",
	"oenanthe oenanthe" => "Aves",
	"branta leucopsis" => "Aves",
	"branta ruficollis" => "Aves",
	"branta bernicla" => "Aves",
	"saxicola torquatus" => "Aves",
	"saxicola rubetra" => "Aves",
	"sitta europaea" => "Aves",
	"bombycilla garrulus" => "Aves",
	"limosa limosa" => "Aves",
	"limosa lapponica" => "Aves",
	"hirundo rupestris" => "Aves",
	"riparia riparia" => "Aves",
	"phylloscopus ibericus" => "Aves",
	"phylloscopus sibilatrix" => "Aves",
	"phylloscopus collybita" => "Aves",
	"phylloscopus inornatus" => "Aves",
	"phylloscopus schwarzi" => "Aves",
	"phylloscopus trochilus" => "Aves",
	"phylloscopus trochiloides" => "Aves",
	"phylloscopus proregulus" => "Aves",
	"botaurus stellaris" => "Aves",
	"ixobrychus minutus" => "Aves",
	"hydrobates pelagicus" => "Aves",
	"fulmarus glacialis" => "Aves",
	"cettia cetti" => "Aves",
	"locustella fluviatilis" => "Aves",
	"locustella luscinioides" => "Aves",
	"locustella naevia" => "Aves",
	"ciconia ciconia" => "Aves",
	"ciconia nigra" => "Aves",
	"carduelis hornemanni" => "Aves",
	"carduelis flammea" => "Aves",
	"vanellus vanellus" => "Aves",
	"vanellus spinosus" => "Aves",
	"erithacus rubecula" => "Aves",
	"anas querquedula" => "Aves",
	"anas discors" => "Aves",
	"anas crecca" => "Aves",
	"carduelis spinus" => "Aves",
	"dryocopus martius" => "Aves",
	"picoides tridactylus" => "Aves",
	"coccothraustes coccothraustes" => "Aves",
	"tetrax tetrax" => "Aves",
	"otis tarda" => "Aves",
	"turdus viscivorus" => "Aves",
	"turdus iliacus" => "Aves",
	"turdus pilaris" => "Aves",
	"turdus obscurus" => "Aves",
	"turdus naumanni" => "Aves",
	"turdus philomelos" => "Aves",
	"falco columbarius" => "Aves",
	"upupa epops" => "Aves",
	"burhinus oedicnemus" => "Aves",
	"corvus frugilegus" => "Aves",
	"bucephala clangula" => "Aves",
	"clangula hyemalis" => "Aves",
	"columba oenas" => "Aves",
	"columba palumbus" => "Aves",
	"streptopelia turtur" => "Aves",
	"streptopelia decaocto" => "Aves",
	"anser albifrons" => "Aves",
	"anser erythropus" => "Aves",
	"anser fabalis" => "Aves",
	"anser anser" => "Aves",
	"tadorna tadorna" => "Aves",
	"anas americana" => "Aves",
	"anas penelope" => "Aves",
	"pyrrhula pyrrhula" => "Aves",
	"bucanetes githagineus" => "Aves",
	"pinicola enucleator" => "Aves",
	"carpodacus erythrinus" => "Aves",
	"stercorarius longicaudus" => "Aves",
	"stercorarius pomarinus" => "Aves",
	"stercorarius parasiticus" => "Aves",
	"stercorarius skua" => "Aves",
	"galerida cristata" => "Aves",
	"porzana porzana" => "Aves",
	"porzana parva" => "Aves",
	"porzana pusilla" => "Aves",
	"crex crex" => "Aves",
	"rallus aquaticus" => "Aves",
	"plegadis falcinellus" => "Aves",
	"grus grus" => "Aves",
	"tetrastes bonasia" => "Aves",
	"limicola falcinellus" => "Aves",
	"philomachus pugnax" => "Aves",
	"calidris bairdii" => "Aves",
	"calidris ferruginea" => "Aves",
	"calidris minuta" => "Aves",
	"calidris maritima" => "Aves",
	"calidris alpina" => "Aves",
	"calidris alba" => "Aves",
	"tryngites subruficollis" => "Aves",
	"calidris canutus" => "Aves",
	"calidris melanotos" => "Aves",
	"calidris temminckii" => "Aves",
	"calidris fuscicollis" => "Aves",
	"accipiter gentilis" => "Aves",
	"delichon urbicum" => "Aves",
	"anas platyrhynchos" => "Aves",
	"somateria spectabilis" => "Aves",
	"somateria mollissima" => "Aves",
	"polysticta stelleri" => "Aves",
	"asio flammeus" => "Aves",
	"asio otus" => "Aves",
	"arenaria interpres" => "Aves",
	"buteo rufinus" => "Aves",
	"buteo buteo" => "Aves",
	"buteo lagopus" => "Aves",
	"pyrrhocorax graculus" => "Aves",
	"corvus monedula" => "Aves",
	"vanellus leucurus" => "Aves",
	"vanellus gregarius" => "Aves",
	"numenius phaeopus" => "Aves",
	"numenius arquata" => "Aves",
	"platalea leucorodia" => "Aves",
	"motacilla alba" => "Aves",
	"motacilla citreola" => "Aves",
	"motacilla cinerea" => "Aves",
	"motacilla flava" => "Aves",
	"carduelis cannabina" => "Aves",
	"carduelis flavirostris" => "Aves",
	"anas strepera" => "Aves",
	"phalacrocorax aristotelis" => "Aves",
	"phalacrocorax carbo" => "Aves",
	"perdix perdix" => "Aves",
	"turdus merula" => "Aves",
	"turdus torquatus" => "Aves",
	"accipiter brevipes" => "Aves",
	"accipiter nisus" => "Aves",
	"regulus regulus" => "Aves",
	"regulus ignicapilla" => "Aves",
	"corvus corax" => "Aves",
	"jynx torquilla" => "Aves",
	"coturnix coturnix" => "Aves",
	"loxia leucoptera" => "Aves",
	"loxia curvirostra" => "Aves",
	"loxia pytyopsittacus" => "Aves",
	"cuculus canorus" => "Aves",
	"pluvialis squatarola" => "Aves",
	"pluvialis dominica" => "Aves",
	"charadrius morinellus" => "Aves",
	"charadrius alexandrinus" => "Aves",
	"charadrius hiaticula" => "Aves",
	"charadrius dubius" => "Aves",
	"pluvialis apricaria" => "Aves",
	"glaucidium passerinum" => "Aves",
	"nycticorax nycticorax" => "Aves",
	"cygnus columbianus" => "Aves",
	"cygnus olor" => "Aves",
	"cygnus cygnus" => "Aves",
	"alcedo atthis" => "Aves",
	"ficedula albicollis" => "Aves",
	"ficedula hypoleuca" => "Aves",
	"ficedula parva" => "Aves",
	"muscicapa striata" => "Aves",
	"caprimulgus europaeus" => "Aves",
	"anthus spinoletta" => "Aves",
	"anthus trivialis" => "Aves",
	"anthus pratensis" => "Aves",
	"anthus petrosus" => "Aves",
	"anthus cervinus" => "Aves",
	"anthus campestris" => "Aves",
	"elanus caeruleus" => "Aves",
	"milvus milvus" => "Aves",
	"milvus migrans" => "Aves",
	"fulica atra" => "Aves",
	"phalaropus fulicarius" => "Aves",
	"phalaropus lobatus" => "Aves",
	"anas clypeata" => "Aves",
	"coracias garrulus" => "Aves",
	"aegithalos caudatus" => "Aves",
	"tarsiger cyanurus" => "Aves",
	"mergellus albellus" => "Aves",
	"mergus serrator" => "Aves",
	"mergus merganser" => "Aves",
	"circus pygargus" => "Aves",
	"circus cyaneus" => "Aves",
	"circus aeruginosus" => "Aves",
	"circus macrourus" => "Aves",
	"remiz pendulinus" => "Aves",
	"aquila fasciata" => "Aves",
	"aquila heliaca" => "Aves",
	"aquila pomarina" => "Aves",
	"haliaeetus albicilla" => "Aves",
	"hieraaetus pennatus" => "Aves",
	"aquila chrysaetos" => "Aves",
	"aquila nipalensis" => "Aves",
	"aquila clanga" => "Aves",
	"circaetus gallicus" => "Aves",
	"pandion haliaetus" => "Aves",
	"nucifraga caryocatactes" => "Aves",
	"anas acuta" => "Aves",
	"falco eleonorae" => "Aves",
	"falco subbuteo" => "Aves",
	"glareola nordmanni" => "Aves",
	"glareola pratincola" => "Aves",
	"pelecanus onocrotalus" => "Aves",
	"sylvia melanocephala" => "Aves",
	"sylvia atricapilla" => "Aves",
	"sylvia communis" => "Aves",
	"sylvia undata" => "Aves",
	"sylvia nana" => "Aves",
	"sylvia curruca" => "Aves",
	"sylvia borin" => "Aves",
	"sylvia nisoria" => "Aves",
	"fringilla montifringilla" => "Aves",
	"fringilla coelebs" => "Aves",
	"montifringilla nivalis" => "Aves",
	"prunella modularis" => "Aves",
	"prunella collaris" => "Aves",
	"actitis hypoleucos" => "Aves",
	"himantopus himantopus" => "Aves",
	"phoenicopterus ruber" => "Aves",
	"aythya fuligula" => "Aves",
	"aythya marila" => "Aves",
	"aythya nyroca" => "Aves",
	"aythya collaris" => "Aves",
	"aythya ferina" => "Aves",
	"falco naumanni" => "Aves",
	"falco tinnunculus" => "Aves",
	"falco vespertinus" => "Aves",
	"podiceps nigricollis" => "Aves",
	"podiceps cristatus" => "Aves",
	"podiceps auritus" => "Aves",
	"gavia immer" => "Aves",
	"gavia stellata" => "Aves",
	"gavia arctica" => "Aves",
	"gavia adamsii" => "Aves",
	"strix uralensis" => "Aves",
	"strix aluco" => "Aves",
	"ichthyaetus audouinii" => "Aves",
	"larus cachinnans" => "Aves",
	"chroicocephalus philadelphia" => "Aves",
	"larus canus" => "Aves",
	"ichthyaetus melanocephalus" => "Aves",
	"larus delawarensis" => "Aves",
	"ardea alba" => "Aves",
	"chroicocephalus ridibundus" => "Aves",
	"hydrocoloeus minutus" => "Aves",
	"larus marinus" => "Aves",
	"larus glaucoides" => "Aves",
	"xema sabini" => "Aves",
	"larus michahellis" => "Aves",
	"larus argentatus" => "Aves",
	"larus hyperboreus" => "Aves",
	"rissa tridactyla" => "Aves",
	"ichthyaetus ichthyaetus" => "Aves",
	"larus fuscus" => "Aves",
	"acrocephalus scirpaceus" => "Aves",
	"acrocephalus paludicola" => "Aves",
	"acrocephalus schoenobaenus" => "Aves",
	"acrocephalus melanopogon" => "Aves",
	"acrocephalus arundinaceus" => "Aves",
	"acrocephalus palustris" => "Aves",
	"falco cherrug" => "Aves",
	"phoenicurus ochruros" => "Aves",
	"phoenicurus phoenicurus" => "Aves",
	"apus apus" => "Aves",
	"apus melba" => "Aves",
	"chlidonias hybrida" => "Aves",
	"chlidonias leucopterus" => "Aves",
	"gelochelidon nilotica" => "Aves",
	"chlidonias niger" => "Aves",
	"sterna paradisaea" => "Aves",
	"sternula albifrons" => "Aves",
	"sterna hirundo" => "Aves",
	"thalasseus sandvicensis" => "Aves",
	"hydroprogne caspia" => "Aves",
	"hippolais icterina" => "Aves",
	"hippolais caligata" => "Aves",
	"hippolais pallida" => "Aves",
	"monticola saxatilis" => "Aves",
	"cinclus cinclus" => "Aves",
	"lullula arborea" => "Aves",
	"eremophila alpestris" => "Aves",
	"alauda arvensis" => "Aves",
	"luscinia svecica" => "Aves",
	"luscinia megarhynchos" => "Aves",
	"luscinia luscinia" => "Aves",
	"gallinula chloropus" => "Aves",
	"lymnocryptes minimus" => "Aves",
	"scolopax rusticola" => "Aves",
	"plectrophenax nivalis" => "Aves",
	"garrulus glandarius" => "Aves",
	"falco peregrinus" => "Aves",
	"tyto alba" => "Aves",
	"surnia ulula" => "Aves",
	"bubo scandiacus" => "Aves",
	"carduelis carduelis" => "Aves",
	"pica pica" => "Aves",
	"dendrocopos leucotos" => "Aves",
	"dendrocopos syriacus" => "Aves",
	"dendrocopos minor" => "Aves",
	"dendrocopos medius" => "Aves",
	"dendrocopos major" => "Aves",
	"emberiza leucocephalos" => "Aves",
	"emberiza cirlus" => "Aves",
	"emberiza bruniceps" => "Aves",
	"emberiza calandra" => "Aves",
	"emberiza pusilla" => "Aves",
	"emberiza citrinella" => "Aves",
	"emberiza aureola" => "Aves",
	"emberiza schoeniclus" => "Aves",
	"emberiza rustica" => "Aves",
	"calcarius lapponicus" => "Aves",
	"emberiza cia" => "Aves",
	"emberiza hortulana" => "Aves",
	"troglodytes troglodytes" => "Aves",
	"gyps fulvus" => "Aves",
	"aegypius monachus" => "Aves",
	"neophron percnopterus" => "Aves",
	"aegolius funereus" => "Aves",
	"athene noctua" => "Aves",
	"cyanistes cyanus" => "Aves",
	"poecile palustris" => "Aves",
	"parus major" => "Aves",
	"poecile montana" => "Aves",
	"cyanistes caeruleus" => "Aves",
	"lophophanes cristatus" => "Aves",
	"periparus ater" => "Aves",
	"panurus biarmicus" => "Aves",
	"certhia familiaris" => "Aves",
	"certhia brachydactyla" => "Aves",
	"sturnus vulgaris" => "Aves",
	"sturnus roseus" => "Aves",
	"recurvirostra avosetta" => "Aves",
	"morus bassanus" => "Aves",
	"tetrao urogallus" => "Aves",
	"lanius minor" => "Aves",
	"lanius collurio" => "Aves",
	"lanius senator" => "Aves",
	"lanius excubitor" => "Aves",
	"melanitta nigra" => "Aves",
	"melanitta fusca" => "Aves",
	"haematopus ostralegus" => "Aves",
	"pernis apivorus" => "Aves",
	"hirundo rustica" => "Aves",
	"hirundo daurica" => "Aves",
	"merops apiaster" => "Aves",
	"tringa glareola" => "Aves",
	"tringa ochropus" => "Aves",
	"xenus cinereus" => "Aves",
	"tringa totanus" => "Aves",
	"tringa nebularia" => "Aves",
	"tringa stagnatilis" => "Aves",
	"tringa erythropus" => "Aves",
	"tringa melanoleuca" => "Aves",
	"ardea purpurea" => "Aves",
	"ardea cinerea" => "Aves",
	"bubulcus ibis" => "Aves",
	"egretta garzetta" => "Aves",
	"ardeola ralloides" => "Aves",
	"passer domesticus" => "Aves",
	"passer montanus" => "Aves",
	"corvus corone" => "Aves",
	"corvus cornix" => "Aves",
	"bubo bubo" => "Aves",
	"otus scops" => "Aves",
	"tichodroma muraria" => "Aves",
	"netta rufina" => "Aves",
	"carduelis chloris" => "Aves",
	"serinus citrinella" => "Aves",
	"serinus serinus" => "Aves",
	"picus canus" => "Aves",
	"picus viridis" => "Aves",
	"oriolus oriolus" => "Aves",
	"alca torda" => "Aves",
	"cursorius cursor" => "Aves",
	"calonectris diomedea" => "Aves",
	"marmaronetta angustirostris" => "Aves",
	"chlamydotis macqueenii" => "Aves",
	"turdus atrogularis" => "Aves",
	"chen caerulescens" => "Aves",
	"tadorna ferruginea" => "Aves",
	"melanocorypha calandra" => "Aves",
	"falco biarmicus" => "Aves",
	"falco rusticolus" => "Aves",
	"hippolais polyglotta" => "Aves",
	"syrrhaptes paradoxus" => "Aves",
	"phasianus colchicus" => "Aves",
	"syrmaticus reevesii" => "Aves",
	"branta canadensis" => "Aves",
	"columba livia" => "Aves",
	"alopochen aegyptiaca" => "Aves",
	"threskiornis aethiopicus" => "Aves",
	"aix galericulata" => "Aves",
	"oxyura jamaicensis" => "Aves",
	"bucephala albeola" => "Aves",
	"bucephala islandica" => "Aves",
	"lophodytes cucullatus" => "Aves",
	"histrionicus histrionicus" => "Aves",
	"gypaetus barbatus" => "Aves",
	"melopsittacus undulatus" => "Aves",
	"anas flavirostris" => "Aves",
	"anas sibilatrix" => "Aves",
	"anser indicus" => "Aves",
	"dendrocygna arcuata" => "Aves",
	"chenonetta jubata" => "Aves",
	"aix sponsa" => "Aves",
	"platalea alba" => "Aves",
	"meleagris gallopavo" => "Aves",
	"cygnus atratus" => "Aves",
	"leptoptilos crumeniferus" => "Aves",
	"alectoris graeca" => "Aves",
	"anas bahamensis" => "Aves",
	"cairina moschata" => "Aves",
	"phoenicopterus chilensis" => "Aves",
	"monticola solitarius" => "Aves",
	"perisoreus infaustus" => "Aves",
	"cyanopica cyanus" => "Aves",
	"acipenser ruthenus" => "Actinopterygii",
	"acipenser sturio" => "Actinopterygii",
	"huso huso" => "Actinopterygii",
	"anguilla anguilla" => "Actinopterygii",
	"alosa alosa" => "Actinopterygii",
	"salmo salar" => "Actinopterygii",
	"hucho hucho" => "Actinopterygii",
	"oncorhynchus mykiss" => "Actinopterygii",
	"salvelinus fontinalis" => "Actinopterygii",
	"coregonus lavaretus" => "Actinopterygii",
	"coregonus oxyrinchus" => "Actinopterygii",
	"thymallus thymallus" => "Actinopterygii",
	"esox lucius" => "Actinopterygii",
	"umbra krameri" => "Actinopterygii",
	"rutilus rutilus" => "Actinopterygii",
	"rutilus pigus" => "Actinopterygii",
	"leucaspius delineatus" => "Actinopterygii",
	"leuciscus leuciscus" => "Actinopterygii",
	"leuciscus idus" => "Actinopterygii",
	"phoxinus phoxinus" => "Actinopterygii",
	"pseudorasbora parva" => "Actinopterygii",
	"scardinius erythrophthalmus" => "Actinopterygii",
	"aspius aspius" => "Actinopterygii",
	"tinca tinca" => "Actinopterygii",
	"chondrostoma nasus" => "Actinopterygii",
	"gobio gobio" => "Actinopterygii",
	"romanogobio kesslerii" => "Actinopterygii",
	"romanogobio albipinnatus" => "Actinopterygii",
	"barbus barbus" => "Actinopterygii",
	"alburnus alburnus" => "Actinopterygii",
	"alburnoides bipunctatus" => "Actinopterygii",
	"blicca bjoerkna" => "Actinopterygii",
	"abramis brama" => "Actinopterygii",
	"vimba vimba" => "Actinopterygii",
	"pelecus cultratus" => "Actinopterygii",
	"rhodeus amarus" => "Actinopterygii",
	"carassius carassius" => "Actinopterygii",
	"carassius auratus" => "Actinopterygii",
	"cyprinus carpio" => "Actinopterygii",
	"ctenopharyngodon idella" => "Actinopterygii",
	"hypophthalmichthys molitrix" => "Actinopterygii",
	"hypophthalmichthys nobilis" => "Actinopterygii",
	"barbatula barbatula" => "Actinopterygii",
	"misgurnus fossilis" => "Actinopterygii",
	"cobitis taenia" => "Actinopterygii",
	"cobitis elongatoides" => "Actinopterygii",
	"silurus glanis" => "Actinopterygii",
	"ameiurus nebulosus" => "Actinopterygii",
	"lota lota" => "Actinopterygii",
	"gasterosteus aculeatus" => "Actinopterygii",
	"perca fluviatilis" => "Actinopterygii",
	"sander lucioperca" => "Actinopterygii",
	"sander volgensis" => "Actinopterygii",
	"gymnocephalus schraetser" => "Actinopterygii",
	"gymnocephalus baloni" => "Actinopterygii",
	"zingel zingel" => "Actinopterygii",
	"zingel streber" => "Actinopterygii",
	"micropterus salmoides" => "Actinopterygii",
	"lepomis gibbosus" => "Actinopterygii",
	"proterorhinus marmoratus" => "Actinopterygii",
	"cottus gobio" => "Actinopterygii",
	"cottus poecilopus" => "Actinopterygii",
	"platichthys flesus" => "Actinopterygii",
	"canis aureus" => "Mammalia",
	"mustela lutreola" => "Mammalia",
	"bison bonasus" => "Mammalia",
	"myotis brandti" => "Mammalia",
	"pipistrellus kuhlii" => "Mammalia",
	"erinaceus roumanicus" => "Mammalia",
	"sorex alpinus" => "Mammalia",
	"lissotriton helveticus" => "Amphibia",
	"lissotriton montandoni" => "Amphibia",
	"lissotriton vulgaris" => "Amphibia",
	"salamandra salamandra" => "Amphibia",
	"triturus carnifex" => "Amphibia",
	"triturus cristatus" => "Amphibia",
	"triturus dobrogicus" => "Amphibia",
	"bombina bombina" => "Amphibia",
	"bombina variegata" => "Amphibia",
	"bufo bufo" => "Amphibia",
	"epidalea calamita" => "Amphibia",
	"hyla arborea" => "Amphibia",
	"pelobates fuscus" => "Amphibia",
	"pelophylax lessonae" => "Amphibia",
	"pelophylax ridibundus" => "Amphibia",
	"rana arvalis" => "Amphibia",
	"rana temporaria" => "Amphibia",
	"emys orbicularis" => "Reptilia",
	"testudo graeca" => "Reptilia",
	"testudo hermanni" => "Reptilia",
	"testudo horsfieldii" => "Reptilia",
	"testudo marginata" => "Reptilia",
	"trachemys scripta" => "Reptilia",
	"podarcis muralis" => "Reptilia",
	"lacerta vivipara" => "Reptilia",
	"hemidactylus turcicus" => "Reptilia",
	"natrix natrix" => "Reptilia",
	"natrix tessellata" => "Reptilia",
	"vipera berus" => "Reptilia",
	"carassius gibelio" => "Actinopterygii",
	"lampetra planeri" => "Cephalaspidomorphi",
	"tachybaptus ruficollis" => "Aves",
	"saxicola rubicola" => "Aves",
	"lyrurus tetrix" => "Aves",
	"podiceps grisegena" => "Aves",
	"chroicocephalus genei" => "Aves",
	"anser brachyrhynchus" => "Aves",
	"calidris ruficollis" => "Aves",
	"callonetta leucophrys" => "Aves",
];

$package = Json::decode(file_get_contents('package.json'));

foreach ($package->organism as $scientificName => $organismData) {
	if (isset($ranks[$scientificName])) {
		$rank = $ranks[$scientificName];
		if ($rank == 'Mammalia') $organismData->concepts[] = 'cz_mammals';
		if ($rank == 'Aves') $organismData->concepts[] = 'cz_birds';
		if ($rank == 'Reptilia') $organismData->concepts[] = 'cz_reptiles';
		if ($rank == 'Amphibia') $organismData->concepts[] = 'cz_amphibians';
		if ($rank == 'Cephalaspidomorphi' || $rank == 'Actinopterygii') $organismData->concepts[] = 'cz_fishes';
	}
}

$package = file_put_contents('package.json.new', Json::encode($package, Json::PRETTY));