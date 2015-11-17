<?php
define("HASH_FUNC", "crc32b");
$pathUserAgents = __DIR__ . "/user-agent";
$types = ["desktop", "tablet", "bot", "apps", "glass", "mobile"];
//TODO: define structures per type
$pathBuild = __DIR__ . "/packages";
$buildTypes = ["php", "json", "go"];
$meta = ["meta" => ["hash" => HASH_FUNC]];
$compiled = array_merge($meta, ["userAgents" => []]);
$priorities = array_merge($meta, ["userAgents" => [20 => [], 100 => [], "rest" => []]]);

/**
 * @param $path
 * @param array $types
 * @return mixed
 */
function loadUserAgentsList($path, array $types) {
    $userAgents = [];
    foreach ($types as $type) {
        $currentPath = sprintf("%s/%s/", $path, $type);
        $directoryIterator = new DirectoryIterator($currentPath);
        foreach ($directoryIterator as $fileInfo) {
            $currentFile = $fileInfo->getFilename();
            if ($fileInfo->getExtension() != "json") {
                continue;
            }
            if (0 === strpos($currentFile, ".")) {continue;}
            $list = file_get_contents($currentPath . $currentFile);
            $userAgent = json_decode($list, true);
            $userAgents = array_merge($userAgents, $userAgent);
        }
    }
    return $userAgents;

}
$userAgents = loadUserAgentsList($pathUserAgents, $types);

/**
 * @param array $userAgents
 * @param $compiled
 * @param $priorities
 */
function compileLists(array $userAgents, array &$compiled, array &$priorities) {
    foreach ($userAgents as $userAgent => $meta) {
        $userAgent = strtolower($userAgent);
        $compiledUserAgent = hexdec(hash(HASH_FUNC, $userAgent));
        echo "Loading $userAgent with $compiledUserAgent...", PHP_EOL;
        if (isset($compiled["userAgents"][$compiledUserAgent])) {
            $message = HASH_FUNC . " collision!: user agent: \n $userAgent \n";
            throw new \Exception($message);
        }
        $compiled["userAgents"][$compiledUserAgent] = array_merge(["name" => $userAgent], $meta);

        //merge referenced user agent
        if (isset($meta["ref"]) && !empty($userAgents[$meta["ref"]])) {
            $compiled["userAgents"][$compiledUserAgent] = array_replace_recursive($compiled["userAgents"][$compiledUserAgent], $userAgents[$meta["ref"]]);
        }

        if (!isset($meta["meta"]["priority"])) {
            $priority = "rest";
        } else {
            $priority = $meta["meta"]["priority"];
        }
        $priorities["userAgents"][$priority][$compiledUserAgent] = $compiled["userAgents"][$compiledUserAgent];
    }

}

compileLists($userAgents, $compiled, $priorities);

/**
 * @param $pathBuild
 * @param $type
 * @param $compiled
 * @param $priorities
 */
function build($pathBuild, $type, $compiled, $priorities) {

    switch ($type) {
    case "go":
        $json = json_encode($compiled, JSON_NUMERIC_CHECK);
        $jsonPriorities = json_encode($priorities, JSON_NUMERIC_CHECK);
        $goFile = <<<EOA
package gouseragents

var CompiledUserAgents = `%s`
var CompiledPriorityUserAgents = `%s`
EOA;
        $goFile = sprintf($goFile, $json, $jsonPriorities);
        file_put_contents($pathBuild . "/go/gouseragents/gouseragents.go", $goFile);
        break;
    case "json":
        $json = json_encode($compiled, JSON_NUMERIC_CHECK);
        $jsonPriorities = json_encode($priorities, JSON_NUMERIC_CHECK);
        file_put_contents($pathBuild . "/json/compiled-user-agents.json", $json);
        file_put_contents($pathBuild . "/json/compiled-priority-user-agents.json", $jsonPriorities);
        break;
    case "php":
        $phpFile = sprintf('<?php%sreturn %s;', PHP_EOL, var_export($compiled, true));
        $phpPriorities = sprintf('<?php%sreturn %s;', PHP_EOL, var_export($priorities, true));
        file_put_contents($pathBuild . "/php/compiled-user-agents.php", $phpFile);
        file_put_contents($pathBuild . "/php/compiled-priority-user-agents.php", $phpPriorities);
        break;

    }

}
foreach ($buildTypes as $type) {
    echo sprintf("compiling %s\n", $type);
    build($pathBuild, $type, $compiled, $priorities);
}

echo "Done", PHP_EOL;