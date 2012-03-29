<?php
/* $Id$ */

function getAllUsers() {
    $opts = array("ignore_errors" => true);
    $ctx = stream_context_create(array("http" => $opts));
    $token = getenv("TOKEN");
    if (!$token) {
        $token = trim(file_get_contents("../token"));
    }
    $retval = @file_get_contents("https://master.php.net/fetch/allusers.php?&token=" . rawurlencode($token), false, $ctx);
    if (!$retval) {
        return;
    }
    $json = json_decode($retval, true);
    if (!is_array($json)) {
        return;
    }
    if (isset($json["error"])) {
        return;
    }
    return $json;
}

if(!$json = apc_fetch("cvsusers")) {
    $json = getAllUsers();
    if ($json) {
        apc_store("cvsusers", $json, 3600);
        apc_store("cvsusers_update", $_SERVER["REQUEST_TIME"], 3600);
    }
}
$modified = apc_fetch("cvsusers_update");

if (!$json) { return; }

$tsstring = gmdate("D, d M Y H:i:s ", $modified);
if (isset($_SERVER["HTTP_IF_MODIFIED_SINCE"]) && $_SERVER["HTTP_IF_MODIFIED_SINCE"] == $tsstring) {
    header("HTTP/1.1 304 Not Modified");
    exit;
}
else {
    $expires = gmdate("D, d M Y H:i:s ", strtotime("+2 months", $_SERVER["REQUEST_TIME"])) . "GMT";
    header("Last-Modified: " . $tsstring);
    header("Expires: $expires");
}

$lookup = $user = array();

foreach($json as $row) {
    $lookup[] = $row["name"];
    $lookup[] = $row["username"];

    $data = array(
        "email"    => md5($row["username"] . "@php.net"),
        "name"     => $row["name"],
        "username" => $row["username"],
    );

    $user[$row["username"]] = $data;
    $user[$row["name"]]     = $data;
}
echo 'var users = ' . json_encode($user) . ';';
echo 'var lookup = ' . json_encode($lookup) . ';';


// vim: set expandtab shiftwidth=4 softtabstop=4 tabstop=4 : 

