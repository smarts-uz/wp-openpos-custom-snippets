<?php
if(!class_exists('OP_REST_API_Auth'))
{
    class OP_REST_API_Auth extends WC_REST_CRUD_Controller{
       
        protected $namespace = 'op/v1';
        
       
        public function register_routes() {
            
                // staff  route         
                register_rest_route( $this->namespace, '/auth/login', array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array($this,'login'),
                    'permission_callback' => '__return_true',
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/login-register/(?P<id>\d+)', array(
                    'methods' => 'POST',
                    'callback' => array($this,'login_register'),
                    'permission_callback' => '__return_true',
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/login-session/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => array($this,'login_session'),
                    'permission_callback' => '__return_true',
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/logout/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => array($this,'logout'),
                    'permission_callback' => array($this,'permission_callback'),
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/logoff/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => array($this,'logoff'),
                    'permission_callback' => array($this,'permission_callback'),
                    ) 
                );
                register_rest_route( $this->namespace, '/auth/logon/(?P<id>\d+)', array(
                    'methods' => 'GET',
                    'callback' => array($this,'logon'),
                    'permission_callback' => array($this,'permission_callback'),
                    ) 
                );
                

           
        }
        
        public function test_api(WP_REST_Request $request){
            $param = $request->get_param( 'some_param' );
            return new WP_Error( 'empty_category', 'There are no posts to display', array('status' => 404) );
        }
        public function login(WP_REST_Request $request){
            try{
                $username = $request->get_param( 'username' );
                $password = $request->get_param( 'password' );
                $login_mode = $request->get_param( 'login_mode' );
                $location = $request->get_param( 'location' );
                $lang = $request->get_param('lang');
                $time_stamp = $request->get_param('time_stamp');

                $result = array(
                    'status' => 1,
                    'data' => array(),
                    'message' => ''
                );//$this->front->login($username,$password);
                $response = rest_ensure_response( $result );
                $response->set_status( 200 );
                return $response;
            }catch(Exception $e){
                return new WP_Error( 'login_error', $e->getMessage() , array('status' => 404) );
            }
        }
        public function login_register(WP_REST_Request $request){
            $session = $request->get_param( 'session' );
            $client_time_offset = $request->get_param( 'client_time_offset' );

        }
        public function logon(WP_REST_Request $request){
           
        }
        public function logoff(WP_REST_Request $request){
           
        }
        public function login_session(WP_REST_Request $request){
           
        }
        
    }
}