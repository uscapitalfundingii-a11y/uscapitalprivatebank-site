<?php

defined('BASEPATH') or exit('No direct script access allowed');
set_time_limit(0);



class Zoom_meetings extends AdminController
{
	protected $csrf_exclude_uris = array(
        'zoom_meetings', // Add your URL here
    );
	
    public function __construct()
    {
        parent::__construct();
        $this->load->model('zoom_model');
		$this->load->helper('zoom_meetings_helper');
		

		
    }
	
	public function authenticate(){
		
        $settings=$this->zoom_model->get_api_settings();
        $ClientId=$settings[0]['zoom_email'];
        $ClientSecret=$settings[0]['api_secret'];
        $RedirectUri=$settings[0]['api_key'];
		$zoomClientId = $ClientId;
		$zoomRedirectUri = $RedirectUri;
		
		
		$data['settings']=$this->zoom_model->get_api_settings();
		$accessToken=$_SESSION['zoom_code'];	
		if($accessToken ==''){
		$accessToken =  $email=$settings[0]['access_token'];
		}
		
		
		
					// Define the API endpoint URL
			$apiUrl = 'https://api.zoom.us/v2/users/me';

			// Initialize cURL session
			$ch = curl_init();

			// Set cURL options
			curl_setopt($ch, CURLOPT_URL, $apiUrl);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  
			  // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

			// Set the HTTP headers for authentication and content type
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $accessToken,
				'Content-Type: application/json',
			));

			// Execute the cURL request and get the response
			$response = curl_exec($ch);

			// Check for cURL errors
			if (curl_errno($ch)) {
				echo 'Error: ' . curl_error($ch);
			}

			// Close cURL session
			curl_close($ch);

			// Output the API response
			$responseData = json_decode($response, true);
			$check=$responseData['id'];
			if($check==''){
				
				$data['title']= _l('zoom_api_settings');
               redirect(admin_url('zoom_meetings/api_meeting'));	 
			}		
	
	}	
	
   // Handles OAuth authorization
   public function authorize()
   {
	   $settings = $this->zoom_model->get_api_settings();
	   $clientId = $settings[0]['zoom_email'];
	   $redirectUri = $settings[0]['api_key'];

	   $authorizationUrl = "https://zoom.us/oauth/authorize?response_type=code&client_id={$clientId}&redirect_uri={$redirectUri}";
	   redirect($authorizationUrl);
   }
	
   public function authorized()
   {
	   $settings = $this->zoom_model->get_api_settings();
	   if (empty($settings)) {
		   log_message('error', 'No Zoom API settings found.');
		   show_error('Zoom API settings not found.');
		   return;
	   }
   
	   $authorizationCode = $this->input->get('code');
	   if (empty($authorizationCode)) {
		   log_message('error', 'Authorization code is missing.');
		   show_error('Authorization code is required.');
		   return;
	   }
   
	   $clientId = $settings[0]['zoom_email'];
	   $clientSecret = $settings[0]['api_secret'];
	   $redirectUri = $settings[0]['api_key'];
   
	   // Prepare token exchange request
	   $url = 'https://zoom.us/oauth/token';
	   $data = [
		   'grant_type' => 'authorization_code',
		   'code' => $authorizationCode,
		   'redirect_uri' => $redirectUri,
	   ];
   
	   $headers = [
		   'Authorization: Basic ' . base64_encode("$clientId:$clientSecret"),
	   ];
   
	   $ch = curl_init($url);
	   curl_setopt($ch, CURLOPT_POST, 1);
	   curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
	   curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	   curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	   curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  
   
	   $response = curl_exec($ch);
	   $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	   $error = curl_error($ch);
	   curl_close($ch);
   
	   if ($httpCode !== 200 || $response === false) {
		   log_message('error', "Token Exchange Failed. HTTP Code: $httpCode, Error: $error, Response: $response");
		   show_error('Failed to retrieve tokens. Please try again.');
		   return;
	   }
   
	   $responseData = json_decode($response, true);
	   if (isset($responseData['access_token'], $responseData['refresh_token'])) {
		   $this->zoom_model->update_meeting_settings($settings[0]['id'], [
			   'access_token' => $responseData['access_token'],
			   'refresh_token' => $responseData['refresh_token'],
		   ]);
		   redirect(admin_url('zoom_meetings/api_meeting'));
	   } else {
		   log_message('error', 'Invalid token response: ' . $response);
		   show_error('Failed to retrieve tokens. Please try again.');
	   }
   }
   
		
		

   
    public function index()
    {
		
       $this->authenticate();
		
        $settings=$this->zoom_model->get_api_settings();
        $apiKey=$settings[0]['api_key'];
        $apiSecret=$settings[0]['api_secret'];
        $email=$settings[0]['zoom_email'];
        
		$accessToken=$_SESSION['zoom_code'];
		
		if($accessToken==''){
			
		 	$accessToken =  $email=$settings[0]['access_token'];
			
		}	

		$url = 'https://api.zoom.us/v2/users/me/meetings';

		$headers = [
			'Authorization: Bearer ' . $accessToken,
			'Content-Type: application/json',
		];

				$ch = curl_init($url);

		if ($ch === false) {
			die('cURL initialization failed.');
		}

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);    
		$response = curl_exec($ch);
		$error = curl_error($ch);

		if ($error) {
			// Handle cURL error
			echo 'cURL Error: ' . $error;
		} else {
			// Check HTTP status code
			$httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);

			if ($httpStatus === 200) {
				// Successful response
				$meetingsData = json_decode($response, true);
				// Process the data
			} else {
				// Handle non-200 status codes
				//echo 'Error: HTTP Status Code ' . $httpStatus . '<br>';
				//echo 'Response: ' . $response;
			}
		}

		curl_close($ch);

		
		
		$i=0;
		
		if(isset($meetingsData['meetings'][0])){
			foreach($meetingsData as $meet){
				
						$id=$meetingsData['meetings'][$i]['id'];
						$exist=$this->zoom_model->check_meeting_exist($id);
						
						if(!isset($exist)){
								
								$data2 = array(
									'subject'=>$meetingsData['meetings'][$i]['topic'],
									'start_time'=>$meetingsData['meetings'][$i]['start_time'],
									'duration'=>$meetingsData['meetings'][$i]['duration'],
									'timezone'=>$meetingsData['meetings'][$i]['timezone'],
									'agenda'=>$meetingsData['meetings'][$i]['agenda'],
									'join_url'=>$meetingsData['meetings'][$i]['join_url'],
									'meeting_id'=>$meetingsData['meetings'][$i]['id']	
								);
							$this->zoom_model->insert_meeting($data2);	
							$i++;  						
							 
						}    
								
				
			}
		}
		if ($this->input->is_ajax_request()) {
                $this->app->get_table_data(module_views_path('zoom_meetings', 'zoom_list'));
            }
	    
		$data['title']= _l('zoom_list');	
        $this->load->view('zoom', $data);	 
       
    }

    
	public function create_meeting()
    {
		$this->authenticate();
        $data = [
			'staff_members' => $this->staff_model->get('', ['active' => 1]),
			'rel_type' => 'lead',
			'rel_contact_type' => 'contact',
			'rel_contact_id' => '',
			'rel_id' => '',
			
		];  
		
		//echo '<pre>';
		//print_r($data);
        $data['title']= _l('zoom_create_meeting');
        $this->load->view('zoom_create_meeting', $data);	 

    }
    
	function delete_meeting(){


        $settings=$this->zoom_model->get_api_settings();
        $apiKey=$settings[0]['zoom_email'];
        $apiSecret=$settings[0]['api_secret'];
        $email=$settings[0]['api_key'];
		$accessToken=$_SESSION['zoom_code'];
		
		if($accessToken==''){
			
		 	$accessToken =  $email=$settings[0]['access_token'];
			
		}	
		$meeting_id=$this->uri->segment(4);

        
        
	
				

				// Zoom API endpoint for deleting a meeting
				$zoom_api_url = "https://api.zoom.us/v2/meetings/$meeting_id";

				// Create a cURL handle
				$ch = curl_init();

				// Set cURL options
				curl_setopt($ch, CURLOPT_URL, $zoom_api_url);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);   
				curl_setopt($ch, CURLOPT_HTTPHEADER, [
					"Authorization: Bearer $accessToken",
					"Content-Type: application/json",
				]);

				// Execute the cURL request
				$response = curl_exec($ch);

				// Check for errors
				if ($response === false) {
					echo "cURL Error: " . curl_error($ch);
				} else {
					// Decode the JSON response
					$data = json_decode($response, true);

					// Check if the meeting was successfully deleted
					if ($data && isset($data['status']) && $data['status'] === 'deleted') {
						echo "Meeting $meeting_id has been deleted.";
						//set_alert('success', _l('zoom_meeting_deleted', _l('zoom')));
					} else {
						 
						 // set_alert('error', _l('zoom_meeting_deleted', _l('zoom')));
						echo "Meeting $meeting_id has been deleted.";
					}
				}

				// Close the cURL handle
				curl_close($ch);
		
		
			$this->zoom_model->delete_meeting($meeting_id);			
           
           redirect(admin_url('zoom_meetings'));
        
		
	}		
	
    public function submit_meeting()
    {
        $settings = $this->zoom_model->get_api_settings();
        if (!$settings) {
            show_error('Zoom API settings not found.');
        }

		
        $refreshToken = $settings[0]['refresh_token'];
        $ClientId = $settings[0]['zoom_email'];
        $ClientSecret = $settings[0]['api_secret'];

        // Validate or Refresh Token
        $accessToken = ensureZoomSession($refreshToken, $ClientId, $ClientSecret);

        //$accessToken = $this->session->userdata('zoom_code');
        if (empty($accessToken)) {
            $accessToken = $settings->access_token;
        }

        $subject = $this->input->post('subject', false);
        $start_time = $this->input->post('start_time', false);
        $timezone = $this->input->post('timezone', false);
        $agenda = $this->input->post('agenda', false);
        $duration = $this->input->post('duration', false);
        $join_before_host = $this->input->post('join_before_host', false);
        $host_video = $this->input->post('host_video', false);
        $participant_video = $this->input->post('participant_video', false);
        $mute_upon_entry = $this->input->post('mute_upon_entry', false);
        $waiting_room = $this->input->post('waiting_room', false);
        $clientid = $this->input->post('clientid', false);
        $staff = $this->input->post('staff');

        $meeting_data = [
            "topic" => $subject,
            "start_time" => gmdate("Y-m-d\TH:i:s", strtotime($start_time)),
            "timezone" => $timezone,
            "duration" => $duration,
            "agenda" => $agenda,
            "settings" => [
                'join_before_host' => isset($join_before_host) ? true : false,
                'host_video' => isset($host_video) ? true : false,
                'participant_video' => isset($participant_video) ? true : false,
                'mute_upon_entry' => isset($mute_upon_entry) ? true : false,
                'waiting_room' => isset($waiting_room) ? true : false,
            ],
        ];

        $zoom_api_endpoint = "https://api.zoom.us/v2/users/me/meetings";
        $json_payload = json_encode($meeting_data);

        $ch = curl_init($zoom_api_endpoint);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer $accessToken",
            "Content-Type: application/json",
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
           // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $responseData = json_decode($response, true);

        if (isset($responseData['id'])) {
            $data = [
                'subject' => $responseData['topic'],
                'start_time' => $responseData['start_time'],
                'duration' => $responseData['duration'],
                'timezone' => $responseData['timezone'],
                'agenda' => $responseData['agenda'],
                'join_url' => $responseData['join_url'],
                'meeting_id' => $responseData['id'],
            ];
            $this->zoom_model->insert_meeting($data);

            set_alert('success', _l('zoom_meeting_created', _l('zoom')));
            redirect(admin_url('zoom_meetings'));
        } else {
            show_error('Failed to create Zoom meeting.');
        }
    }


    // Handles Zoom API meeting settings
	public function api_meeting()
{
    // Fetch settings using the model
    $settings = $this->zoom_model->get_api_settings();

	$clientId = $settings[0]['zoom_email'];
	$clientSecret = $settings[0]['api_secret'];
	$redirectUri = $settings[0]['api_key'];


    // Validate settings
    if (
        empty($settings) ||
        $settings[0]['api_key'] === 'Zoom Redirect URI' || // Dummy data
        $settings[0]['api_secret'] === 'Zoom Client Secret' || // Dummy data
        $settings[0]['zoom_email'] === 'Zoom Client ID' // Dummy data
    ) {
        $this->session->set_flashdata('error', 'Please configure Zoom API settings before proceeding.');
        redirect(admin_url('zoom_meetings/configure'));
        return;
    }

    // Retrieve tokens
    $accessToken = $settings[0]['access_token'];
    $refreshToken = $settings[0]['refresh_token'];

    // Validate or refresh the access token
    if (empty($accessToken) || !validateAccessToken($accessToken)) {
        if (!empty($refreshToken)) {
            $newTokens = refreshZoomToken($refreshToken, $settings[0]['zoom_email'], $settings[0]['api_secret']);
            if ($newTokens && isset($newTokens['access_token'], $newTokens['refresh_token'])) {
                saveZoomTokens($this->zoom_model, $settings[0]['id'], $newTokens['access_token'], $newTokens['refresh_token']);
                $accessToken = $newTokens['access_token'];
            } else {
                log_message('error', 'Failed to refresh Zoom token.');
                $this->session->set_flashdata('error', 'Failed to refresh Zoom token. Please reauthorize the application.');
                redirect(admin_url('zoom_meetings/authorize'));
                return;
            }
        } else {
            $this->session->set_flashdata('error', 'Refresh token is missing. Please reauthorize the application.');
            redirect(admin_url('zoom_meetings/authorize'));
            return;
        }
    }

    // Save the valid access token to the session
    $_SESSION['zoom_code'] = $accessToken;

    // Pass settings to the view
    $data['settings'] = $settings;
    $data['title'] = _l('zoom_api_settings');
    $this->load->view('zoom_api_settings', $data);
}

	
	
	
	public function configure()
{
    $settings = $this->zoom_model->get_api_settings();
    $data['settings'] = $settings;
    $data['title'] = _l('zoom_configuration');

    $this->load->view('zoom_configure', $data);
}

public function save_configuration()
{
    $id = $this->input->post('id'); // Retrieve the ID from the form
    $data = [
        'zoom_email' => $this->input->post('zoom_email'),
        'api_key' => $this->input->post('api_key'),
        'api_secret' => $this->input->post('api_secret'),
    ];

    $result = $this->zoom_model->update_meeting_settings($id, $data);
   
	
    if ($result) {
        // Set flash message for success
        $this->session->set_flashdata('success', 'Zoom API settings updated successfully.');
		
        redirect(admin_url('zoom_meetings/api_meeting')); // Redirect to main page
    } else {
        // Set flash message for failure
        $this->session->set_flashdata('error', 'Failed to update Zoom API settings. Please try again.');
        redirect(admin_url('zoom_meetings/api_meeting')); // Redirect to main page
    }
}


	
	
	

	
	
	
    
	 
  public function api_meeting_submit() {
    $id = $this->input->post('id'); // Retrieve the ID from the submitted form
    $data = [
        'zoom_email' => $this->input->post('zoom_email'),
        'api_key' => $this->input->post('api_key'),
        'api_secret' => $this->input->post('api_secret'),
    ];

    $result = $this->zoom_model->update_meeting_settings($id, $data);
    
    if ($result) {
        set_alert('success', 'Zoom settings updated successfully.');
    } else {
        set_alert('danger', 'Failed to update Zoom settings.');
    }

    redirect(admin_url('zoom_meetings/api_meeting'));
}


    public function add_registrant(){

        $data['title']= _l('zoom_add_registrant');
        $this->load->view('zoom_add_registrant', $data);	
          
    }


    public function submit_registrant(){

	   $this->authenticate();       
	   $settings=$this->zoom_model->get_api_settings();
	   $refreshToken = $settings[0]['refresh_token'];
        $apiKey=$settings[0]['api_key'];
        $apiSecret=$settings[0]['api_secret'];
        $email=$settings[0]['zoom_email'];
        $registrant_email   = html_purify($this->input->post('zoom_registrant_email', false));
        $registrant_fname   = html_purify($this->input->post('zoom_registrant_fname', false));
        $registrant_lname = html_purify($this->input->post('zoom_registrant_lname', false));
        $zoom_registrant_meetid = html_purify($this->input->post('zoom_registrant_meetid', false));
        //$accessToken=$_SESSION['zoom_code'];
        // Validate or Refresh Token
        $accessToken = ensureZoomSession($refreshToken, $ClientId, $ClientSecret);
        

        $registrant_data = array(
            "email"  => $registrant_email,
            "first_name"   => $registrant_fname,
            "last_name"=> $registrant_lname,
          );
        
		
		$meeting_id = $zoom_registrant_meetid ;
		

		$url = "https://api.zoom.us/v2/meetings/{$meeting_id}/registrants";
		$headers = array(
			"Authorization: Bearer {$accessToken}",
			"Content-Type: application/json"
		);

		$ch = curl_init($url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($registrant_data));
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);  

		$response = curl_exec($ch);
		$status_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

		if ($status_code == 201) {
			echo 'Registrant added successfully.';
		}else{
			
			
			//$response_data= $zoom->doRequest(GET, '/meetings/{meetingId}/invitation','',$pathParams); 
			$email_body = $response_data['invitation'];
			$msg=htmlentities ($email_body);
			
			
			$this->load->library('email');
			$this->email->from($email, 'Meeting');
			$this->email->to($registrant_email);
			$this->email->subject('Zoom Meeting Invitation');
			$this->email->message($msg);
			$this->email->set_mailtype("html");
			$this->email->send();
			
            set_alert('success', _l('zoom_registrant_added', _l('zoom')));
            redirect(admin_url('zoom_meetings/add_registrant'));
        }
    }

	
}
