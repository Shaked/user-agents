<?php

define("HASH_FUNC", "crc32");
$userAgents = json_decode(file_get_contents("user-agents.json"), true);
$compiled = ["meta" => ["hash" => HASH_FUNC], "userAgents" => []];
foreach ($userAgents as $userAgent => $meta) {
    $compiledUserAgent = hash(HASH_FUNC, $userAgent);
    echo "Loading $userAgent with $compiledUserAgent...", PHP_EOL;
    if (isset($compiled[$compiledUserAgent])) {
        throw new \Exception(HASH_FUNC . " collision!");
    }
    $compiled["userAgents"][$compiledUserAgent] = array_merge(["name" => $userAgent], $meta);
}

file_put_contents("compiled-user-agents.json", json_encode($compiled));
echo "Done", PHP_EOL;