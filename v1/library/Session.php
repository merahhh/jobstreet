<?php

namespace utility;

/**
 * Class Session
 * @package utility
 */
class Session
{

    /**
     * @var Session
     */
    private $session;


    /**
     * Constructor method
     *
     * @param string $sessionLimiter
     * @param int $sessionExpired
     *
     */
    public function __construct( $sessionLimiter = 'nocache', $sessionExpired = 30 )
    {
        $this->setSessionLimiter( $sessionLimiter );
        $this->setSessionExpired( $sessionExpired );
        $this->startSession();
        $this->initFlashData();
    }


    /**
     * Free all session variables
     *
     * @return void
     */
    public function unsetSession()
    {
        session_unset();
    }


    /**
     * Destroys A Session And All Data Registered To A Session
     *
     * @return void
     */
    public function destroySession()
    {
        session_destroy();
    }


    /**
     * Get The Current Session Id
     *
     * @return void
     */
    public function getSessionId()
    {
        return session_id();
    }


    /**
     * Regenerate Session Id
     *
     * @return void
     */
    public function regenerateSessionId()
    {
        session_regenerate_id();
    }


    /**
     * Setting session data
     * Produce $_SESSION[$name]=$value
     *
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function set( $name, $value )
    {
        $this->session[$name] = $value;
    }


    /**
     * Set session so that only exist on next request
     *
     * @param string $key
     * @param mixed $val
     * @return void
     */
    public function flash( $key, $val )
    {
        $_SESSION['flash'][$key] = 'new';
        $this->set( $key, $val );
    }


    /**
     * Set session so it keep exist on only for next request
     * If this method is call without parameter,all flash session var will be keep for next request
     *
     * @param mixed $keys
     * @return void
     */
    public function keepFlash( $keys = null )
    {
        $keys = ( $keys === null ) ? array_keys( $_SESSION['flash'] ) : func_get_args();

        foreach( $keys as $key ){
            if( isset( $_SESSION['flash'][$key] ) ){
                $_SESSION['flash'][$key] = 'new';
            }
        }
    }


    /**
     * Clear flash session
     * If this method is call without parameter,all flash session will be deleted
     *
     * @param string $needles key in flash session to be search
     */
    public function clearFlash( $needles = null )
    {
        $needles = ( $needles === null ) ? null : func_get_args();
        $haystacks = array_keys( $_SESSION['flash'] );

        if( !is_null( $needles ) ){
            foreach( $haystacks as $haystack ){
                foreach( $_SESSION[$haystack] as $ses_key => $ses_val ){
                    foreach( $needles as $needle ){
                        if( $needle == $ses_key ){
                            unset( $_SESSION[$haystack][$ses_key] );
                        }
                    }
                }
            }
        }
        else{
            foreach( $haystacks as $haystack ){
                unset( $_SESSION['flash'][$haystack], $_SESSION[$haystack] );
            }
        }
    }


    /**
     * Delete Session Variable
     *
     * @param string $name
     * @return void
     */
    public function delete( $name )
    {
        unset( $this->session[$name] );
    }


    /**
     * Get Session Variable
     *
     * @param string $name
     * @return mixed
     */
    public function get( $name )
    {
        if( isset( $this->session[$name] ) ){
            return $this->session[$name];
        }
    }


    /**
     * Check For Existence Of Session Variable
     *
     * @param string $name
     * @return bool
     */
    public function check( $name )
    {
        return isset( $this->session[$name] ) ? true : false;
    }


    /**
     * Get All Session List
     *
     * @return array
     */
    public function getSessionList()
    {
        return array_keys( $this->session );
    }


    /**
     * Start the session
     *
     * @access private
     */
    private function startSession()
    {
        session_start();
        $this->session = & $_SESSION;
    }


    /**
     * Set how long cached page in client cache should be store
     * Does not affect session lifetime
     * Use in conjunction with session_cache_limiter != 'nocache'
     *
     * @param mixed $delay
     * @return void
     */
    private function setSessionExpired( $delay )
    {
        session_cache_expire( $delay );
    }


    /**
     * Set session cache limiter whether to permit client to cache page content or not
     * Possible value public, private, private_no_expire, nocache
     *
     * @param mixed $limit
     * @return void
     */
    private function setSessionLimiter( $limit )
    {
        session_cache_limiter( $limit );
    }


    /**
     * Init session flash data
     *
     * @return void
     */
    private function initFlashData()
    {
        if( !isset( $_SESSION['flash'] ) ){
            $_SESSION['flash'] = array();
        }
        $this->expireFlash();
    }


    /**
     * Expire flash session data
     *
     * @return void
     */
    private function expireFlash()
    {
        static $run;
        if( $run === TRUE ) return;

        if( !empty( $_SESSION['flash'] ) ){
            foreach( $_SESSION['flash'] as $key => $state ){
                if( $state === 'old' ){
                    unset( $_SESSION['flash'][$key], $_SESSION[$key] );
                }
                else{
                    $_SESSION['flash'][$key] = 'old';
                }
            }
        }
        $run = TRUE;
    }


    /**
     * Redirect User
     *
     * @param string $path
     * @return void
     */
    public function redirect( $path )
    {
        header( 'Location: ' . $path );
        exit();
    }


}
