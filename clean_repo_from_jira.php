<?php
/**
 * Clean Git repository branches according resolution in Jira
 * Branches names need to be like this: ISSUE-ID_issue-description
 */

// define Jira settings
$username = 'xxxxx'; // Jira account username
$password = 'xxxxx'; // Jira account pass

$url = 'https://xxxxxxxxxx.net/rest/api/latest/issue/';

$resolution_cases = array(
    'Done',
    'Fixed',
);

// define pattern: match with the first occurence of "(anything)_"
$rejex_pattern = '/(.*?)_/';

// curl init
$ch = curl_init();

// define API data type returns
$headers = array(
    'Accept: application/json',
    'Content-Type: application/json'
);

// curl request params
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");

// list Git branches
exec('git branch', $branches);

// user display
echo "\033[36m" . 'Searching Done issue' . "\033[0m\n";

foreach ($branches as $branch) {
    // clean current branch name
    $current_marker = '* ';
    $pos = strpos($branch, $current_marker);

    if ($pos !== false) {
        $branch = str_replace($current_marker, '', $branch);
    }
    // check matches
    if (preg_match($rejex_pattern, $branch, $matches)) {
        $current_issue = trim($matches[1]);
        curl_setopt($ch, CURLOPT_URL, $url . $current_issue);

        // ask API
        $result = curl_exec($ch);
        $ch_error = curl_error($ch);

        if ($ch_error) {
            echo 'cURL Error: $ch_error';
        } else {
            // parse API response
            $issue_datas = json_decode($result);

            // if ($issue_datas->fields->resolution['name'] !== null) {
            if (
                $issue_datas->fields->resolution !== null
                && in_array($issue_datas->fields->resolution->name, $resolution_cases)
            ) {
                // issue done delete the branch
                exec('git branch -D ' . $branch);
                echo $branch . ' deleted' . "\n";
            }
        }
    }
}

// stop curl
curl_close($ch);
?>
