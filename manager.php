<?php

///
/// Load bookmark storage
///

libxml_use_internal_errors(true);
$storage = array("id" => 0, "links" => array());

if (file_exists("./data/bookmarks.dat")) {
  $storage = unserialize(file_get_contents("./data/bookmarks.dat"));
}

///
/// Actions
///

$actions = array();

$actions['add'] = function() {
  global $storage;
  $id = id();

  $storage["links"][$id] = array(
    "link" => pparam('link', 'http://www.google.com/'), // @todo make sure this is always set
    "description" => pparam('description', ''),
    "tags" => pparam('tags', array()),
  );

  // Fills title and favicon
  fill_data($id);

  // Return result
  return_result("0", $id);
};

$actions['modify'] = function() {

};

$actions['delete'] = function() {

};

$actions['list'] = function() {

};

///
/// Helper functions
///

function return_result($code, $message) {
  echo json_encode(array("status" => $code, "data" => $message));
}

function pparam($key, $default) {
  return array_key_exists($key, $_POST) ? $_POST[$key] : $default;
}

function id() {
  global $storage;
  return $storage["id"]++;
}

function fill_data($id) {
  global $storage;

  $doc = new DOMDocument();
  $doc->strictErrorChecking = FALSE;
  $doc->loadHTML(utf8_encode(file_get_contents($storage["links"][$id]["link"])));
  $xml = simplexml_import_dom($doc);

  $storage["links"][$id]["favicon"] = $xml->xpath('//link[@rel="shortcut icon"]')[0]['href']->asXML(); // favicon
  $storage["links"][$id]["title"] = (string)$xml->xpath('//title')[0]; // title
}

///
/// Execute action and return result
///

if (!array_key_exists('action', $_GET)) {
  return_result("1", "No action specified");
} elseif (!array_key_exists($_GET['action'], $actions)) {
  return_result("2", "No such action");
} else {
  $actions[$_GET['action']]();
}

///
/// Save bookmark storage
///

file_put_contents("./data/bookmarks.dat", serialize($storage));

?>