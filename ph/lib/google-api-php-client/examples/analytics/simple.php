<?php
require_once '../../src/Google_Client.php';
require_once '../../src/contrib/Google_AnalyticsService.php';
session_start();

$client = new Google_Client();
$client->setApplicationName("Google Analytics PHP Starter Application");
$client->setUseObjects(true);

// Visit https://code.google.com/apis/console?api=analytics to generate your
// client id, client secret, and to register your redirect uri.
// $client->setClientId('insert_your_oauth2_client_id');
// $client->setClientSecret('insert_your_oauth2_client_secret');
// $client->setRedirectUri('insert_your_oauth2_redirect_uri');
// $client->setDeveloperKey('insert_your_developer_key');
$analytics = new Google_AnalyticsService($client);

if (isset($_GET['logout'])) {
  unset($_SESSION['token']);
}

if (isset($_GET['code'])) {
  $client->authenticate();
  $_SESSION['token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
  $client->setAccessToken($_SESSION['token']);
}

echo '<pre>';
if ($client->getAccessToken()) {
  $accounts = $analytics->management_accounts->listManagementAccounts();

//  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
//    $firstAccountId = $items[0]->getId();
    foreach ($items as $account) {
      $firstAccountId = $account->getId();

    $webproperties = $analytics->management_webproperties
        ->listManagementWebproperties($firstAccountId);

//    if (count($webproperties->getItems()) > 0) {
      $items = $webproperties->getItems();
//      $firstWebpropertyId = $items[0]->getId();
      foreach ($items as $item) {
        $profiles = $analytics->management_profiles
            ->listManagementProfiles($firstAccountId, $item->getId());
        print_r($profiles->getItems());
      }
/*
      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();
        return $items[0]->getId();

      } else {
        throw new Exception('No profiles found for this user.');
      }
*/
//    } else {
//      throw new Exception('No webproperties found for this user.');
//    }
//  } else {
  //  throw new Exception('No accounts found for this user.');
  }

/*
  $props = $service->management_webproperties->listManagementWebproperties("~all");
  print "<h1>Web Properties</h1><pre>" . print_r($props, true) . "</pre>";

  $accounts = $service->management_accounts->listManagementAccounts();
  print "<h1>Accounts</h1><pre>" . print_r($accounts, true) . "</pre>";

  $accounts = $service->management_accounts->listManagementAccounts();
  print "<h1>Accounts</h1><pre>" . print_r($accounts, true) . "</pre>";

  $goals = $service->management_goals->listManagementGoals("~all", "~all", "~all");
  print "<h1>Segments</h1><pre>" . print_r($goals, true) . "</pre>";

  */
  $_SESSION['token'] = $client->getAccessToken();
} else {
  $authUrl = $client->createAuthUrl();
  print "<a class='login' href='$authUrl'>Connect Me!</a>";
}