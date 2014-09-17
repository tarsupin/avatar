<?php if(!defined("CONF_PATH")) { die("No direct script access allowed."); }

// Load Forum-Specific Panel Links
$userPanel['Doll']['Get Free Auro'] = "/user-panel/free-auro";
$userPanel['Doll']['Transactions'] = "/user-panel/transactions";

// Reorder the Panel
$userPanel = array('Doll' => $userPanel['Doll']) + $userPanel;