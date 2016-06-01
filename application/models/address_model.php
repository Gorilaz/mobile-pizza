<?php

class Address_Model extends CI_Model {

	public function __construct(){
		parent::__construct();
		$this->load->database();
	}


	public function getSuburbDataById($suburb_id=0){
		if($suburb_id > 0){
			// $query = $this->db->query("select * from mast_country where country_id=".$country_id);

			$query = $this->db->get_where('tbl_suburb', array('id' => $suburb_id));

			$row = array();
			if($query->num_rows() > 0){
				$row = $query->row();
			}
			return $row;
		}
	}//end getSuburbDataById

	#-----------------------------------------
   // get State record by id
   #-----------------------------------------

   public function getStateDataById($id=0){
      if($id > 0){
         $query = $this->db->query("select * from mast_state where id=".$id);
         $row = array();
         if($query->num_rows() > 0){
            $row = $query->row();
         }
         return $row;
      }
   }//end getStateDataById

}//end class Address_Model

?>
