<?php

// Simple Bookmark Manager, https://github.com/invokr/Simple-Bookmark-Manager

///
/// Load bookmark storage
///

libxml_use_internal_errors(true);
$storage = array("id" => 1, "links" => array()); // Start at 1

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
    "link" => pparam('link', ''),
    "description" => pparam('description', ''),
    "tags" => explode(", ", pparam('tags', "")),
    "id" => $id
  );

  // Fills title and favicon
  fill_data($id);

  if ($storage["links"][$id]['favicon'] != "") {
    file_put_contents("cache/$id.ico", file_get_contents($storage["links"][$id]['favicon']));
  }

  // Sort, @todo: check performance for many bookmarks
  usort($storage["links"], function($a, $b) {
    return $a['title'] > $b['title'];
  });

  // Return result
  return_result("0", $id);
};

$actions['modify'] = function() {
  global $storage;
  $id = pparam('id', -1);

  if ($id === -1 || !array_key_exists($id, $storage['links'])) {
    return_result("4", "Unkown link ID specified");
  } else {
    $storage['links'][$id]['description'] = pparam('description', '');
    $storage['links'][$id]['tags'] = explode(", ", pparam('tags', ""));
    return_result("0", "");
  }
};

$actions['delete'] = function() {
  global $storage;
  $id = pparam('id', -1);

  if ($id === -1 || !array_key_exists($id, $storage['links'])) {
    return_result("4", "Unkown link ID specified");
  } else {
    unset($storage['links'][$id]);
    return_result("0", "");
  }
};

$actions['list'] = function() {
  global $storage;
  $id = pparam('id', -1);

  if ($id === -1) {
    return_result("0", $storage['links']);
  } elseif (array_key_exists($id, $storage['links'])) {
    return_result("0", $storage['links'][$id]);
  } else {
    return_result("3", "Unkown link ID requested");
  }
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

  $fav = "";
  $t_fav1 = $xml->xpath('//link[@rel="icon"]');
  $t_fav2 = $xml->xpath('//link[@rel="shortcut icon"]');

  if (count($t_fav1)) {
    $fav = $t_fav1[0]['href'][0]->__toString();
  } else if (count($t_fav2)) {
    $fav = $t_fav2[0]['href'][0]->__toString();
  }

  if ($fav) {
    if (substr($fav, 0, 4) != "http") {
      if (substr($fav, 0, 2) == "//") {
        $fav = "http:".$fav;
      } else {
        $data = parse_url($storage["links"][$id]["link"]);

        if ($fav[0] == '/') {
          // absolute path
          $fav = $data["scheme"]."://".$data["host"].$fav;
        } else {
          // relative path
          $fav = $data["scheme"]."://".$data["host"]."?".$data["argument"].$fav;
        }
       }
    }
   }

  $storage["links"][$id]["favicon"] = $fav;
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