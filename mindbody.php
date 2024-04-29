<?php
/**
 * Mindbody Api Calls
 *
 * This file contains api calls to MZ Mindbody
 *
 * @package MzRegistrantsListing
 */

namespace MZoo\MzRegistrantsListing;

use MZoo\MzMindbody as NS;

// Exit if accessed directly
defined( 'ABSPATH' ) || exit;

class MzMboApiCalls {

  /**
   * Mindbody Credentials Options
   */
  public $mindbody_credentials;

  /**
   * Stored Oauth Token
   */
  public $stored_token;

  /**
   * Customer Has Studio Account
   */
  public $customer_has_studio_account;

  /**
   * Constructor
   *
   * @since 1.0.0
   * @access public
   */

  public function __construct() {
    $this->mindbody_credentials = get_option( 'mzmbo_oauth_options' );
  }
    /**
     * Get User Token
     *
     * Get Oauth token from MBO API.
     *
     * Documentation not linked in MBO API docs, but found here:
     * https://developers.mindbodyonline.com/PlatformDocumentation#post-token
     *
     * @since 1.0.0
     * @access public
     * @return TODO
     *
     */
    public function get_oauth_token() {
    /*
    If a client that is logging in doesn't have an OAuth login but a local login,
    then they will be asked to verify their email. Once they verify the email it
    will then create that OAuth login for them.
    */
        $nonce = wp_create_nonce( 'mz_mbo_authenticate_with_api' );
        $id_token = $_POST['id_token'];
        $request_body = array(
            'method'                => 'POST',
            'timeout'               => 55,
            'httpversion'           => '1.0',
            'blocking'              => true,
            'headers'               => '',
            'body'                  => [
                'client_id'     => $this->mindbody_credentials['mz_mindbody_client_id'],
                'grant_type'      => 'authorization_code',
                'scope'         => 'email profile openid offline_access Mindbody.Api.Public.v6 PG.ConsumerActivity.Api.Read',
                'client_secret'    => $this->mindbody_credentials['mz_mindbody_client_secret'],
                'code'                => $_POST['code'],
                'redirect_uri'    => home_url() . '/mzmbo/authenticate',
                'nonce'                => $nonce
            ],
            'redirection'             => 0,
            'cookies'       => array()
        );
        $response = wp_remote_request(
            "https://signin.mindbodyonline.com/connect/token",
            $request_body
        );

        if ( is_wp_error( $response ) ) {
            $error_message = $response->get_error_message();
            echo "Something went wrong in getting your auth token: $error_message";
        } else {
            $response_body = json_decode($response['body']);
            if (empty($response_body->access_token)) {
                return false;
            } else {
        $this->save_oauth_token($response_body->access_token);
                return $response_body->access_token;
            }
        }
    }

  /**
     * Check token with MBO API
     *
     * Retrieve the users universal id from MBO API.
     *
     * @since 1.0.0
   * @param string $token
   * @return object|false $response_body
     */
    function get_universal_id($token) {
        $response = wp_remote_request(
            "https://api.mindbodyonline.com/platform/accounts/v1/me",
            array(
                'method'                => 'GET',
                'timeout'               => 55,
                'httpversion'           => '1.0',
                'blocking'              => true,
                'headers'               => [
                    'API-Key'             => \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mbo_api_key'],
                    'Authorization' => 'Bearer ' . $token
                ],
                'body'                  => '',
                'redirection'             => 0,
                'cookies'       => array()
            )
        );
        $response_body = json_decode($response['body']);
    \Mzoo\MzMindbody\MZMBO()->helpers->log($response_body);
    if (empty($response_body->id)) {
        return false;
    }
    $this->customer_has_studio_account = false;
    $this->save_universal_details([
        'id' => $response_body->id,
        'email' => $response_body->email,
        'firstName' => $response_body->firstName,
        'lastName' => $response_body->lastName
    ]);
    $siteID = \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mindbody_siteID'];
    if ($siteID === "-99") {
        $_SESSION['MindbodyAuth']['MBO_USER_Business_ID'] = $siteID;
        $retClient = new \MZoo\MzMindbody\Client\RetrieveClient();
        $result = $retClient->get_clients("", $response_body->email);
        if (isset($result['Clients'][0]['Id'])) {
            $_SESSION['MindbodyAuth']['MBO_USER_StudioProfile_ID'] = $result['Clients'][0]['Id'];
            $this->customer_has_studio_account = true;
        } else {
            NS\MZMBO()->helpers->log("No client found");
        }
        return $response_body;
    }
    foreach($response_body->businessProfiles as $studio){
      \Mzoo\MzMindbody\MZMBO()->helpers->log($studio);
      if ( (int) $siteID === $studio->businessId ) {
        $_SESSION['MindbodyAuth']['MBO_USER_StudioProfile_ID'] = $studio->profileId;
        $_SESSION['MindbodyAuth']['MBO_USER_Business_ID'] = $studio->businessId;
        $this->customer_has_studio_account = true;
      }
    }
    /*
    Weve assigned your case 04568535 to BUG-16659.

    This does seem to be related to the unique setup the Sandbox site has.
    It seems the response should return the client profile information you just added, but once you add
    the profile, it becomes synced to a completely different user:

    We do expect that more and more developers will want to test integrations in the API Sandbox site
    with the Platform API features, so this will present a bigger problem as times goes on.

    I have submitted a bug report to the development team and in the ticket I have proposed a
    request to overhaul the method that is used to refresh the site each day so that these synced
    accounts are not presenting these kinds of issues.
    */
    if ($siteID === "-99") {
      $_SESSION['MindbodyAuth']['MBO_USER_Business_ID'] = $siteID;
      $this->customer_has_studio_account = true;
      return $response_body;
    }
    return $response_body;
    }

  /**
   * Request Studio Registration
   *
   *  DEPRECATED
   * @since 1.0.0
   * @param object $response_body
   */
  public function request_studio_registration($response_body){
    echo '<script>window.close();</script>';
  }

    /**
     * Save Oauth Token
     *
     * Store Oauth token in $_SESSION..
     *
     * @since 1.0.0
     * @param string $token Oauth Token from MBO API.
     *
     */
  public function save_oauth_token($token) {
        $current = new \DateTime();

        $this->stored_token = array(
            'stored_time' => $current->format( 'Y-m-d H:i:s' ),
            'AccessToken' => $token,
        );

    $_SESSION['MindbodyAuth'] = empty($_SESSION['MindbodyAuth']) ? array() : $_SESSION['MindbodyAuth'];

        $_SESSION['MindbodyAuth']['MBO_Public_Oauth_Token'] = $this->stored_token;
    }

      /**
     * Save Universal ID
     *
     * Store universal id in $_SESSION.
     *
     * @since 1.0.0
     * @param array containing id, email, firstName, lastName from MBO API.
     *
     */
  public function save_universal_details($universal_account) {
        $_SESSION['MindbodyAuth']['MBO_Universal_Account'] = $universal_account;
    }

  /**
   * Register User with Studio
   *
   * IMPORTANT API NOTES:
   * The documentation on that endpoint is not totally accurate. The "businessId" should be used in the body of the request
   * instead of the Header. Leaving that out of the request body will return an error about the business ID.
   * Another thing that we found during testing is that passing information from the Identity account, email, first name, last
   * name, also seems to return an error. That information should populate automatically, so the request would only need the
   * required fields that the business asks for, such as "address_line_1", "state", "city", and "postal_code". The property
   * "names" will be underscored like that and be all lower case.
   *
   * @since 1.0.0
   * @param array $params from user form to submit to Mindbody.
   */
  public function register_user_with_studio( $params ) {
    $contactProps = [];
    foreach($params as $k=>$v) {
      $contactProps[] = ['name' => lcfirst($k), 'value' => $v];
    }
    $request_body = array(
        'method'                => 'POST',
        'timeout'               => 55,
        'httpversion'           => '1.0',
        'blocking'              => true,
        'headers'               => [
          'API-Key'                 => \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mbo_api_key'],
          'Authorization'        => 'Bearer ' . $_SESSION['MindbodyAuth']['MBO_Public_Oauth_Token']['AccessToken'],
          'Content-Type'        => 'application/json',
        ],
        'body'                => json_encode([
            "userId" => $_SESSION['MindbodyAuth']['MBO_Universal_Account']['id'],
            'BusinessId'      => \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mindbody_siteID'],
            // Can we count on form containing all required fields?
            "contactProperties" => $contactProps
        ]),
        'redirection'             => 0,
        'cookies'                        => array()
    );

    /* I believe the following list shows all possibilities for the formatting of such fields through the OAuth API:

      external_id
      first_name
      last_name
      email
      middle_name
      address_line_1
      address_line_2
      city
      state
      country
      postal_code
      home_phone_number
      work_phone_number
      mobile_phone_number
      birth_date
      referred_by
      emergency_contact_name
      emergency_contact_email
      emergency_contact_phone
      emergency_contact_relationship
      gender

      –Raymond
      */

    // This will create a Studio Specific Account for user based on MBO Universal Account
    // https://api.mindbodyonline.com/platform/index.html
    $response = wp_remote_request(
      "https://api.mindbodyonline.com/platform/contacts/v1/profiles",
      $request_body
    );

    /* \MZoo\MzMindbody\MZMBO()->helpers->log("######request_body");
    \MZoo\MzMindbody\MZMBO()->helpers->log($request_body);
    \MZoo\MzMindbody\MZMBO()->helpers->log("######response");
    \MZoo\MzMindbody\MZMBO()->helpers->log($response); */

    if (is_wp_error($response)) {
      return array( 'error' => $response->get_error_message() );
    }
    NS\MZMBO()->helpers->log($response);
    switch ($response['response']['code']) {
      case 200:
        // Success
        // Store the user's studio account ID in the session
                $siteID = \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mindbody_siteID'];
                if ((string) "-99" === (string) $siteID) {
          /*
          Weve assigned your case 04568535 to BUG-16659.

          This does seem to be related to the unique setup the Sandbox site has.
          It seems the response should return the client profile information you just added, but once you add
          the profile, it becomes synced to a completely different user:

          We do expect that more and more developers will want to test integrations in the API Sandbox site
          with the Platform API features, so this will present a bigger problem as times goes on.

          I have submitted a bug report to the development team and in the ticket I have proposed a
          request to overhaul the method that is used to refresh the site each day so that these synced
          accounts are not presenting these kinds of issues.
          */
          $_SESSION['MindbodyAuth']['MBO_USER_Business_ID'] = $siteID;
          $retClient = new \MZoo\MzMindbody\Client\RetrieveClient();
          $result = $retClient->get_clients('', $_SESSION['MindbodyAuth']['MBO_Universal_Account']['email']);
          if (isset($result['Clients'][0]['Id'])) {
            $_SESSION['MindbodyAuth']['MBO_USER_StudioProfile_ID'] = $result['Clients'][0]['Id'];
            $this->customer_has_studio_account = true;
          }
        } else {
          $_SESSION['MindbodyAuth']['MBO_USER_StudioProfile_ID'] = $client->Id;
          $this->customer_has_studio_account = true;
        }
        $json = json_decode($response['body']);
        $client = $json['Client'];
        $_SESSION['MindbodyAuth']['MBO_USER_Business_ID'] = \MZoo\MzMindbody\Core\MzMindbodyApi::$basic_options['mz_mindbody_siteID'];
        $_SESSION['MindbodyAuth']['MBO_USER_StudioProfile_ID'] = $client->Id;
        $this->customer_has_studio_account = true;
        return ["success" => "User successfully registered with studio."];
        break;
      case 409:
        // Duplicate
        return ["error" => "User already registered with studio."];
        break;
      default:
        // Error
        return ["error" => $response['body']];
        break;
    }

  }
}

 ?>
