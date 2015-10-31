<?php
define("HASH_FUNC", "crc32");
$userAgents = json_decode(file_get_contents("user-agents.json"), true);
$meta = ["meta" => ["hash" => HASH_FUNC]];
$compiled = array_merge($meta, ["userAgents" => []]);
$priorities = array_merge($meta, ["userAgents" => [20 => [], 100 => [], "rest" => []]]);
foreach ($userAgents as $userAgent => $meta) {
    $compiledUserAgent = hexdec(hash(HASH_FUNC, $userAgent));
    echo "Loading $userAgent with $compiledUserAgent...", PHP_EOL;
    if (isset($compiled["userAgents"][intval($compiledUserAgent)])) {
        throw new \Exception(HASH_FUNC . " collision!");
    }
    $compiled["userAgents"][intval($compiledUserAgent)] = array_merge(["name" => $userAgent], $meta);
    if (!isset($meta["meta"]["priority"])) {
        $priority = "rest";
    } else {
        $priority = $meta["meta"]["priority"];
    }
    $priorities["userAgents"][$priority][intval($compiledUserAgent)] = $compiled["userAgents"][intval($compiledUserAgent)];
}

$json = json_encode($compiled, JSON_NUMERIC_CHECK);
$jsonPriorities = json_encode($priorities, JSON_NUMERIC_CHECK);
file_put_contents("compiled-user-agents.json", $json);
file_put_contents("compiled-priority-user-agents.json", $jsonPriorities);

$phpFile = sprintf('<?php%sreturn %s;', PHP_EOL, var_export($compiled, true));
file_put_contents("compiled-user-agents.php", $phpFile);
echo "Done", PHP_EOL;