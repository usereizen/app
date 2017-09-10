<?php
/**
 * Implements Special:UserLogin
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup SpecialPage
 */

/**
 * Implements Special:UserLogin
 * @ingroup SpecialPage
 */
class LoginForm extends SpecialPage {

	const SUCCESS = 0;
	const NO_NAME = 1;
	const ILLEGAL = 2;
	const WRONG_PLUGIN_PASS = 3;
	const NOT_EXISTS = 4;
	const WRONG_PASS = 5;
	const EMPTY_PASS = 6;
	const RESET_PASS = 7;
	const ABORTED = 8;
	const CREATE_BLOCKED = 9;
	const THROTTLED = 10;
	const USER_BLOCKED = 11;
	const NEED_TOKEN = 12;
	const WRONG_TOKEN = 13;

	var $mUsername, $mPassword, $mRetype, $mReturnTo, $mCookieCheck, $mPosted;
	var $mAction, $mCreateaccount, $mCreateaccountMail, $mMailmypassword;
	var $mLoginattempt, $mRemember, $mEmail, $mDomain, $mLanguage;
	var $mSkipCookieCheck, $mReturnToQuery, $mToken, $mStickHTTPS;
	var $mType, $mReason, $mName, $mRealName;
	var $mAbortLoginErrorMsg = 'login-abort-generic';
	private $mLoaded = false;
	var $mMarketingOptIn, $mRegistrationCountry;
	var $wpBirthYear, $wpBirthMonth, $wpBirthDay, $wpMsgPrefix;
	var $wpUserLoginExt, $wpUserBirthDay;

	/**
	 * @var ExternalUser_Wikia
	 */
	private $mExtUser = null;

	/**
	 * @ var WebRequest
	 */
	protected $mOverrideRequest = null; // Wikia change - used in UserLoginForm

	/**
	 * @param WebRequest $request
	 */
	public function __construct( WebRequest $request = null ) {
		parent::__construct( 'Userlogin' );

		$this->mOverrideRequest = $request;
	}

	/**
	 * Loader
	 */
	function load() {
		global $wgAuth, $wgHiddenPrefs, $wgEnableEmail;

		if ( $this->mLoaded ) {
			return;
		}
		$this->mLoaded = true;

		if ( $this->mOverrideRequest === null ) {
			$request = $this->getRequest();
		} else {
			$request = $this->mOverrideRequest;
		}

		$this->mType = $request->getText( 'type' );
		$this->mUsername = $request->getText( 'wpName' );
		$this->mPassword = $request->getText( 'wpPassword' );
		$this->mRetype = $request->getText( 'wpRetype' );
		$this->mDomain = $request->getText( 'wpDomain' );
		$this->mReason = $request->getText( 'wpReason' );
		$this->mCookieCheck = $request->getVal( 'wpCookieCheck' );
		$this->mPosted = $request->wasPosted();
		$this->mCreateaccount = $request->getCheck( 'wpCreateaccount' );
		$this->mCreateaccountMail = $request->getCheck( 'wpCreateaccountMail' )
									&& $wgEnableEmail;
		$this->mMailmypassword = $request->getCheck( 'wpMailmypassword' )
		                         && $wgEnableEmail;
		$this->mLoginattempt = $request->getCheck( 'wpLoginattempt' );
		$this->mAction = $request->getVal( 'action' );
		$this->mRemember = $request->getCheck( 'wpRemember' );
		$this->mStickHTTPS = $request->getCheck( 'wpStickHTTPS' );
		$this->mMarketingOptIn = $request->getCheck( 'wpMarketingOptIn' );
		$this->mRegistrationCountry = $request->getVal( 'wpRegistrationCountry' );
		$this->mLanguage = $request->getText( 'uselang' );
		$this->mSkipCookieCheck = $request->getCheck( 'wpSkipCookieCheck' );
		$this->mToken = ( $this->mType == 'signup' ) ? $request->getVal( 'wpCreateaccountToken' ) : $request->getVal( 'wpLoginToken' );

		$this->mReturnTo = $request->getVal( 'returnto', '' );
		$this->mReturnToQuery = $request->getVal( 'returntoquery', '' );

		if( $wgEnableEmail ) {
			$this->mEmail = $request->getText( 'wpEmail' );
		} else {
			$this->mEmail = '';
		}
		if( !in_array( 'realname', $wgHiddenPrefs ) ) {
			$this->mRealName = $request->getText( 'wpRealName' );
		} else {
			$this->mRealName = '';
		}

		if( !$wgAuth->validDomain( $this->mDomain ) ) {
			$this->mDomain = $wgAuth->getDomain();
		}
		$wgAuth->setDomain( $this->mDomain );

		$this->wpMsgPrefix = 'userlogin-error-';
		$this->wpUserLoginExt = true;

		$title = Title::newFromText($this->mReturnTo);
		if (!empty($title))
		{
			$this->mResolvedReturnTo = strtolower(array_shift(SpecialPageFactory::resolveAlias($title->getDBKey())));
			if(in_array($this->mResolvedReturnTo,array('userlogout','signup','connect')))
			{
				$this->mReturnTo = '';
				$this->mReturnToQuery = '';
			}
		}
	}
	function getDescription() {
		return $this->msg( $this->getUser()->isAllowed( 'createaccount' ) ?
			'userlogin' : 'userloginnocreate' )->text();
	}

	public function execute( $par ) {

		if ( session_id() == '' ) {
			wfSetupSession();
		}

		$this->load();
		$this->setHeaders();

		if ( $par == 'signup' ) { # Check for [[Special:Userlogin/signup]]
			$this->mType = 'signup';
			if(!$this->mToken) {
				$this->mToken = $this->getRequest()->getVal( 'wpCreateaccountToken' );
			}
		}

		if ( !is_null( $this->mCookieCheck ) ) {
			$this->onCookieRedirectCheck( $this->mCookieCheck );
			return;
		} elseif( $this->mPosted ) {
			if( $this->mCreateaccount ) {
				$this->addNewAccount();
				return;
			} elseif ( $this->mCreateaccountMail ) {
				$this->addNewAccountMailPassword();
				return;
			} elseif ( ( 'submitlogin' == $this->mAction ) || $this->mLoginattempt ) {
				$this->processLogin();
				return;
			}
		}
		$this->mainLoginForm( '' );
	}

	/**
	 * @private
	 */
	function addNewAccount() {
		global $wgUser, $wgEmailAuthentication, $wgLoginLanguageSelector;

		# Create the account and abort if there's a problem doing so
		$u = $this->addNewAccountInternal();
		if( $u == null ) {
			return false;
		}

		# If we showed up language selection links, and one was in use, be
		# smart (and sensible) and save that language as the user's preference
		if( $wgLoginLanguageSelector && $this->mLanguage ) {
			$u->setGlobalPreference( 'language', $this->mLanguage );
		}

		$out = $this->getOutput();

		# Send out an email authentication message if needed
		if( $wgEmailAuthentication && Sanitizer::validateEmail( $u->getEmail() ) ) {
			$status = $u->sendConfirmationMail();
			if( $status->isGood() ) {
				$out->addWikiMsg( 'confirmemail_oncreate' );
			} else {
				$out->addWikiText( $status->getWikiText( 'confirmemail_sendfailed' ) );
			}
		}

		# Save settings (including confirmation token)
		$u->saveSettings();

		# If not logged in, assume the new account as the current one and set
		# session cookies then show a "welcome" message or a "need cookies"
		# message as needed
		if( $this->getUser()->isAnon() ) {
			$u->setCookies();
			$wgUser = $u;
			// This should set it for OutputPage and the Skin
			// which is needed or the personal links will be
			// wrong.
			$this->getContext()->setUser( $u );
			Hooks::run( 'AddNewAccount', array( $u, false ) );
			$u->addNewUserLogEntry();
			if( $this->hasSessionCookie() ) {
				$this->successfulCreation();
			} else {
				$this->cookieRedirectCheck( 'new' );
			}
		} else {
			# Confirm that the account was created
			$out->setPageTitle( $this->msg( 'accountcreated' ) );
			$out->addWikiMsg( 'accountcreatedtext', $u->getName() );
			$out->addReturnTo( $this->getTitle() );

			Hooks::run( 'AddNewAccount', array( $u, false ) );

			$u->addNewUserLogEntry( false, $this->mReason );
		}
		return true;
	}

	/**
	 * @private
	 * @return bool|User
	 */
	function addNewAccountInternal() {
		global $wgAuth, $wgMemc, $wgAccountCreationThrottle,
			$wgMinimalPasswordLength, $wgEmailConfirmToEdit;

		// If the user passes an invalid domain, something is fishy
		if( !$wgAuth->validDomain( $this->mDomain ) ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'wrongpassword' )->text(), 'error', 'password' );
			return false;
		}

		// If we are not allowing users to login locally, we should be checking
		// to see if the user is actually able to authenticate to the authenti-
		// cation server before they create an account (otherwise, they can
		// create a local account and login as any domain user). We only need
		// to check this for domains that aren't local.
		if( 'local' != $this->mDomain && $this->mDomain != '' ) {
			if( !$wgAuth->canCreateAccounts() && ( !$wgAuth->userExists( $this->mUsername )
				|| !$wgAuth->authenticate( $this->mUsername, $this->mPassword ) ) ) {
				$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'wrongpassword' )->text(), 'error', 'password' );
				return false;
			}
		}

		if ( wfReadOnly() ) {
			throw new ReadOnlyError;
		}

		# Request forgery checks.
		if ( !self::getCreateaccountToken() ) {
			self::setCreateaccountToken();
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'sessionfailure' )->parse() );
			return false;
		}

		# The user didn't pass a createaccount token
		if ( !$this->mToken ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'sessionfailure' )->text() );
			return false;
		}

		# Validate the createaccount token
		if ( $this->mToken !== self::getCreateaccountToken() ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'sessionfailure' )->text() );
			return false;
		}

		# Check permissions
		$currentUser = $this->getUser();
		if ( !$currentUser->isAllowed( 'createaccount' ) ) {
			throw new PermissionsError( 'createaccount' );
		} elseif ( $currentUser->isBlockedFromCreateAccount() ) {
			$this->userBlockedMessage( $currentUser->isBlockedFromCreateAccount() );
			return false;
		}

		# Include checks that will include GlobalBlocking (Bug 38333)
		$permErrors = $this->getTitle()->getUserPermissionsErrors( 'createaccount', $currentUser, true );
		if ( count( $permErrors ) ) {
				throw new PermissionsError( 'createaccount', $permErrors );
		}

		$ip = $this->getRequest()->getIP();
		if ( $currentUser->isDnsBlacklisted( $ip, true /* check $wgProxyWhitelist */ ) ) {
			$this->mainLoginForm( $this->msg( 'sorbs_create_account_reason' )->text() . ' ' . $this->msg( 'parentheses', $ip )->escaped() );
			return false;
		}

		# Now create a dummy user ($u) and check if it is valid
		$name = trim( $this->mUsername );
		$u = User::newFromName( $name, 'creatable' );
		if ( !is_object( $u ) ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'noname' )->text(), 'error', 'username' );
			return false;
		}

		$this->mExtUser = ExternalUser_Wikia::newFromName( $this->mUsername );

		if ( is_object( $this->mExtUser ) && ( 0 != $this->mExtUser->getId() ) ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'userexists' )->text(), 'error', 'username' );
			return false;
		} elseif ( 0 != $u->idForName() ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'userexists' )->text(), 'error', 'username' );
			return false;
		}

		if ( 0 != strcmp( $this->mPassword, $this->mRetype ) ) {
			$this->mainLoginForm( $this->msg( 'badretype' )->text(), 'error', 'password' );
			return false;
		}

		# check for minimal password length
		$valid = $u->getPasswordValidity( $this->mPassword );
		if ( $valid !== true ) {
			if ( !$this->mCreateaccountMail ) {
				if ( is_array( $valid ) ) {
					$message = array_shift( $valid );
					$params = $valid;
				} else {
					$message = $valid;
					$params = array( $wgMinimalPasswordLength );
				}
				$this->mainLoginForm( $this->msg( $message, $params )->text(), 'error', 'password' );
				return false;
			} else {
				# do not force a password for account creation by email
				# set invalid password, it will be replaced later by a random generated password
				$this->mPassword = null;
			}
		}

		# if you need a confirmed email address to edit, then obviously you
		# need an email address.
		if ( $wgEmailConfirmToEdit && empty( $this->mEmail ) ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'noemailtitle' )->text(), 'error', 'email' );
			return false;
		}

		if( !empty( $this->mEmail ) && !Sanitizer::validateEmail( $this->mEmail ) ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'invalidemailaddress' )->text(), 'error', 'email' );
			return false;
		}

		# Set some additional data so the AbortNewAccount hook can be used for
		# more than just username validation
		$u->setEmail( $this->mEmail );
		$u->setRealName( $this->mRealName );

		$abortError = '';
		if( !Hooks::run( 'AbortNewAccount', array( $u, &$abortError ) ) ) {
			// Hook point to add extra creation throttles and blocks
			wfDebug( "LoginForm::addNewAccountInternal: a hook blocked creation\n" );
			/* Wikia change begin */
			$errParam = '';
			Hooks::run( 'AbortNewAccountErrorMessage', array( &$abortError, &$errParam ) );
			$this->mainLoginForm( $abortError, 'error', $errParam );
			/* Wikia change end */
			return false;
		}

		// Hook point to check for exempt from account creation throttle
		if ( !Hooks::run( 'ExemptFromAccountCreationThrottle', array( $ip ) ) ) {
			wfDebug( "LoginForm::exemptFromAccountCreationThrottle: a hook allowed account creation w/o throttle\n" );
		} else {
			if ( ( $wgAccountCreationThrottle && $currentUser->isPingLimitable() ) ) {
				/** WIKIA CHANGE BEGIN -- use wfSharedMemcKey here **/
				$key = wfSharedMemcKey( 'acctcreate', 'ip', $ip );
				/** WIKIA CHANGE END **/
				$value = $wgMemc->get( $key );
				if ( !$value ) {
					$wgMemc->set( $key, 0, 86400 );
				}
				if ( !F::app()->wg->DevelEnvironment && $value >= $wgAccountCreationThrottle ) {
					$this->throttleHit( $wgAccountCreationThrottle );
					return false;
				}
				$wgMemc->incr( $key );
			}
		}

		if( !$wgAuth->addUser( $u, $this->mPassword, $this->mEmail, $this->mRealName ) ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'externaldberror' )->text() );
			return false;
		}

		self::clearCreateaccountToken();
		$u->mBirthDate = date( 'Y-m-d', $this->wpUserBirthDay );

		if ( ! $this->initUser( $u, false ) ) {
			$this->mainLoginForm( $this->msg( $this->wpMsgPrefix . 'externaldberror' )->text() );
			return false;
		}

		return $u;
	}

	/**
	 * Actually add a user to the database.
	 * Give it a User object that has been initialised with a name.
	 *
	 * @param $u User object.
	 * @param $autocreate boolean -- true if this is an autocreation via auth plugin
	 * @return boolean true on success; false otherwise
	 * @private
	 */
	function initUser( User &$u, $autocreate ) {
		global $wgAuth, $wgExternalAuthType;

		if ( $wgExternalAuthType ) {
			if ( ExternalUser_Wikia::addUser( $u, $this->mPassword, $this->mEmail, $this->mRealName ) ) {
				$this->mExtUser = ExternalUser_Wikia::newFromName( $this->mUsername );
			} else {
				// Terminate on failure.
				return false;
			}
		} else {
			$u->addToDatabase();
		}

		if ( $wgAuth->allowPasswordChange() ) {
			$u->setPassword( $this->mPassword );
		}

		$u->setEmail( $this->mEmail );
		$u->setRealName( $this->mRealName );
		$u->setToken();

		$wgAuth->initUser( $u, $autocreate );

		if ( is_object( $this->mExtUser ) ) {
			$this->mExtUser->linkToLocal( $u->getId() );
			$email = $this->mExtUser->getPref( 'emailaddress' );
			if ( $email && !$this->mEmail ) {
				$u->setEmail( $email );
			}
		}

		$u->setGlobalAttribute( 'registrationCountry', $this->mRegistrationCountry );
		$u->setGlobalPreference( 'skinoverwrite', 1 );
		$u->setGlobalPreference( 'rememberpassword', $this->mRemember ? 1 : 0 );
		$u->setGlobalPreference( 'marketingallowed', $this->mMarketingOptIn ? 1 : 0 );

		$u->saveSettings();

		# Update user count
		$ssUpdate = new SiteStatsUpdate( 0, 0, 0, 0, 1 );
		$ssUpdate->doUpdate();

		return true;
	}

	/**
	 * Internally authenticate the login request.
	 *
	 * This may create a local account as a side effect if the
	 * authentication plugin allows transparent local account
	 * creation.
	 */
	public function authenticateUserData() {
		global $wgUser, $wgAuth;

		$this->load();

		/* Wikia change - begin */
		// This might not be needed once we're upgraded to MW 1.19 since that throws new ReadOnlyError in a few spots (test that).
		global $wgOut;
		if ( wfReadOnly() ) {
			if(is_object($wgOut)){
				$wgOut->readOnlyPage();
				return false;
			} else {
				$this->mAbortLoginErrorMsg = wfMsg( 'login-abort-readonly' ); // msg is in languages/messages/wikia/MessagesEn.php if we end up deleting this after the upgrade to MW 1.19
				return self::ABORTED;
			}
		}
		/* Wikia change - end */

		if ( $this->mUsername == '' ) {
			return self::NO_NAME;
		}

		// We require a login token to prevent login CSRF
		// Handle part of this before incrementing the throttle so
		// token-less login attempts don't count towards the throttle
		// but wrong-token attempts do.
		// If the user doesn't have a login token yet, set one.
		if ( !self::getLoginToken() ) {
			self::setLoginToken();
			return self::NEED_TOKEN;
		}
		// If the user didn't pass a login token, tell them we need one
		if ( !$this->mToken ) {
			return self::NEED_TOKEN;
		}

		$throttleCount = self::incLoginThrottle( $this->mUsername );
		if ( $throttleCount === true ) {
			return self::THROTTLED;
		}

		// Validate the login token
		if ( $this->mToken !== self::getLoginToken() ) {
			return self::WRONG_TOKEN;
		}

		// Load the current user now, and check to see if we're logging in as
		// the same name. This is necessary because loading the current user
		// (say by calling getName()) calls the UserLoadFromSession hook, which
		// potentially creates the user in the database. Until we load $wgUser,
		// checking for user existence using User::newFromName($name)->getId() below
		// will effectively be using stale data.
		if ( $this->getUser()->getName() === $this->mUsername ) {
			wfDebug( __METHOD__ . ": already logged in as {$this->mUsername}\n" );
			return self::SUCCESS;
		}

		# TODO: Allow some magic here for invalid external names, e.g., let the
		# user choose a different wiki name.
		$u = User::newFromName( $this->mUsername );
		if( !( $u instanceof User ) || !User::isUsableName( $u->getName() ) ) {
			return self::ILLEGAL;
		}

		$isAutoCreated = false;
		if ( 0 == $u->getID() ) {
			$status = $this->attemptAutoCreate( $u );
			if ( $status !== self::SUCCESS ) {
				return $status;
			} else {
				$isAutoCreated = true;
			}
		}

		// Give general extensions, such as a captcha, a chance to abort logins
		$abort = self::ABORTED;
		if( !Hooks::run( 'AbortLogin', array( $u, $this->mPassword, &$abort, &$this->mAbortLoginErrorMsg ) ) ) {
			return $abort;
		}

		global $wgBlockDisablesLogin;
		$abortedMessageKey = null;
		$authResult = $u->checkPassword( $this->mPassword, $abortedMessageKey );
		if ( !$authResult->success() ) {
			if ( $abortedMessageKey ) {
				$this->mAbortLoginErrorMsg = $abortedMessageKey;
				return self::ABORTED;
			}
			$retval = ( $this->mPassword  == '' ) ? self::EMPTY_PASS : self::WRONG_PASS;
		} elseif ( $authResult->isResetPasswordAuth() ) {
			// At this point we just return an appropriate code/ indicating
			// that the UI should show a password reset form; bot inter-
			// faces etc will probably just fail cleanly here.
			$retval = self::RESET_PASS;
		} elseif ( $wgBlockDisablesLogin && $u->isBlocked() ) {
			// If we've enabled it, make it so that a blocked user cannot login
			$retval = self::USER_BLOCKED;
		} else {
			$retval = self::SUCCESS;
		}

		if ( !in_array( $retval, [ self::SUCCESS, self::RESET_PASS ] ) ) {
			Hooks::run( 'LoginFormAuthenticateModifyRetval', [ $this, $u->getName(), $this->mPassword, &$retval, $authResult ] );
		}

		switch ($retval) {
			case self::SUCCESS:
				$this->onAuthenticateUserDataSuccess($u, $isAutoCreated, $throttleCount);
				break;
			case self::RESET_PASS:
				$this->onAuthenticateUserDataResetPass($u);
				break;
		}

		Hooks::run( 'LoginAuthenticateAudit', array( $u, $this->mPassword, $retval ) );
		return $retval;
	}

	private function onAuthenticateUserDataSuccess(User $u, $isAutoCreated, $throttleCount) {
		global $wgAuth, $wgUser;

		$wgAuth->updateUser( $u );
		$wgUser = $u;
		// This should set it for OutputPage and the Skin
		// which is needed or the personal links will be
		// wrong.
		$this->getContext()->setUser( $u );
		Hooks::run( 'AfterUserLogin', array( $u ) ); // Wikia change

		// Please reset throttle for successful logins, thanks!
		if ( $throttleCount ) {
			self::clearLoginThrottle( $this->mUsername );
		}

		if ( $isAutoCreated ) {
			// Must be run after $wgUser is set, for correct new user log
			Hooks::run( 'AuthPluginAutoCreate', array( $u ) );
		}
	}

	private function onAuthenticateUserDataResetPass(User $u) {
		// The e-mailed temporary password should not be used for actu-
		// al logins; that's a very sloppy habit, and insecure if an
		// attacker has a few seconds to click "search" on someone's o-
		// pen mail reader.
		//
		// Allow it to be used only to reset the password a single time
		// to a new value, which won't be in the user's e-mail ar-
		// chives.
		//
		// For backwards compatibility, we'll still recognize it at the
		// login form to minimize surprises for people who have been
		// logging in with a temporary password for some time.
		//
		// As a side-effect, we can authenticate the user's e-mail ad-
		// dress if it's not already done, since the temporary password
		// was sent via e-mail.
		if( !$u->isEmailConfirmed() ) {
			$u->confirmEmail();
			$u->saveSettings();
		}
	}

	/**
	 * Increment the login attempt throttle hit count for the (username,current IP)
	 * tuple unless the throttle was already reached.
	 * @param $username string The user name
	 * @return Bool|Integer The integer hit count or True if it is already at the limit
	 */
	public static function incLoginThrottle( $username ) {
		global $wgPasswordAttemptThrottle, $wgMemc, $wgRequest;
		$canUsername = User::getCanonicalName( $username, 'usable' );
		$username = $canUsername !== false ? $canUsername : $username;

		$throttleCount = 0;
		if ( is_array( $wgPasswordAttemptThrottle ) ) {
			$throttleKey = wfSharedMemcKey( 'password-throttle', $wgRequest->getIP(), md5( $username ) );
			$count = $wgPasswordAttemptThrottle['count'];
			$period = $wgPasswordAttemptThrottle['seconds'];

			$throttleCount = $wgMemc->get( $throttleKey );
			if ( !$throttleCount ) {
				$wgMemc->add( $throttleKey, 1, $period ); // start counter
			} elseif ( $throttleCount < $count ) {
				$wgMemc->incr( $throttleKey );
			} elseif ( $throttleCount >= $count ) {
				return true;
			}
		}

		return $throttleCount;
	}

	/**
	 * Clear the login attempt throttle hit count for the (username,current IP) tuple.
	 * @param $username string The user name
	 * @return void
	 */
	public static function clearLoginThrottle( $username ) {
		global $wgMemc, $wgRequest;
		$canUsername = User::getCanonicalName( $username, 'usable' );
		$username = $canUsername !== false ? $canUsername : $username;

		$throttleKey = wfSharedMemcKey( 'password-throttle', $wgRequest->getIP(), md5( $username ) );
		$wgMemc->delete( $throttleKey );
	}

	/**
	 * Attempt to automatically create a user on login. Only succeeds if there
	 * is an external authentication method which allows it.
	 *
	 * @param $user User
	 *
	 * @return integer Status code
	 */
	function attemptAutoCreate( $user ) {
		global $wgAuth;

		if ( $this->getUser()->isBlockedFromCreateAccount() ) {
			wfDebug( __METHOD__ . ": user is blocked from account creation\n" );
			return self::CREATE_BLOCKED;
		}

		/**
		 * If the external authentication plugin allows it, automatically cre-
		 * ate a new account for users that are externally defined but have not
		 * yet logged in.
		 */
		if ( $this->mExtUser ) {
			if ( !$this->mExtUser->authenticate( $this->mPassword ) ) {
				return self::WRONG_PLUGIN_PASS;
			}
		} else {
			# Old AuthPlugin.
			if ( !$wgAuth->autoCreate() ) {
				return self::NOT_EXISTS;
			}
			if ( !$wgAuth->userExists( $user->getName() ) ) {
				wfDebug( __METHOD__ . ": user does not exist\n" );
				return self::NOT_EXISTS;
			}
			if ( !$wgAuth->authenticate( $user->getName(), $this->mPassword ) ) {
				wfDebug( __METHOD__ . ": \$wgAuth->authenticate() returned false, aborting\n" );
				return self::WRONG_PLUGIN_PASS;
			}
		}

		$abortError = '';
		if( !Hooks::run( 'AbortAutoAccount', array( $user, &$abortError ) ) ) {
			// Hook point to add extra creation throttles and blocks
			wfDebug( "LoginForm::attemptAutoCreate: a hook blocked creation: $abortError\n" );
			$this->mAbortLoginErrorMsg = $abortError;
			return self::ABORTED;
		}

		wfDebug( __METHOD__ . ": creating account\n" );
		$this->initUser( $user, true );
		return self::SUCCESS;
	}

	function processLogin() {
		global $wgMemc, $wgLang;

		switch ( $this->authenticateUserData() ) {
			case self::SUCCESS:
				# We've verified now, update the real record
				$user = $this->getUser();
				if( (bool)$this->mRemember != (bool)$user->getGlobalPreference( 'rememberpassword' ) ) {
					$user->setGlobalPreference( 'rememberpassword', $this->mRemember ? 1 : 0 );
					$user->saveSettings();
				} else {
					$user->invalidateCache();
				}
				$user->setCookies();
				self::clearLoginToken();

				// Reset the throttle
				$request = $this->getRequest();
				$key = wfSharedMemcKey( 'password-throttle', $request->getIP(), md5( $this->mUsername ) );
				$wgMemc->delete( $key );

				if( $this->hasSessionCookie() || $this->mSkipCookieCheck ) {
					/* Replace the language object to provide user interface in
					 * correct language immediately on this first page load.
					 */
					$code = $request->getVal( 'uselang', $user->getGlobalPreference( 'language' ) );
					$userLang = Language::factory( $code );
					$wgLang = $userLang;
					$this->getContext()->setLanguage( $userLang );
					// Reset SessionID on Successful login (bug 40995)
					$this->renewSessionId();
					$this->successfulLogin();
				} else {
					$this->cookieRedirectCheck( 'login' );
				}
				break;

			case self::NEED_TOKEN:
				$this->mainLoginForm( $this->msg( 'nocookiesforlogin' )->parse() );
				break;
			case self::WRONG_TOKEN:
				$this->mainLoginForm( $this->msg( 'sessionfailure' )->text() );
				break;
			case self::NO_NAME:
			case self::ILLEGAL:
				$this->mainLoginForm( $this->msg( 'noname' )->text() );
				break;
			case self::WRONG_PLUGIN_PASS:
				$this->mainLoginForm( $this->msg( 'wrongpassword' )->text() );
				break;
			case self::NOT_EXISTS:
				if( $this->getUser()->isAllowed( 'createaccount' ) ) {
					$this->mainLoginForm( $this->msg( 'nosuchuser',
						wfEscapeWikiText( $this->mUsername ) )->parse() );
				} else {
					$this->mainLoginForm( $this->msg( 'nosuchusershort',
						wfEscapeWikiText( $this->mUsername ) )->text() );
				}
				break;
			case self::WRONG_PASS:
				$this->mainLoginForm( $this->msg( 'wrongpassword' )->text() );
				break;
			case self::EMPTY_PASS:
				$this->mainLoginForm( $this->msg( 'wrongpasswordempty' )->text() );
				break;
			case self::RESET_PASS:
				$this->resetLoginForm( $this->msg( 'resetpass_announce' )->text() );
				break;
			case self::CREATE_BLOCKED:
				$this->userBlockedMessage( $this->getUser()->mBlock );
				break;
			case self::THROTTLED:
				$this->mainLoginForm( $this->msg( 'login-throttled' )->text() );
				break;
			case self::USER_BLOCKED:
				$this->mainLoginForm( $this->msg( 'login-userblocked',
					$this->mUsername )->escaped() );
				break;
			case self::ABORTED:
				$this->mainLoginForm( $this->msg( $this->mAbortLoginErrorMsg )->text() );
				break;
			default:
				throw new MWException( 'Unhandled case value' );
		}
	}

	function resetLoginForm( $error ) {
		$this->getOutput()->addHTML( Xml::element('p', array( 'class' => 'error' ), $error ) );
		$reset = new SpecialChangePassword();
		$reset->setContext( $this->getContext() );
		$reset->execute( null );
	}

	/**
	 * Run any hooks registered for logins, then HTTP redirect to
	 * $this->mReturnTo (or Main Page if that's undefined).  Formerly we had a
	 * nice message here, but that's really not as useful as just being sent to
	 * wherever you logged in from.  It should be clear that the action was
	 * successful, given the lack of error messages plus the appearance of your
	 * name in the upper right.
	 *
	 * @private
	 */
	function successfulLogin() {
		# Run any hooks; display injected HTML if any, else redirect
		$currentUser = $this->getUser();
		$injected_html = '';
		Hooks::run( 'UserLoginComplete', array( &$currentUser, &$injected_html ) );

		if( $injected_html !== '' ) {
			$this->displaySuccessfulLogin( 'loginsuccess', $injected_html );
		} else {
			$this->executeReturnTo( 'successredirect' );
		}
	}

	/**
	 * Run any hooks registered for logins, then display a message welcoming
	 * the user.
	 *
	 * @private
	 */
	function successfulCreation() {
		# Run any hooks; display injected HTML
		$currentUser = $this->getUser();
		$injected_html = '';
		$welcome_creation_msg = 'welcomecreation';

		Hooks::run( 'UserLoginComplete', array( &$currentUser, &$injected_html ) );

		/**
		 * Let any extensions change what message is shown.
		 * @see https://www.mediawiki.org/wiki/Manual:Hooks/BeforeWelcomeCreation
		 * @since 1.18
		 */
		Hooks::run( 'BeforeWelcomeCreation', array( &$welcome_creation_msg, &$injected_html ) );

		$this->displaySuccessfulLogin( $welcome_creation_msg, $injected_html );
	}

	/**
	 * Display a "login successful" page.
	 * @param $msgname string
	 * @param $injected_html string
	 */
	private function displaySuccessfulLogin( $msgname, $injected_html ) {
		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'loginsuccesstitle' ) );
		if( $msgname ){
			$out->addWikiMsg( $msgname, wfEscapeWikiText( $this->getUser()->getName() ) );
		}

		$out->addHTML( $injected_html );

		$this->executeReturnTo( 'success' );
	}

	/**
	 * Output a message that informs the user that they cannot create an account because
	 * there is a block on them or their IP which prevents account creation.  Note that
	 * User::isBlockedFromCreateAccount(), which gets this block, ignores the 'hardblock'
	 * setting on blocks (bug 13611).
	 * @param $block Block the block causing this error
	 */
	function userBlockedMessage( Block $block ) {
		# Let's be nice about this, it's likely that this feature will be used
		# for blocking large numbers of innocent people, e.g. range blocks on
		# schools. Don't blame it on the user. There's a small chance that it
		# really is the user's fault, i.e. the username is blocked and they
		# haven't bothered to log out before trying to create an account to
		# evade it, but we'll leave that to their guilty conscience to figure
		# out.

		$out = $this->getOutput();
		$out->setPageTitle( $this->msg( 'cantcreateaccounttitle' ) );

		$block_reason = $block->mReason;
		if ( strval( $block_reason ) === '' ) {
			$block_reason = $this->msg( 'blockednoreason' )->text();
		}

		$out->addWikiMsg(
			'cantcreateaccount-text',
			$block->getTarget(),
			$block_reason,
			$block->getByName()
		);

		$this->executeReturnTo( 'error' );
	}

	/**
	 * Add a "return to" link or redirect to it.
	 *
	 * @param $type string, one of the following:
	 *    - error: display a return to link ignoring $wgRedirectOnLogin
	 *    - success: display a return to link using $wgRedirectOnLogin if needed
	 *    - successredirect: send an HTTP redirect using $wgRedirectOnLogin if needed
	 */
	private function executeReturnTo( $type ) {
		global $wgRedirectOnLogin, $wgSecureLogin;

		if ( $type != 'error' && $wgRedirectOnLogin !== null ) {
			$returnTo = $wgRedirectOnLogin;
			$returnToQuery = array();
		} else {
			$returnTo = $this->mReturnTo;
			$returnToQuery = wfCgiToArray( $this->mReturnToQuery );
		}

		$returnToTitle = Title::newFromText( $returnTo );
		if ( !$returnToTitle ) {
			$returnToTitle = Title::newMainPage();
		}

		if ( $type == 'successredirect' ) {
			$redirectUrl = $returnToTitle->getFullURL( $returnToQuery );
			if( $wgSecureLogin && !$this->mStickHTTPS ) {
				$redirectUrl = preg_replace( '/^https:/', 'http:', $redirectUrl );
			}
			$this->getOutput()->redirect( $redirectUrl );
		} else {
			$this->getOutput()->addReturnTo( $returnToTitle, $returnToQuery );
		}
	}

	/**
	 * @private
	 * Wikia change - add new param ($errParam)
	 */
	function mainLoginForm( $msg, $msgtype = 'error', $errParam='' ) {
		global $wgEnableEmail, $wgEnableUserEmail;
		global $wgHiddenPrefs, $wgLoginLanguageSelector;
		global $wgAuth, $wgEmailConfirmToEdit, $wgCookieExpiration;
		global $wgSecureLogin, $wgPasswordResetRoutes;
		global $wgOut;

		$titleObj = $this->getTitle();
		$user = $this->getUser();

		if ( $this->mType == 'signup' ) {
			// Block signup here if in readonly. Keeps user from
			// going through the process (filling out data, etc)
			// and being informed later.
			$permErrors = $titleObj->getUserPermissionsErrors( 'createaccount', $user, true );
			if ( count( $permErrors ) ) {
				throw new PermissionsError( 'createaccount', $permErrors );
			} elseif ( $user->isBlockedFromCreateAccount() ) {
				$this->userBlockedMessage( $user->isBlockedFromCreateAccount() );
				return;
			} elseif ( wfReadOnly() ) {
				throw new ReadOnlyError;
			}
		}

		if ( $this->mUsername == '' ) {
			if ( $user->isLoggedIn() ) {
				$this->mUsername = $user->getName();
			} else {
				$this->mUsername = $this->getRequest()->getCookie( 'UserName' );
			}
		}

		if ( $this->mType == 'signup' ) {
			$template = new UsercreateTemplate();
			$q = 'action=submitlogin&type=signup';
			$q2 = 'action=submitlogin&type=login';
			$linkq = 'type=login';
			$linkmsg = 'gotaccount';

			// ADi: marketing opt-in/out checkbox added
			$template->addInputItem( 'wpMarketingOptIn', 1, 'checkbox', 'tog-marketingallowed');
		} else {
			$template = new UserloginTemplate();
			$q = 'action=submitlogin&type=login';
			$q2 = 'action=submitlogin&type=login';
			$linkq = 'type=signup';
			$linkmsg = 'nologin';
		}

		if ( $this->mReturnTo !== '' ) {
			$returnto = '&returnto=' . wfUrlencode( $this->mReturnTo );
			if ( $this->mReturnToQuery !== '' ) {
				$returnto .= '&returntoquery=' .
					wfUrlencode( $this->mReturnToQuery );
			}
			$q .= $returnto;
			$linkq .= $returnto;
			$q2 .= $returnto;
			//$linkq2 .= $returnto; // not used anywhere else... FIXME: REMOVE
		}

		# Don't show a "create account" link if the user can't
		if( $this->showCreateOrLoginLink( $user ) ) {
			# Pass any language selection on to the mode switch link
			if( $wgLoginLanguageSelector && $this->mLanguage ) {
				$linkq .= '&uselang=' . $this->mLanguage;
			}
			$link = Html::element( 'a', array( 'href' => $titleObj->getLocalURL( $linkq ) ),
				$this->msg( $linkmsg . 'link' )->text() ); # Calling either 'gotaccountlink' or 'nologinlink'

			$template->set( 'link', $this->msg( $linkmsg )->rawParams( $link )->parse() );
		} else {
			$template->set( 'link', '' );
		}

		$resetLink = $this->mType == 'signup'
			? null
			: is_array( $wgPasswordResetRoutes ) && in_array( true, array_values( $wgPasswordResetRoutes ) );

		$template->set( 'header', '' );
		$template->set( 'name', $this->mUsername );
		$template->set( 'password', $this->mPassword );
		$template->set( 'retype', $this->mRetype );
		$template->set( 'email', $this->mEmail );
		$template->set( 'realname', $this->mRealName );
		$template->set( 'domain', $this->mDomain );
		$template->set( 'reason', $this->mReason );
		$template->set( 'action', $titleObj->getLocalURL( $q ) );
		$template->set( 'actioncreate', $titleObj->getLocalUrl( $q ) );
		$template->set( 'actionlogin', $titleObj->getLocalUrl( $q2 ) );
		$template->set( 'message', $msg );
		$template->set( 'messagetype', $msgtype );
		$template->set( 'createemail', $wgEnableEmail && $user->isLoggedIn() );
		$template->set( 'userealname', !in_array( 'realname', $wgHiddenPrefs ) );
		$template->set( 'useemail', $wgEnableEmail );
		$template->set( 'emailrequired', $wgEmailConfirmToEdit );
		$template->set( 'emailothers', $wgEnableUserEmail );
		$template->set( 'canreset', $wgAuth->allowPasswordChange() );
		$template->set( 'resetlink', $resetLink );
		$template->set( 'canremember', ( $wgCookieExpiration > 0 ) );
		$template->set( 'usereason', $user->isLoggedIn() );
		$template->set( 'remember', $user->getGlobalPreference( 'rememberpassword' ) || $this->mRemember );
		$template->set( 'cansecurelogin', ( $wgSecureLogin === true ) );
		$template->set( 'stickHTTPS', $this->mStickHTTPS );

		$template->set( 'birthyear', $this->wpBirthYear );
		$template->set( 'birthmonth', $this->wpBirthMonth );
		$template->set( 'birthday', $this->wpBirthDay );

		if ( $this->mType == 'signup' ) {
			if ( !self::getCreateaccountToken() ) {
				self::setCreateaccountToken();
			}
			$template->set( 'token', self::getCreateaccountToken() );
		} else {
			if ( !self::getLoginToken() ) {
				self::setLoginToken();
			}
			$template->set( 'token', self::getLoginToken() );
		}

		# Prepare language selection links as needed
		if( $wgLoginLanguageSelector ) {
			$template->set( 'languages', $this->makeLanguageSelector() );
			if( $this->mLanguage ) {
				$template->set( 'uselang', $this->mLanguage );
			}
		}

		// Use loginend-https for HTTPS requests if it's not blank, loginend otherwise
		// Ditto for signupend
		$usingHTTPS = WebRequest::detectProtocol() == 'https';
		$loginendHTTPS = $this->msg( 'loginend-https' );
		$signupendHTTPS = $this->msg( 'signupend-https' );
		if ( $usingHTTPS && !$loginendHTTPS->isBlank() ) {
			$template->set( 'loginend', $loginendHTTPS->parse() );
		} else {
			$template->set( 'loginend', $this->msg( 'loginend' )->parse() );
		}
		if ( $usingHTTPS && !$signupendHTTPS->isBlank() ) {
			$template->set( 'signupend', $signupendHTTPS->parse() );
		} else {
			$template->set( 'signupend', $this->msg( 'signupend' )->parse() );
		}

		// Give authentication and captcha plugins a chance to modify the form
		$wgAuth->modifyUITemplate( $template, $this->mType );
		if ( $this->mType == 'signup' ) {
			Hooks::run( 'UserCreateForm', array( &$template ) );
		} else {
			Hooks::run( 'UserLoginForm', array( &$template ) );
		}

		$out = $this->getOutput();
		$out->disallowUserJs(); // just in case...
		$out->addTemplate( $template );
	}

	/**
	 * @private
	 *
	 * @param $user User
	 *
	 * @return Boolean
	 */
	function showCreateOrLoginLink( &$user ) {
		if( $this->mType == 'signup' ) {
			return true;
		} elseif( $user->isAllowed( 'createaccount' ) ) {
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Check if a session cookie is present.
	 *
	 * This will not pick up a cookie set during _this_ request, but is meant
	 * to ensure that the client is returning the cookie which was set on a
	 * previous pass through the system.
	 *
	 * @private
	 */
	function hasSessionCookie() {
		global $wgDisableCookieCheck;
		return $wgDisableCookieCheck ? true : $this->getRequest()->checkSessionCookie();
	}

	/**
	 * Get the login token from the current session
	 */
	public static function getLoginToken() {
		global $wgRequest;
		return $wgRequest->getSessionData( 'wsLoginToken' );
	}

	/**
	 * Randomly generate a new login token and attach it to the current session
	 */
	public static function setLoginToken() {
		global $wgRequest;
		// Generate a token directly instead of using $user->editToken()
		// because the latter reuses $_SESSION['wsEditToken']
		$wgRequest->setSessionData( 'wsLoginToken', MWCryptRand::generateHex( 32 ) );
	}

	/**
	 * Remove any login token attached to the current session
	 */
	public static function clearLoginToken() {
		global $wgRequest;
		$wgRequest->setSessionData( 'wsLoginToken', null );
	}

	/**
	 * Get the createaccount token from the current session
	 */
	public static function getCreateaccountToken() {
		global $wgRequest;
		return $wgRequest->getSessionData( 'wsCreateaccountToken' );
	}

	/**
	 * Randomly generate a new createaccount token and attach it to the current session
	 */
	public static function setCreateaccountToken() {
		global $wgRequest;
		$wgRequest->setSessionData( 'wsCreateaccountToken', MWCryptRand::generateHex( 32 ) );
	}

	/**
	 * Remove any createaccount token attached to the current session
	 */
	public static function clearCreateaccountToken() {
		global $wgRequest;
		$wgRequest->setSessionData( 'wsCreateaccountToken', null );
	}

 	/**
	 * Renew the user's session id, using strong entropy
	 */
	private function renewSessionId() {
		global $wgSecureLogin, $wgCookieSecure;
		if ( $wgSecureLogin && !$this->mStickHTTPS ) {
			$wgCookieSecure = false;
		}

		// Always make sure edit token is regenerated. (T122056)
		$this->getRequest()->setSessionData( 'wsEditToken', null );
		wfResetSessionID();
	}

	/**
	 * @private
	 */
	function cookieRedirectCheck( $type ) {
		$titleObj = SpecialPage::getTitleFor( 'Userlogin' );
		$query = array( 'wpCookieCheck' => $type );
		if ( $this->mReturnTo !== '' ) {
			$query['returnto'] = $this->mReturnTo;
			$query['returntoquery'] = $this->mReturnToQuery;
		}
		$check = $titleObj->getFullURL( $query );

		$this->getOutput()->redirect( $check );
	}

	/**
	 * @private
	 */
	function onCookieRedirectCheck( $type ) {
		if ( !$this->hasSessionCookie() ) {
			if ( $type == 'new' ) {
				$this->mainLoginForm( $this->msg( 'nocookiesnew' )->parse() );
			} elseif ( $type == 'login' ) {
				$this->mainLoginForm( $this->msg( 'nocookieslogin' )->parse() );
			} else {
				# shouldn't happen
				$this->mainLoginForm( $this->msg( 'error' )->text() );
			}
		} else {
			$this->successfulLogin();
		}
	}

	/**
	 * @private
	 */
	function throttleHit( $limit ) {
		$this->mainLoginForm( $this->msg( 'acct_creation_throttle_hit' )->numParams( $limit )->parse() );
	}

	/**
	 * Produce a bar of links which allow the user to select another language
	 * during login/registration but retain "returnto"
	 *
	 * @return string
	 */
	function makeLanguageSelector() {
		$msg = $this->msg( 'loginlanguagelinks' )->inContentLanguage();
		if( !$msg->isBlank() ) {
			$langs = explode( "\n", $msg->text() );
			$links = array();
			foreach( $langs as $lang ) {
				$lang = trim( $lang, '* ' );
				$parts = explode( '|', $lang );
				if ( count( $parts ) >= 2 ) {
					$links[] = $this->makeLanguageSelectorLink( $parts[0], trim( $parts[1] ) );
				}
			}
			return count( $links ) > 0 ? $this->msg( 'loginlanguagelabel' )->rawParams(
				$this->getLanguage()->pipeList( $links ) )->escaped() : '';
		} else {
			return '';
		}
	}

	/**
	 * Create a language selector link for a particular language
	 * Links back to this page preserving type and returnto
	 *
	 * @param $text Link text
	 * @param $lang Language code
	 */
	function makeLanguageSelectorLink( $text, $lang ) {
		if( $this->getLanguage()->getCode() == $lang ) {
			// no link for currently used language
			return htmlspecialchars( $text );
		}
		$query = array( 'uselang' => $lang );
		if( $this->mType == 'signup' ) {
			$query['type'] = 'signup';
		}
		if( $this->mReturnTo !== '' ) {
			$query['returnto'] = $this->mReturnTo;
			$query['returntoquery'] = $this->mReturnToQuery;
		}

		$attr = array();
		$targetLanguage = Language::factory( $lang );
		$attr['lang'] = $attr['hreflang'] = $targetLanguage->getHtmlCode();

		return Linker::linkKnown(
			$this->getTitle(),
			htmlspecialchars( $text ),
			$attr,
			$query
		);
	}
}
