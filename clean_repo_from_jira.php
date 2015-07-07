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

// define supported arguments
$arguments_intended = array(
    'verbose' => '-v|-verbose',
);

// define pattern: match with the first occurence of "(anything)_"
$rejex_pattern = '/(.*?)_/';

$branches_to_delete = array();
$verbose = false;

// check passed arguments
foreach ($argv as $order => $argument) {
    if (in_array($argument, explode('|', $arguments_intended['verbose']))) {
        $verbose = true;
    }
}

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

if ($verbose) {
    echo 'Retrieving branches with `git branch`.' . "\n";
}

// list Git branches
exec('git branch', $branches);

// user display
echo "\033[36m" . 'Searching issues, please wait...' . "\033[0m\n";

foreach ($branches as $branch) {
    // clean current branch name
    $current_marker = '* ';
    $pos = strpos($branch, $current_marker);

    if ($pos !== false) {
        $branch = str_replace($current_marker, '', $branch);
    }
    // check matches
    if (preg_match($rejex_pattern, $branch, $matches)) {
        $issue_name = trim($matches[1]);
        curl_setopt($ch, CURLOPT_URL, $url . $issue_name);

        if ($verbose) {
            echo 'Checking ' . $issue_name . ' issue.' . "\n";
        }

        // ask API
        $result = curl_exec($ch);
        $ch_error = curl_error($ch);

        if ($ch_error) {
            echo 'cURL Error: $ch_error';
        } else {
            // parse API response
            $issue_datas = json_decode($result);

            // if issue exists
            if (empty($issue_datas->errorMessages)) {
                // if issue is Done

                if ($verbose) {
                    echo 'Issue exists.' . "\n";
                }
                if (
                    $issue_datas->fields->resolution !== null
                    && in_array($issue_datas->fields->resolution->name, $resolution_cases)
                ) {
                    if ($verbose) {
                        echo 'Issue is Done, add ' . trim($branch) . ' to the to-delete list.' . "\n";
                        echo "\n";
                    }

                    // fill waiting list
                    $branches_to_delete[] = trim($branch);
                }
            }
        }
    }
}

// stop curl
curl_close($ch);

// user confirmation
echo count($branches_to_delete) . ' branch(es) will be deleted, continue? (y/n) ';
$input = fgetc(STDIN);

if (
    $input == 'y'
    || $input == 'yes'
) {
    // user feedback
    echo "\033[36m" . 'Deleting branches...' . "\033[0m\n";

    foreach ($branches_to_delete as $branch) {
        if ($verbose) {
            echo 'Deleting ' . $branch . ' with `git branch -D`.' . "\n";
        }

        // delete branch
        exec('git branch -D ' . $branch);
    }

    // user feedback
    echo "\033[36m" . 'Finished.' . "\033[0m\n";
}

?>
