<?php

namespace OAuth\OAuth2\Service;

use OAuth\OAuth2\Token\StdOAuth2Token;
use OAuth\Common\Http\Exception\TokenResponseException;
use OAuth\Common\Http\Uri\Uri;
use OAuth\Common\Consumer\CredentialsInterface;
use OAuth\Common\Http\Client\ClientInterface;
use OAuth\Common\Storage\TokenStorageInterface;
use OAuth\Common\Http\Uri\UriInterface;

class Microsoft extends AbstractService
{
    const SCOPE_BASIC = 'basic';
    const SCOPE_OFFLINE_ACCESS = 'offline_access';
    const SCOPE_SIGNIN = 'signin';
    const SCOPE_BIRTHDAY = 'birthday';
    const SCOPE_CALENDARS = 'calendars';
    const SCOPE_CALENDARS_UPDATE = 'calendars_update';
    const SCOPE_CONTACTS_BIRTHDAY = 'contacts_birthday';
    const SCOPE_CONTACTS_CREATE = 'contacts_create';
    const SCOPE_CONTACTS_CALENDARS = 'contacts_calendars';
    const SCOPE_CONTACTS_PHOTOS = 'contacts_photos';
    const SCOPE_CONTACTS_SKYDRIVE = 'contacts_skydrive';
    const SCOPE_EMAIL = 'email';
    const SCOPE_EVENTS_CREATE = 'events_create';
    const SCOPE_MESSENGER = 'messenger';
    const SCOPE_OPENID = 'openid';
    const SCOPE_PHONE_NUMBERS = 'phone_numbers';
    const SCOPE_PHOTOS = 'photos';
    const SCOPE_POSTAL_ADDRESSES = 'postal_addresses';
    const SCOPE_PROFILE = 'profile';
    const SCOPE_SHARE = 'share';
    const SCOPE_SKYDRIVE = 'skydrive';
    const SCOPE_SKYDRIVE_UPDATE = 'skydrive_update';
    const SCOPE_WORK_PROFILE = 'work_profile';
    const SCOPE_APPLICATIONS = 'applications';
    const SCOPE_APPLICATIONS_CREATE = 'applications_create';
    const SCOPE_IMAP = 'imap';
    const SCOPE_IMAP_ACCESSASUSERALL = 'https://outlook.office365.com/IMAP.AccessAsUser.All';
    const SCOPE_SMTPSEND = 'https://outlook.office365.com/SMTP.Send';
    const SCOPE_USERREAD = 'User.Read';
    const SCOPE_MAILREAD = 'Mail.Read';
    const SCOPE_MAILSEND = 'Mail.Send';

    protected $storage;


    /**
     * MS uses some magical not officialy supported scope to get even moar info like full emailaddresses.
     * They agree that giving 3rd party apps access to 3rd party emailaddresses is a pretty lame thing to do so in all
     * their wisdom they added this scope because fuck you that's why.
     *
     * https://github.com/Lusitanian/PHPoAuthLib/issues/214
     * http://social.msdn.microsoft.com/Forums/live/en-US/c6dcb9ab-aed4-400a-99fb-5650c393a95d/how-retrieve-users-
     *                                  contacts-email-address?forum=messengerconnect
     *
     * Considering this scope is not officially supported: use with care
     */
    const SCOPE_CONTACTS_EMAILS = 'contacts_emails';


    public function __construct(
        CredentialsInterface $credentials,
        ClientInterface $httpClient,
        TokenStorageInterface $storage,
        $scopes = array(),
        UriInterface $baseApiUri = null
    ) {
        parent::__construct($credentials, $httpClient, $storage, $scopes, $baseApiUri);

        $this->storage = $storage;

        if (null === $baseApiUri) {
            $this->baseApiUri = new Uri('https://apis.live.net/v5.0/');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationEndpoint()
    {
    	$tenant = $this->storage->getTenant();

    	//return new Uri('https://login.live.com/oauth20_authorize.srf');
        //return new Uri('https://login.microsoftonline.com/organizations/oauth2/v2.0/authorize');
        return new Uri('https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/authorize');
    }

    /**
     * {@inheritdoc}
     */
    public function getAccessTokenEndpoint()
    {
    	$tenant = $this->storage->getTenant();

        //return new Uri('https://login.live.com/oauth20_token.srf');
        //return new Uri('https://login.microsoftonline.com/organizations/oauth2/v2.0/token');
        return new Uri('https://login.microsoftonline.com/'.$tenant.'/oauth2/v2.0/token');
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthorizationMethod()
    {
        return static::AUTHORIZATION_METHOD_QUERY_STRING;
    }

    /**
     * {@inheritdoc}
     */
    protected function parseAccessTokenResponse($responseBody)
    {
        $data = json_decode($responseBody, true);

        if (null === $data || !is_array($data)) {
            throw new TokenResponseException('Unable to parse response.');
        } elseif (isset($data['error'])) {
            throw new TokenResponseException('Error in retrieving token: "' . $data['error'] . '"');
        }
        //print $data['access_token'];exit;

        $token = new StdOAuth2Token();
        $token->setAccessToken($data['access_token']);
        $token->setLifetime($data['expires_in']);

        if (isset($data['refresh_token'])) {
            $token->setRefreshToken($data['refresh_token']);
            unset($data['refresh_token']);
        }

        unset($data['access_token']);
        unset($data['expires_in']);

        $token->setExtraParams($data);

        return $token;
    }
}
