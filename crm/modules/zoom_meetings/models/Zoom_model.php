<?php



defined('BASEPATH') or exit('No direct script access allowed');



class Zoom_model extends App_Model

{

    public function __construct()

    {

        parent::__construct();

    }



   
// Update meeting settings
public function update_meeting_settings($id, $data)
{
    if (empty($id) || empty($data)) {
        return false; // Early return if invalid input
    }

    $this->db->where('id', $id);
    $result = $this->db->update(db_prefix() . 'zoom', $data);

    if ($result) {
        // Check if any rows were actually affected
        return $this->db->affected_rows() > 0;
    }

    return false; // Return false if update failed
}

    


     public function update_client_meeting_settings($data){



        
        $id=$data['client_id'];
        $this->db->where('client_id', $id);

        $this->db->update(db_prefix() . 'zoom_client', $data);

        return true;



    }
	
    public function insert_meeting($data){

        

        $this->db->insert(db_prefix() . 'zoom_meetings', $data);

        return true;



    }
	
	    public function insert_client_meeting($data){

        

        $this->db->insert(db_prefix() . 'zoom_client_meetings', $data);

        return true;



    }
	
	  public function insert_notificatons($data){

        

        $this->db->insert(db_prefix() . 'notifications', $data);

        return true;



    }
	
	public function delete_meeting($id){
		
		
		
		$this->db->where('meeting_id =',$id);

		$this->db->delete(db_prefix() . 'zoom_meetings');

		
		
	}	
	
	public function delete_client_meeting($id){
		
		
		
		$this->db->where('meeting_id =',$id);

		$this->db->delete(db_prefix() . 'zoom_client_meetings');

		
		
	}
	
	public function check_meeting_exist($id){
		
        $this->db->where('meeting_id =',$id);

		$array = $this->db->get(db_prefix() . 'zoom_meetings')->row_array();

		return $array ;
    }
		public function check_client_meeting_exist($id){
		
        $this->db->where('meeting_id =',$id);

		$array = $this->db->get(db_prefix() . 'zoom_client_meetings')->result_array();

		return $array ;
    }
	
   // Fetch API settings
   public function get_api_settings()
   {
       $this->db->limit(1);
       return $this->db->get(db_prefix() . 'zoom')->result_array();
   }

	
	public function get_client_api_settings($id){



        $this->db->where('id =', $id);

		$array = $this->db->get(db_prefix() . 'zoom_client')->result_array();

		return $array ;

    }
	
	public function get_client_meetings($id){
		
		$this->db->where('client_id =', $id);
		$array = $this->db->get(db_prefix() . 'zoom_client_meetings')->result_array();

		return $array ;
		
		
    } 
	
public function update_access_token($id, $token) {
    if (empty($id) || empty($token)) {
        log_message('error', 'Invalid ID or token passed to update_access_token.');
        return false;
    }

    $data = array('access_token' => $token);

    $this->db->where('id', $id);
    $result = $this->db->update(db_prefix() . 'zoom', $data);

    if ($result) {
        log_message('info', 'Access token updated successfully for ID: ' . $id);
        return true;
    } else {
        log_message('error', 'Failed to update access token for ID: ' . $id);
        return false;
    }
}



    
    public function get_meetings_created_by_user()
    {
        // Debug: Ensure correct user ID is being used
        $current_user_id = get_staff_user_id();
        log_message('debug', 'Fetching meetings for user ID: ' . $current_user_id);
    
        $query = $this->db->get('zoom_meetings');
    
        // Debug: Ensure results are being fetched
        log_message('debug', 'Fetched meetings: ' . json_encode($query->result()));
    
        return $query->result();
    }
    
    

	

}

