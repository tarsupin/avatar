<?php
$toerase['female'] = array();
$toerase['male'] = array();

foreach ($outfit as $item)
{
	if (is_array($item))
	{
		if ($item != array())
			$item = $item[0];
		else
			$item = false;
	}
	
	switch ($item)
	{
		case 2489:	// Mermaid Tail Blue
		case 2490:	// Mermaid Tail Green
		case 2491:	// Mermaid Tail Pink
		case 2516:	// Octopus
		case 2517:	// Shark Tail
		case 2949:	// Ebony Centaur Legs
		case 2950:	// Chestnut Centaur Legs
		case 2951:	// Ivory Centaur Legs
			$toerase['female'][] = array(30, 220, 160, 163, array("base", "skin"), $item);
			$toerase['male'][] = array(30, 230, 150, 153, array("base", "skin"), $item);
			break;
		case 3169:	// Peg
			$toerase['female'][] = array(118, 263, 87, 65, array("base", "skin"), $item);
			$toerase['female'][] = array(136, 328, 69, 55, array("base", "skin"), $item);
			$toerase['male'][] = array(0, 260, 100, 123, array("base", "skin"), $item);
			$toerase['female'][] = array(116, 263, 89, 48, array("shoes"), $item);
			$toerase['female'][] = array(127, 311, 78, 19, array("shoes"), $item);
			$toerase['female'][] = array(136, 330, 69, 53, array("shoes"), $item);
			$toerase['male'][] = array(0, 262, 98, 121, array("shoes"), $item);
			break;
		case 3171:	// Hook
			$toerase['female'][] = array(143, 174, 62, 43, array("base", "skin"), $item);
			$toerase['male'][] = array(0, 171, 74, 53, array("base", "skin"), $item);
			break;
		case 1362:	// Booby Base
		case 3002:	// Heel Feet
		case 3248:	// Velvet Skin Heels
		case 3303:	// Ballerina Base
		case 3455:  // Velvet Ballerina
			$toerase['female'][] = array(0, 0, 205, 383, array("base"), $item);
			break;
		case 3302:	// Dancer Base
		case 3454:  // Velvet Dancer
			$toerase['female'][] = array(0, 0, 205, 383, array("base"), $item);
			$toerase['male'][] = array(0, 0, 205, 383, array("base"), $item);
			break;
	}
}

/*
	case IDNUMBER:
		$toerase['female'][] = array(UPPERLEFTX, UPPERLEFTY, WIDTH, HEIGHT, LAYERS, $item);	// data of the rectangle to be erased on female avis
		$toerase['female'][] = array(UPPERLEFTX, UPPERLEFTY, WIDTH, HEIGHT, LAYERS, $item);	// repeat with other data if you want to erase more than one rectangle
		$toerase['male'][] = array(UPPERLEFTX, UPPERLEFTY, WIDTH, HEIGHT, LAYERS, $item);	// same for male
		break;
	case IDNUMBER1:																			// same as above for a different item
	case IDNUMBER2:																			// do it this way if the same settings apply for both items 1 and 2
		...
		break;
*/
?>