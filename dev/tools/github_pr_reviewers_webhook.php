<?php
/* Copyright (C) 2025       Marc de Lima Lucio      <marc-dll@user.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/*
 * This code handles GitHub webhooks so that when a pull request is
 * created or edited (in case it is not draft), or made ready for review,
 * a list of targeted users is assigned to it as reviewers. This is
 * done using GitHub REST API.
 *
 * You must first create an API token. I strongly advise to create a
 * "fine-grained token", this will enable you to restrict access to
 * features. To do so:
 * * Go to your account settings (top right menu > "Settings")
 * * Click on "Developer settings" on the bottom left
 * * Click on left menu "Personal access tokens" > "Fine-grained tokens"
 * * On the top right, click on "Generate new token"
 * * Fill the necessary information:
 *     - "Token name"
 *     - "Resource owner": if the targeted repository is part of an
 *       organization, you must choose it
 *     - "Expiration": self explanatory
 *           /!\ This script does not handle token renewal
 *     - "Repository access": unless you have a specific use-case,
 *       choose "Only select repositories" and select the targeted
 *       repository
 *           /!\ I couldn't select the repository with AdBlock enabled
 *     - "Permissions": only the following permission is required:
 *           + "Repository permissions" > "Pull requests" : Read and
 *             write
 *     - Click on "Generate token", confirm in the pop-in and copy the
 *       value
 *
 * Put this script on a publicly-available Web location. Create a config
 * PHP file that returns an array with the following indices (all are
 * required):
 * * `'reviewers'`: an `array` associating branches to their reviewers,
 *   identified by their GitHub login. The values are either another
 *   `array` or a `string` if there is only one reviewer
 * * `'secret'`: a `string` that will be used by GitHub to sign its
 *   request
 *       /!\ While GitHub doesn't require a secret to be set, this script
 *           currently does not work without one.
 * * `'token'`: the token created in the step above.
 *
 * Then, you must create said webhook on the repository of your choice:
 * * Go to your repository homepage
 * * Click on "Settings" tab
 * * Under left menu "Code and automation", click on "Webhooks"
 * * On the top right, click on "Add webhook"
 * * Fill the necessary information:
 *     - "Payload URL" is the path to this script
 *           You can add `?debug` to output more information:wq;
 *     - "Content type" should be left as "application/x-www-form-
 *       urlencoded"
 *           /!\ This script currently does not handle "application/json"
 *     - "Secret": copy the secret you set in the config file
 *     - If you want to restrict the events passed to this script, select
 *       "Let me select individual events", check "Pull requests" and
 *       uncheck everything else
 * * Validate by clicking on "Add webhook"
 *
 * A `ping` webhook will then be sent to the URL you set. You can see a
 * log of deliveries by clicking on "Edit" and then on the tab "Recent
 * Deliveries". By clicking on any delivery, you can see the request and
 * the response, and also "Redeliver" it.
 */

header('Content-Type: text/plain');

define('GITHUB_API_VERSION', '2022-11-28');


$config = @require_once __DIR__ . '/github_pr_reviewers_webhook.config.php';

if (false === $config) {
	_error('Could not load config');
}

$secret = $config['secret'] ?? '';

if (empty($secret)) {
	_error('Empty secret configuration');
}

$reviewers = $config['reviewers'] ?? [ ];

if (empty($reviewers)) {
	_error('Empty reviewers configuration');
}

$token = $config['token'] ?? '';

if (empty($token)) {
	_error('Empty token configuration');
}


$event = $_SERVER['HTTP_X_GITHUB_EVENT'] ?? '';

if (empty($event)) {
	_error('GitHub event name not found', 400);
}


$signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';

if (empty($signature)) {
	_error('Message signature not found', 400);
}

$expectedSignature = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $secret);

_debug('expectedSignature = ' . var_export($expectedSignature, true));
_debug('signature = ' . var_export($signature, true));

// Use `hash_equals()` instead of direct comparison to avoir timing attacks (@see https://www.php.net/manual/en/function.hash-equals.php)
if (! hash_equals($signature, $expectedSignature)) {
	_error('Invalid webhook signature', 401);
}

_debug('event = ' . var_export($event, true));

if ('pull_request' !== $event) {
	_out('Event ' . var_export($event, true) . ' not qualified');
	exit;
}

$rawPayload = $_REQUEST['payload'] ?? '';

if (empty($rawPayload)) {
	_error('Empty payload', 400);
}

// _debug('rawPayload = ' . var_export($payload, true));

$payload = json_decode($rawPayload, /* associative: */ true);

if (null === $payload) {
	_error('Could not decode payload, got ' . var_export($rawPayload, true) . ' in input');
}

// _debug('payload = ' . var_export($payload, true));


$targetBranch = $payload['pull_request']['base']['ref'] ?? null;

if (null === $targetBranch) {
	_error('Target branch not in payload');
}

_debug('targetBranch = ' . var_export($targetBranch, true));


if (! array_key_exists($targetBranch, $reviewers)) {
	_out('Target branch ' . var_export($targetBranch, true) . ' not qualified');
	exit;
}

$wantedReviewers = $reviewers[$targetBranch];

if (! is_array($wantedReviewers) && ! is_string($wantedReviewers)) {
	_error('Wanted reviewers incorrectly set in config for branch ' . var_export($targetBranch, true));
}

if (! is_array($wantedReviewers) && ! empty($wantedReviewers)) {
	$wantedReviewers = [ $wantedReviewers ];
}

if (empty($wantedReviewers)) {
	_out('Branch ' . var_export($targetBranch, true) . ' configured with no reviewers, not qualified');
	exit;
}

$action = $payload['action'] ?? null;

if (null === $action) {
	_error('Pull request action not in payload');
}


if ('opened' !== $action && 'edited' !== $action && 'ready_for_review' !== $action) {
	_out('Action ' . var_export($action, true) . ' not qualified');
	exit;
}


$isDraft = $payload['pull_request']['draft'] ?? null;

if (null === $isDraft) {
	_error('Pull request draft boolean not in payload');
}

if ('ready_for_review' !== $action && $isDraft) {
	_out('Pull request opened as draft : not qualified');
	exit;
}


$author = $payload['pull_request']['user']['login'] ?? null;

if (null === $author) {
	_error('Pull request author not in payload');
}

$currentReviewers = $payload['pull_request']['requested_reviewers'] ?? null;

if (null === $currentReviewers) {
	_error('Current reviewers not in payload');
}

// GitHub API returns an error 422 if we try to add the author as a reviewer, we have to filter them out
$reviewersToBeAdded = array_diff($wantedReviewers, $currentReviewers, empty($author) ? [ ] : [ $author ]);

if (empty($reviewersToBeAdded)) {
	_out('Reviewers already requested or author of the pull request : not qualified');
	exit;
}


_out('Webhook qualified');
_debug('Adding reviewers: ' . implode(', ', $reviewersToBeAdded));


$pullRequestUrl = $payload['pull_request']['url'] ?? null;

if (null === $pullRequestUrl) {
	_error('Pull request API URL not in payload');
}

$c = curl_init($pullRequestUrl . '/requested_reviewers');

if (false === $c) {
	_error('Could not init cURL');
}


$setMethodReturn = curl_setopt($c, CURLOPT_CUSTOMREQUEST, 'POST');

if (false === $setMethodReturn) {
	_error('Could not set request method: ' . curl_error($c));
}

$setHeadersReturn = curl_setopt($c, CURLOPT_HTTPHEADER, [
	'Accept: application/vnd.github+json',
	'Authorization: Bearer ' . $token,
	'X-GitHub-Api-Version: ' . GITHUB_API_VERSION,
	'User-Agent: dolibarr-github-webhook-handler/1.0 dolibarr/20250616', // PHP cURL implementation has no default User-Agent yet, and GitHub REST API requires one
	'Content-Type: application/json',
]);

if (false === $setHeadersReturn) {
	_error('Could not set request headers: ' . curl_error($c));
}

$setBodyReturn = curl_setopt($c, CURLOPT_POSTFIELDS, json_encode([
	'reviewers' => $reviewersToBeAdded,
	'team_reviewers' => [ ], // TODO
]));


/*
$setFailOnErrorReturn = curl_setopt($c, CURLOPT_FAILONERROR, true);

if (false === $setFailOnErrorReturn) {
	_error('Could not set fail on error: ' . curl_error($c));
}
 */

$setReturnTransferReturn = curl_setopt($c, CURLOPT_RETURNTRANSFER, true);

if (false === $setReturnTransferReturn) {
	_error('Could not set return transfer: ' . curl_error($c));
}

$response = curl_exec($c);

if (false === $response) {
	_error('Error handling cURL request: ' . curl_error($c));
}

$responseCode = curl_getinfo($c, CURLINFO_RESPONSE_CODE);

if (false === $responseCode) {
	_error('Error getting response code: ' . curl_error($c));
}

_debug('responseCode = ' . $responseCode);

if ($responseCode < 200 || $responseCode > 399) {
	_error('Error from GitHub API, code ' . $responseCode . ': ' . $response);
}

_out('Added the following reviewers: ' . implode(', ', $reviewersToBeAdded));

curl_close($c);


/**
 * Echoes a message with an EOL
 *
 * @param	string	$message	The message to print
 * @return	void
 */
function _out(string $message): void
{
	echo $message . PHP_EOL;
}

/**
 * Echoes a message if debug mode is enabled
 *
 * @param	string	$message	The message to print
 * @return	void
 */
function _debug(string $message): void
{
	if (isset($_REQUEST['debug'])) {
		_out($message);
	}
}

/**
 * Exits with an error message and an HTTP response code
 *
 * @param	string	$message	The message to print
 * @param	int		$status		HTTP response code
 * @return	void
 */
function _error(string $message, int $status = 500): void
{
	http_response_code($status);
	_out('Error: ' . $message);
	exit;
}
