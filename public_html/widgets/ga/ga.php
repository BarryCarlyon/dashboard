<?php

require_once 'lib/google-api-php-client/src/Google_Client.php';
require_once 'lib/google-api-php-client/src/contrib/Google_AnalyticsService.php';

$client = new Google_Client();
$client->setApplicationName('Real Time');

$client->setClientId('42833402772.apps.googleusercontent.com');
$client->setClientSecret('wM0p4HlcIZ8y4PnzF2z5U7qp');
$client->setRedirectUri('http://fadashboard.net');
$client->setDeveloperKey('AIzaSyBxqzMZNEsjchKhkquJyx5ilgWqLuAnumI');
$client->setScopes(array('https://www.googleapis.com/auth/analytics.readonly'));

$client->setUseObjects(true);

if (isset($_GET['code'])) {
    $client->authenticate();
    $_SESSION['token'] = $client->getAccessToken();
    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
} else if (isset($_GET['gadisconnect'])) {
    session_destroy();
    $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
    header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

if (isset($_SESSION['token'])) {
    $client->setAccessToken($_SESSION['token']);
}

ob_start();

?>
<li id="gara" data-col="1" data-row="1" data-sizex="1" data-sizey="1">
<?php

if (!$client->getAccessToken()) {
    $authUrl = $client->createAuthUrl();
    print "<a class='login' href='$authUrl'>Connect Me!</a>";
} else {
    // Create analytics service object. See next step below.
    $analytics = new Google_AnalyticsService($client);
    fetchGaRa($analytics);
    echo '<a href="?gadisconnect=1">Disconnect</a>';
}

?>
</li>
<?php

$widgets[] = ob_get_contents();
ob_end_clean();

function fetchGaRa(&$analytics) {
    try {
//        $profileId = getFirstProfileId($analytics);
        $profileId = '3703340';

        if (isset($profileId)) {
            $results = getResults($analytics, $profileId);
            printResults($results);
        }
    } catch (apiServiceException $e) {
        // Error from the API.
        print 'There was an API error : ' . $e->getCode() . ' : ' . $e->getMessage();
    } catch (Exception $e) {
        print 'There wan a general error : ' . $e->getMessage();
    }
}


/**
Utility
*/
function getFirstprofileId(&$analytics) {
  $accounts = $analytics->management_accounts->listManagementAccounts();

  if (count($accounts->getItems()) > 0) {
    $items = $accounts->getItems();
    $firstAccountId = $items[0]->getId();

    $webproperties = $analytics->management_webproperties
        ->listManagementWebproperties($firstAccountId);

    if (count($webproperties->getItems()) > 0) {
      $items = $webproperties->getItems();
      $firstWebpropertyId = $items[0]->getId();

      $profiles = $analytics->management_profiles
          ->listManagementProfiles($firstAccountId, $firstWebpropertyId);

      if (count($profiles->getItems()) > 0) {
        $items = $profiles->getItems();
        return $items[0]->getId();

      } else {
        throw new Exception('No profiles found for this user.');
      }
    } else {
      throw new Exception('No webproperties found for this user.');
    }
  } else {
    throw new Exception('No accounts found for this user.');
  }
}

function getResults(&$analytics, $profileId) {
   return $analytics->data_ga->get(
       'ga:' . $profileId,
       date('Y-m-d', time()),
       date('Y-m-d', time()),
       'ga:visits');
//       '2012-03-03',
//       '2012-03-03',
}

function printResults(&$results) {
  if (count($results->getRows()) > 0) {
    $profileName = $results->getProfileInfo()->getProfileName();
    $rows = $results->getRows();
    $visits = $rows[0][0];

    echo '<p>' . $profileName . '</p>';
//    print "<p>First profile found: $profileName</p>";
    print "<p>Total visits: $visits</p>";

  } else {
    print '<p>No results found.</p>';
  }
}
