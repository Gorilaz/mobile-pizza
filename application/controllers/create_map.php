<?php
/** ********************************************************************************************************************************************************
 *   File: To create google map image & retrieve its driving direction 
 *   Author: Amol K.
 *   Last modified Date: 14 Oct. 2011
 **********************************************************************************************************************************************************/

   //$location = "penn station, new york, ny";//$row11['redeem_at_add'].",".$row11['city'].",".$row11['code'].",".$row11['zipcode'];


	if($data['map_flag'] == 'Y')
	{
		$gurl = "http://maps.google.com/maps/api/staticmap?center=".str_replace(" ","+",$destination)."&zoom=16&size=500x180&maptype=roadmap&markers=color:red|color:red|label:A|".str_replace(" ","%",$destination)."&sensor=true";
			//---------------Create PNG Images----------------------------------
		$img_time = time();
		//$img = "uploads/gmap/".$img_time.".png";
		$img = $this->config->item('gmap_upload_dir').$img_time.".png";
		$ch = curl_init($gurl);
		$fp = fopen($img, 'wb');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		//-----------Convert to jpg image--------------------------------
		png2jpg($this->config->item('gmap_upload_dir').$img_time.".png",$this->config->item('gmap_upload_dir').$orderId.".jpg","100%");
		//-----------Unlink png image--------------------------------
		@unlink($this->config->item('gmap_upload_dir').$img_time.".png");
	}	

	function png2jpg($originalFile, $outputFile, $quality)
	{
		$image = @imagecreatefrompng($originalFile);
		@imagejpeg($image, $outputFile, $quality);
		@imagedestroy($image);
	} 
	
   /**   code for directions  **/
   //$source = urlencode('grand central station, new york, ny');
	//$source = urlencode($shop_address);
   //$destination = urlencode('350 5th Ave, New York, NY, 10118');
  // echo $source." ".$destination;
	$tbl = '';
	if($data['driving_instruction_flag'] == 'Y')
	{
		$gurl = "http://maps.googleapis.com/maps/api/directions/json?origin=".$source."&alternatives=false&units=km&destination=".$destination."&sensor=false";
	
		$curl_handle=curl_init();
		curl_setopt($curl_handle, CURLOPT_URL, $gurl);//"http://www.youtube.com/watch?v=sPzuqCfqc14&feature=related");
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 2);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
		$jsondata = curl_exec($curl_handle);
		curl_close($curl_handle); 
	
		$arr = json_decode($jsondata,true);

		$direction = '';
	
		$start_address = (!empty($arr['routes']['0']['legs']['0']['start_address']))?$arr['routes']['0']['legs']['0']['start_address']:'';
		$end_address = (!empty($arr['routes']['0']['legs']['0']['end_address']))?$arr['routes']['0']['legs']['0']['end_address']:'';
		$distance = (!empty($arr['routes']['0']['legs']['0']['distance']['text']))?$arr['routes']['0']['legs']['0']['distance']['text']:'';
		$duration = (!empty($arr['routes']['0']['legs']['0']['duration']['text']))?$arr['routes']['0']['legs']['0']['duration']['text']:'';

		$tbl = '<table  width="450px" border="0" cellspacing="0" cellpadding="0" style="font-family:Arial; font-size:16px; line-height:20px;" align="center; margin-left:-20px ">';
        $tbl .= '<tr><td width="450px" style="padding:5px;" ><b>'.$end_address.'</b></td></tr>';
		$i = 1;
		
		$direction = "<pre>\n\n";
		$direction .= $start_address."\n\n";
		$direction .= $distance." - about ".$duration."\n\n";
	
		if(!empty($distance) && !empty($duration))
		{
		$tbl .= '<tr><td width="450px" style="padding:5px;" bgcolor="#FFFFFF"><b>'.$distance." - about ".$duration.'</b></td></tr>';
		}
		else
		{
			$tbl .= '<tr><td width="450px" style="padding:5px;" bgcolor="#FFFFFF"><b></b>'.$distance." Due to wrong address we unable to provide driving instruction ".$duration.'</td></tr>';
		}


		$tbl .= '<tr><td bgcolor="#FFFFFF" style="padding:5px;">
					<table width="400" border="0" cellspacing="0" cellpadding="0" style="font-family:Arial; font-size:16px;" align="left">
					<tr><td valign="top" style="padding:5px;" >';
	
		if(!empty($arr['routes']['0']['legs']['0']['steps']))
		{
			$recCount = count($arr['routes']['0']['legs']['0']['steps']);
			$style = ''; $i = 1;
			foreach($arr['routes']['0']['legs']['0']['steps'] as $d)
			{
				//$d['html_instructions'] = str_replace('Destination will', '<br />Destination will', $d['html_instructions']);
				if(strpos($d['html_instructions'], 'Destination will be on the'))
				{
					$arr = explode('Destination', $d['html_instructions']);
					$direction = '<b>'.$i.".</b> ".$arr[0]." ";
					$tbl .= $direction;
					//$tbl .= '</td></tr>';	
					//$tbl .= '<tr><td valign="top" style="padding:5px;">';
					/*$d['html_instructions'] = $arr[0];
					
					$direction = '<b>'.$i.".</b> ".$d['html_instructions'].".";
					$tbl .= $direction;   
					$tbl .= '</td></tr>';	
					$tbl .= '<tr><td valign="top" style="padding:5px;">';
					
					$direction = 'Destination will be on the '.$arr[1]." ".$d['distance']['text'];*/
					//$direction = '0-->'.$arr[0]."1-->".$arr[1]." dist= ".$d['distance']['text'];
					//$direction = '<b>'.$i.".</b> ".$arr[0]." Destination ".$arr[1]." ".$d['distance']['text'].".";
					$direction = ' - Destination '.$arr[1]." ".$d['distance']['text'].".";
				}
				else
				{
					$direction = '<b>'.$i.".</b> ".$d['html_instructions']." ".$d['distance']['text'].".";
				}
				$direction = '<b>'.$i.".</b> ".$d['html_instructions']." ".$d['distance']['text'].".";
				if($i==$recCount)
					$i.$style = '';
				else 
					$i.$style = 'style="border-bottom:1px solid #000000"';
				
         	//	 $tbl .= $direction;   
				if(strpos($d['html_instructions'], 'Destination will be on the'))
				{
					$t = strip_tags($direction, '<b>');
					$direction = $t;
				}  
				$tbl .= str_replace("Destination",", Destination",$direction);
	
				$i++;
			}//for	
		}	
		$tbl .= '</td></tr></table></td></tr>';	
		$direction .= "\n".$end_address;	

	
		$tbl .= '</table>';
//        print_pre($tbl);die;
	// echo $tbl;exit;
	}
?>
