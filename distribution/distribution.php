<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Distribution extends CI_Controller {
	
	private $replymail = "";
    
	function __construct()
	{
		parent::__construct();
		ini_set('memory_limit', '-1');
		$this->load->helper(array('form', 'url'));
	  $this->load->library('session');
	  $this->load->library('exportxls');
	  $this->load->model('distributiondata');//distribution_list.sql
	  $this->load->model('distributionemails');//distribution_list_emails.sql
	  
	}
	
  //process on uploading csv files from forms
	public function uploaddistribution(){
		$userid = $this->session->userdata('userid');
		if ($this->session->userdata('logged_in')){
		$title = $this->db->escape_str($this->input->post('file_title'));
		$config['upload_path'] = './uploads/distribution';
  	    $config['allowed_types'] = 'csv';
	    $this->load->library('upload', $config);
	    
	    if ( ! $this->upload->do_upload('csv_file'))
		{
			$error = array('error' => $this->upload->display_errors());
		    $content = ""; 
			foreach ($error as $e){
				$content .= ' <div class="notification msgerror">
                        <a class="close"></a>
                        <p>'.$e.'</p>
                        </div><!-- notification msgerror -->
                    '; 
			}
		}
		else
		{
			$data = array('upload_data' => $this->upload->data());
	  		 	 foreach ($this->upload->data('csv_file') as $item => $value){
	            if ($item=="file_name"){
	              $filename = $value;
	            }
	          }
	         
	          $darray = array('title'=>$title,'filename'=>$filename,'member_id'=>$userid);
	          $list_id = $this->distributiondata->update(0,$darray);
	         
		      $content = "";
	          	$content = '<div class="notification msgsuccess">
                        <a class="close"></a>
                        <p>You successfully uploaded csv file.</p>
                    </div><!-- notification msgsuccess -->';
					
			 
		   }  
	        
			 redirect('/distribution');
			 exit;
		}else {
			redirect(base_url());
		}
	}


	private function str_getcsv($input, $delimiter=',', $enclosure='"', $escape=null, $eol=null) {
	  $temp=fopen("php://memory", "rw");
	  fwrite($temp, $input);
	  fseek($temp, 0);
	  $r = array();
	  while (($data = fgetcsv($temp, 4096, $delimiter, $enclosure)) !== false) {
	    $r[] = $data;
	  }
	  fclose($temp);
	  return $r;
	}
	
  private function CustomImplode($parts = null, $delimiter = "," ){
			$returnValue = "";
			foreach($parts as $key=>$value){
				if(strpos($value, ","))
					$parts[$key] = sprintf("\"%s\"", $value);
				else
					$parts[$key] = $value;
			}
			$returnValue = implode(",", $parts);
			return $returnValue;
		}
	
    function ProcessRow($line=null,$records){
    	
	    $returnValue = array();
	    $exist = 0;
	    $index = 0;
	    if (!empty($line)){
		     $Data = $this->str_getcsv($this->CustomImplode($line)); //parse the rows
		     foreach($Data as &$d){
		     $domain = $this->db->escape_str($d[0]);
		     $owner = $this->db->escape_str($d[4]);
		     $email = $this->db->escape_str($d[5]);
	    }  
	     
	     	if ($email != ""){
	     		
	     		if(stristr($email, ',') === FALSE) {
	     			
	     		}else {
	     			$e = explode(',',$email);
	     			$email = $e[0];
	     		}
	     		
	     		if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
	     			
				 	   $returnValue[$email] = array('owner'=>$owner,'domains'=>$domain);
		     		
	     		}
	     		
	     	}
		 
    } 
    return $returnValue;
    }


    //function to parse every csv line/rows to get emails
    private function process($list_id){
       $lineNumber = 0;
	   
	   $rowdata = array();
	   $dom = array();
	   
	   
	   $filename = './uploads/distribution/'.$this->distributiondata->getinfobyid('filename',$list_id);
	   $records = array();
		  	   $fh = fopen($filename, 'r');
				while (($line = fgetcsv($fh, 3000, ',')) !== FALSE) {
						if ($lineNumber>0){
						     $result = $this->ProcessRow($line,$records);
	               			 array_push($records, $result);
					    }
					    $lineNumber++;
				}
				fclose($fh);
	 
				$i =0;
				
	  foreach ($records as $count=>$array){
		if (count($array)>0){
			  foreach ($array as $key=>$val){
			  	      if ($this->distributionemails->checkexist('list_id',$list_id,'email',$key)===false){
			  	      	  	  $rowdata = array('email'=>$key,'owner'=>$val['owner'],'domains'=>$val['domains'],'list_id'=>$list_id);
			  	      	  	  $this->distributionemails->update(0,$rowdata);
					  }else {
					  	
					  	     $query = $this->distributionemails->getbyattribute('list_id',$list_id,'email', $key);
							  if ($query->num_rows() > 0){
					          foreach ($query->result() as $row)
						         {
						           $email_id =  $row->email_id;
						           $domains = $row->domains;
						           
						         }
					          }
								$rowdata = array('domains'=>$domains.",".$val['domains']);
								$this->distributionemails->update($email_id,$rowdata);
					  	   
					  }
			  }
		}
		
	  }		
	    unset($records);
	    return true;
	  
    }
    
   //function to export parse results per filename  
	public function export(){
	    $list_id = $this->uri->segment(3);
	    $list_name = $this->distributiondata->getinfobyid('title',$list_id); 
	   	$query = $this->distributionemails->getbyattribute('list_id',$list_id);
			if ($query->num_rows() > 0){
				$header[] = "Email";
				$header[] = "Owner";
				$header[] = "Domains";
				$this->exportxls->addHeader($header);
				
				foreach ($query->result() as $row){
					
						$data_row = array();
						$data_row[] = $row->email;
						$data_row[] = $row->owner;
						$data_row[] = $row->domains;
						$this->exportxls->addRow($data_row);
				 }
			}
		
		$list_name = str_replace(' ','',strtolower($list_name));
		$this->exportxls->sendFile($list_name.'.xls');
	}
	

	
    //cron job function to process uploaded csv files
    
    public function cronprocess(){
    	
	    $query = $this->distributiondata->getbyattribute('status',0);
			if ($query->num_rows() > 0){
				foreach ($query->result() as $row){
					   if ($this->process($row->list_id)===true){
					   	  $darray = array('status'=>1);
					   	  $this->distributiondata->update($row->list_id,$darray);
					   	  echo "Processed: ".$row->title." files<br>";
					   }
					  
				 }
			}else {
				echo "No pending distribution list";
			}
		
		
	}
	

	
	
	
}

/* End of file welcome.php */
/* Location: ./application/controllers/welcome.php */