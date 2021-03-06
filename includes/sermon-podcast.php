<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function ca_podcast_list_series()
{
/**
 *
 * Lists podcast series
 * 
 * @author  Andy Moyle
 * @param    null
 * @return   html string
 * @version  0.1
 * 
 */    
    global $wpdb;


    echo'<h2>Sermon Series</h2>';
    echo'<a href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=edit_sermon_series','edit_sermon_series').'">Add a Sermon Series</a></p>';
            
    //grab files from table
    $results=$wpdb->get_results('SELECT * FROM '.CA_SERM_TBL);
    if($results)
    {//results
        $table='<table class="widefat striped"><thead><tr><th>'.__('Edit','church-admin').'</th><th>'.__('Delete','church-admin').'</th><th>'.__('Series','church-admin').'</th><th>'.__('Files','church-admin').'</th><th>'.__('Shortcode','church-admin').'</th></tr></thead>'."\r\n".'<tfoot><tr><th>'.__('Edit','church-admin').'</th><th>'.__('Delete','church-admin').'</th><th>'.__('Series','church-admin').'</th><th>'.__('Files','church-admin').'</th><th>'.__('Shortcode','church-admin').'</th></tr></tfoot>'."\r\n".'<tbody>';
        foreach($results AS $row)
        {
            $edit='<a href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=edit_sermon_series&amp;id='.intval($row->series_id),'edit_sermon_series').'">'.__('Edit','church-admin').'</a>';
            $delete='<a onclick="return confirm(\''.__('Are you sure?','church-admin').'\');" href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=delete_sermon_series&amp;id='.intval($row->series_id),'delete_sermon_series').'">'.__('Delete','church-admin').'</a>';
            $files=$wpdb->get_var('SELECT count(*) FROM '.CA_FIL_TBL.' WHERE series_id="'.esc_sql($row->series_id).'"');
            if(!$files)$files="0";
            $table.='<tr><td>'.$edit.'</td><td>'.$delete.'</td><td>'.esc_html($row->series_name).'</td><td>'.intval($files).'</td><td>[church_admin type="podcast" series_id="'.intval($row->series_id).'"]</td></tr>';
        }
        
        $table.='</tbody></table>';
        echo $table;
    }//end results
    else
    {
        echo'<p>'.__('No Sermon Series stored yet','church-admin').'</p>';
    }
	

}
function ca_podcast_delete_series($id=NULL)
{
    /**
 *
 * Delete podcast events
 * 
 * @author  Andy Moyle
 * @param    $id=null
 * @return   html string
 * @version  0.1
 * 
 */

	global $wpdb;
	$wpdb->query('DELETE  FROM '.CA_SERM_TBL.' WHERE series_id="'.esc_sql(intval($id)).'"');
	 echo'<div class="notice notice-success inline"><p>'.__('Series Deleted','church-admin').'</p></div>';
        ca_podcast_list_series();
 
 }
function ca_podcast_edit_series($id=NULL)
{
    /**
 *
 * Edit podcast events
 * 
 * @author  Andy Moyle
 * @param    $id=null
 * @return   html string
 * @version  0.1
 * 
 */
	
    global $wpdb;
    if(!empty($id))
    {
        $current_data=$wpdb->get_row('SELECT * FROM '.CA_SERM_TBL.' WHERE series_id="'.esc_sql(intval($id)).'"');
        $title='Edit';
    }
    else
    {
        $title='Add';
    }
    echo'<h2>'.esc_html($title).' Sermon Series</h2>';
    if(!empty($_POST['save_series']))
    {//process form
        $series_name=esc_sql(sanitize_text_field(stripslashes($_POST['series_name'])));
        $series_description=esc_sql(sanitize_text_field(stripslashes($_POST['series_description'])));
        if(empty($id))$id=$wpdb->get_var('SELECT series_id FROM '.CA_SERM_TBL.' WHERE series_name="'.$series_name.'" AND series_description="'.$series_description.'"');
        if(!empty($id))
        {//update
            $wpdb->query('UPDATE '.CA_SERM_TBL.' SET series_name="'.$series_name.'",series_description="'.$series_description.'" WHERE series_id="'.esc_sql($id).'"');
        }//end update
        else
        {//insert
            $wpdb->query('INSERT INTO '.CA_SERM_TBL.' (series_name,series_description)VALUES("'.$series_name.'","'.$series_description.'")');
        }//end insert
        echo'<div class="notice notice-success inline"><p>'.__('Series Saved','church-admin').'</p></div>';
        ca_podcast_list_series();
    }//end process form
    else
    {//form
        echo '<form action="" method="POST">';
        echo'<p><label for="series_name">'.__('Series Name','church-admin').'</label><input type="text" name="series_name" id="series_name" ';
        if(!empty($current_data->series_name)) echo 'value="'.esc_html($current_data->series_name).'"';
        echo'/></p>';
        echo'<p><label for="series_description">'.__('Series Description','church-admin').'</label></p>';
        echo'<textarea name="series_description" id="series_description">';
		if(!empty($current_data->series_description))echo esc_html($current_data->series_description);
		echo'</textarea></p>';
        echo '<p><input type="hidden" name="save_series" value="save_series"/><input type="submit" class="primary-button" value="'.__('Save Sermon Series','church-admin').'"/></p></form>';
    }//form
    
   
}




function ca_podcast_list_files()
{
/**
 *
 * Lists podcast files
 * 
 * @author  Andy Moyle
 * @param    null
 * @return   html string
 * @version  0.1
 * 
 */    
    global $wpdb;

	$upload_dir = wp_upload_dir();
	$path=$upload_dir['basedir'].'/sermons/';
	$url=content_url().'/uploads/sermons/';
   
    if(!file_exists($path.'podcast.xml'))
    {
        ca_podcast_xml();
        
    }
    if(file_exists($path.'podcast.xml'))echo'<p><a href="'.$url.'podcast.xml">Podcast RSS File</a></p>';
    //grab files from table
    $results=$wpdb->get_results('SELECT a.* FROM '.CA_FIL_TBL.' a  ORDER BY pub_date DESC');
    if($results)
    {//results
        $table='<table class="widefat striped"><thead><tr><th>'.__('Edit','church-admin').'</th><th>'.__('Delete','church-admin').'</th><th>'.__('Publ. Date','church-admin').'</th><th>'.__('Title','church-admin').'</th><th>'.__('Speakers','church-admin').'</th><th>'.__('Mp3 File','church-admin').'</th></th><th>'.__('File Okay?','church-admin').'</th><th>'.__('Length','church-admin').'</th><th>'.__('Media','church-admin').'</th><th>'.__('Transcript','church-admin').'</th><th>'.__('Event','church-admin').'</th><th>'.__('Shortcode','church-admin').'</th></tr></thead>'."\r\n".'<tfoot><tr><th>'.__('Edit','church-admin').'</th><th>'.__('Delete','church-admin').'</th><th>'.__('Publ. Date','church-admin').'</th><th>'.__('Title','church-admin').'</th><th>'.__('Speakers','church-admin').'</th><th>'.__('Mp3 File','church-admin').'</th></th><th>'.__('File Okay?','church-admin').'</th><th>'.__('Length','church-admin').'</th><th>'.__('Media','church-admin').'</th><th>'.__('Transcript','church-admin').'</th><th>'.__('Event','church-admin').'</th><th>'.__('Shortcode','church-admin').'</th></tr></tfoot>'."\r\n".'<tbody>';
        foreach($results AS $row)
        {
            if(file_exists(plugin_dir_path( $path.$row->file_name))){$okay='<img src="'.plugins_url('images/green.png',dirname(__FILE__) ) .'" width="32" height="32"/>';}else{$okay='<img src="'.plugins_url('images/red.png',dirname(__FILE__) ) .'" width="32" height="32"/>';}
            $edit='<a href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=edit_file&amp;id='.$row->file_id,'edit_podcast_file').'">'.__('Edit','church-admin').'</a>';
            $delete='<a onclick="return confirm(\''.__('Are you sure?','church-admin').'\');" href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=delete_file&amp;id='.$row->file_id,'delete_podcast_file').'">'.__('Delete','church-admin').'</a>';
            $series_name=$wpdb->get_var('SELECT series_name FROM '.CA_SERM_TBL.' WHERE series_id="'.esc_sql($row->series_id).'"');
            if(!empty($row->file_name)&&file_exists($path.$row->file_name)){$file='<a href="'.esc_url($url.$row->file_name).'">'.esc_html($row->file_name).'</a>';$okay='<img src="'.plugins_url('images/green.png',dirname(__FILE__) ) .'"/>';}
			elseif(!empty($row->external_file)){$file='<a href="'.esc_url($row->external_file).'">'.esc_html($row->external_file).'</a>';$okay='<img src="'.plugins_url('images/green.png',dirname(__FILE__) ) .'"/>';}
			else{$file='&nbsp;';$okay='<img src="'.plugins_url('images/red.png',dirname(__FILE__) ).'"/>';}
            $table.='<tr><td>'.$edit.'</td><td>'.$delete.'</td><td>'.date(get_option('date_format'),strtotime($row->pub_date)).'</td><td>'.esc_html($row->file_title).'</td><td>'.esc_html(church_admin_get_people($row->speaker)).'</td><td>'.$file.'</td><td>'.$okay.'</td><td>'.esc_html($row->length).'</td><td>'.$row->video_url.'</td>';
            if(file_exists($path.$row->transcript)){$table.='<td><a href="'.esc_url($url.$row->transcript).'">'.esc_html($row->transcript).'</a></td>';}else{$table.='<td>&nbsp;</td>';}
            $table.='<td>'.esc_html($series_name).'</td><td>[church_admin type="podcast" file_id="'.intval($row->file_id).'"]</td></tr>';
        }
        
        $table.='</tbody></table>';
        echo $table;
    }//end results
    else
    {
        echo'<p>'.__('No files stored yet','church-admin').'</p>';
    }

}

function ca_podcast_edit_file($id=NULL)
{
  /**
 *
 * Edit podcast file
 * 
 * @author  Andy Moyle
 * @param    $id=null
 * @return   html string
 * @version  0.2
 * 
 * 0.2 remove stored XSS vulnerability by sanitising input further
 */
    global $wpdb;
	$settings=get_option('ca_podcast_settings');


	$upload_dir = wp_upload_dir();
	$path=$upload_dir['basedir'].'/sermons/';
	$url=content_url().'/uploads/sermons/';
    if(!empty($id))
    {
        $current_data=$wpdb->get_row('SELECT * FROM '.CA_FIL_TBL.' WHERE file_id="'.esc_sql($id).'"');
        $title='Edit';
    }
    else
    {
        $title='Add';
    }
	if(empty($current_data))$current_data=new stdClass();
    echo'<h2>'.esc_html($title).' File</h2>';
    if(!empty($_POST['save_file']))
    {//process form
        $length='00:00';
		$file_name='';
        if(!empty($_FILES['file']['name']))
		{
			//handle upload
			//mp3s
			$arr_file_type = wp_check_filetype(basename($_FILES['file']['name']));
			$uploaded_file_type = $arr_file_type['type'];
			// Set an array containing a list of acceptable formats
			$allowed_file_types = array( 'audio/mpeg','audio/mpeg3','audio/x-mpeg-3','video/mpeg','video/x-mpeg','application/pdf');
			// If the uploaded file is the right format
			if(in_array($uploaded_file_type, $allowed_file_types))
			{//valid image
				$tmp_name = $_FILES["file"]["tmp_name"];
				$name = $_FILES["file"]["name"];
				$x=1;
				$type=substr($name,-3);
				$split=sanitize_title(substr($name,0,-4));
				$file_name=$split.'.'.$type;
				while(file_exists($path.$file_name))
				{
					$file_name=$split.$x.'.'.$type;
					$x++;
				}
				if(!move_uploaded_file($tmp_name, $path.$file_name)) echo'<p>'.__('File Upload issue','church-admin').'</p>';
            }    
		}
		if(empty($file_name) &&!empty($current_data->file_name))$file_name=$current_data->file_name;   
         if(!empty($file_name)&&file_exists($path.$file_name))
		{			 
			require_once(plugin_dir_path(dirname(__FILE__)).'includes/mp3.php');
            $m = new mp3file($path.$file_name);
            $a = $m->get_metadata();
            $length=esc_sql($a['Length mm:ss']);
		}
			//end mp3
		
        //transcript
        $arr_file_type = wp_check_filetype(basename($_FILES['transcript']['name']));
        $uploaded_file_type = $arr_file_type['type'];
       
        // Set an array containing a list of acceptable formats
        $allowed_file_types = array('application/pdf','application/msword','application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        // If the uploaded file is the right format
        if(in_array($uploaded_file_type, $allowed_file_types))
        {//valid image
            $tmp_name = $_FILES["transcript"]["tmp_name"];
            $name = $_FILES["transcript"]["name"];
            $x=1;
            $type=substr($name,-3);
            $split=sanitize_title(substr($name,0,-4));
            $transcript=$split.'.'.$type;
            while(file_exists($path.$transcript))
            {
                
                $transcript=$split.$x.'.'.$type;
                $x++;
            }
            
            if(!move_uploaded_file($tmp_name, $path.$transcript)) echo'<p>'.__('File Upload issue','church-admin').'</p>';
             
        } 
           
        //end handle upload
        
        $form=array();
		foreach($_POST AS $key=>$value){$form[$key]=sanitize_text_field(stripslashes($value));}
        foreach($_POST AS $key=>$value){$sqlsafe[$key]=esc_sql(sanitize_text_field(stripslashes($value)));}
        if(!empty($sqlsafe['sermon_series']))
        {
            //check if already exists
            $check=$wpdb->get_var('SELECT series_id FROM '.CA_SERM_TBL.' WHERE series_name="'.$sqlsafe['sermon_series'].'"');
            if(!$check)
            {
                $wpdb->query('INSERT INTO '.CA_SERM_TBL.' (series_name)VALUES("'.$sqlsafe['sermon_series'].'")');
                $sqlsafe['series_id']=$wpdb->insert_id;
            }
            else
            {
                $sqlsafe['series_id']=$check;
            }
        }
        $speaker=esc_sql($sqlsafe['people']);
        if(!empty($_POST['private'])){$private="1";}else{$private="0";}
        if(empty($_POST['pub_date'])){$sqlsafe['pub_date']=date("Y-m-d" );}else{$sqlsafe['pub_date']=$_POST['pub_date'];}
		if(empty($transcript))$transcript='';
	
        $sqlsafe['pub_date'].=' 12:00:00';
        if(empty($id))$id=$wpdb->get_var('SELECT file_id FROM '.CA_FIL_TBL.' WHERE external_file="'.$sqlsafe['audio_url'].'" AND length="'.$length.'" AND private="'.$private.'" AND file_name="'.$file_name.'" AND file_title="'.$sqlsafe['file_title'].'" AND file_description="'.$sqlsafe['file_description'].'" AND service_id="'.$sqlsafe['service_id'].'" AND series_id="'.$sqlsafe['series_id'].'" AND speaker="'.$speaker.'"');
        
        
        if(!empty($id))
        {//update
            $sql='UPDATE '.CA_FIL_TBL.' SET external_file="'.$sqlsafe['audio_url'].'", video_url="'.$sqlsafe['video_url'].'",transcript="'.$transcript.'",file_subtitle="'.$sqlsafe['file_subtitle'].'",pub_date="'.$sqlsafe['pub_date'].'",length="'.$length.'", private="'.$private.'",last_modified="'.date("Y-m-d H:i:s" ).'",file_name="'.$file_name.'" , file_title="'.$sqlsafe['file_title'].'" , file_description="'.$sqlsafe['file_description'].'" , service_id="'.$sqlsafe['service_id'].'",series_id="'.$sqlsafe['series_id'].'" , speaker="'.$speaker.'" WHERE file_id="'.esc_sql($id).'"';
			
            $wpdb->query($sql);
        }//end update
        else
        {//insert
            $sql='INSERT INTO '.CA_FIL_TBL.' (file_name,file_title,file_subtitle,file_description,private,length,service_id,series_id,speaker,pub_date,last_modified,transcript,video_url,external_file)VALUES("'.$file_name.'","'.$sqlsafe['file_title'].'","'.$sqlsafe['file_subtitle'].'","'.$sqlsafe['file_description'].'" ,"'.$private.'","'.$length.'","'.$sqlsafe['service_id'].'","'.$sqlsafe['series_id'].'","'.$speaker.'" ,"'.$sqlsafe['pub_date'].'","'.date("Y-m-d H:i:s" ).'","'.$transcript.'","'.$sqlsafe['video_url'].'","'.$sqlsafe['audio_url'].'")';
			$wpdb->query($sql);
			$id=$wpdb->insert_id;
        }//end insert
		//post if set
		if(!empty($_POST['blog']))
		{
			
			$title=$form['file_title'];
			$content='[church_admin type="podcast" file_id="'.$id.'"]';
			$cat_id=wp_create_category( __('Sermon Mp3s','church-admin'));
			$postID=$wpdb->get_var('SELECT postID FROM '.CA_FIL_TBL.' WHERE file_id="'.esc_sql($id).'"');
			
			$args=array('post_title'=>$title,'post_content'=>$content,'post_type'=>'post','post_status'=>'publish');
			if(empty($postID))$args['ID']=$postID;
			$postID=wp_insert_post($args);
			$message ='<p><a href="'.esc_url( get_permalink($postID) ).'">Sermon posted </a></p>';
			
			wp_set_post_categories($postID,array($cat_id));
			if(!empty($settings['thumbnail_id'])){set_post_thumbnail( $postID, $settings['thumbnail_id'] );echo'<p>Thumbnail set</p>';}
			
			$wpdb->query('UPDATE '.CA_FIL_TBL.' SET postID="'.$postID.'"');
		}
        ca_podcast_xml();//update podcast feed
        echo'<div class="notice notice-success inline"><p>'.__('File','church-admin').' '.esc_html($file_name).' '.__('Saved','church-admin').'</p>'.$message.'</div>';
        ca_podcast_list_files();
    }//end process form
    else
    {//form
        $max_upload = (int)(ini_get('upload_max_filesize'));
        $max_post = (int)(ini_get('post_max_size'));
        $memory_limit = (int)(ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        echo'<p>You can upload a file up to '.$upload_mb.'MB </p>';
        echo '<form action="" method="POST"  enctype="multipart/form-data" id="churchAdminForm">';
        echo'<table class="form-table"><tbody><tr><th scope="row">'.__('File Title','church-admin').'</th><td><input type="text" name="file_title" id="file_title" ';
        if(!empty($current_data->file_title)) echo 'value="'.esc_html($current_data->file_title).'"';
        echo'/></td></tr>';
        echo'<tr><th scope="row">'.__('File SubTitle (a few words)','church-admin').'</th><td><input type="text" name="file_subtitle" id="file_subtitle" ';
        if(!empty($current_data->file_subtitle)) echo 'value="'.esc_html($current_data->file_subtitle).'"';
        echo'/></td></tr>';
        echo'<tr><th scope="row">'.__('File Description','church-admin').'</th><td>';
        echo '<textarea name="file_description">';
        if(!empty($current_data->file_description)) echo esc_html($current_data->file_description);
        echo'</textarea></td></tr>';
        echo'<tr><th scope="row">'.__('Logged in only','church-admin').'?</th><td><input type="checkbox" name="private" value="yes"/></td></tr>';
        //sermon series
        $series_res=$wpdb->get_results('SELECT * FROM '.CA_SERM_TBL.' ORDER BY series_id DESC');
        if($series_res)
        {
            $first='<option value="">'.__('Choose a sermon series...','church-admin').'</option>';
            echo'<tr><th scope="row">'.__('Sermon Series','church-admin').'</th><td><select name="series_id">';
            $first=$option='';
            foreach($series_res AS $series_row)
            {
                if(!empty($series_row->series_id)&&!empty($current_date->series_id)&&$series_row->series_id==$current_data->series_id)
                {
                    $first='<option value="'.intval($series_row->series_id).'" selected="selected">'.esc_html($series_row->series_name).'</option>';
                }
                else
                {
                    $option.='<option value="'.intval($series_row->series_id).'">'.esc_html($series_row->series_name).'</option>';
                }
                
            }
            echo $first.$option.'</select></td></tr>';
        }
        
            echo'<tr><th scope="row">'.__('Create a new sermon series','church-admin').'</th><td><input type="text" name="sermon_series"/></td></tr>';
        
        //service
        $service_res=$wpdb->get_results('SELECT CONCAT_WS(" ",service_name,service_time) AS service_name,service_id FROM '.CA_SER_TBL.' ORDER BY service_id DESC');
        if($service_res)
        {
            echo'<tr><th scope="row">'.__('Service','church-admin').'</th><td><select name="service_id">';
            $first=$option='';
            foreach($service_res AS $service_row)
            {
                if($service_row->service_id==$current_data->service_id)
                {
                    $first='<option value="'.intval($service_row->service_id).'" selected="selected">'.esc_html($service_row->service_name).'</option>';
                }
                else
                {
                    $option.='<option value="'.intval($service_row->service_id).'">'.esc_html($service_row->service_name).'</option>';
                }
                
            }
            echo $first.$option.'</select></td></tr>';
        }
        echo'<tr><th scope="row">'.__('Speaker','church-admin').'</th><td>';
        $s=array();
	
        if(empty($current_data->speaker))$current_data->speaker='';
        echo church_admin_autocomplete('people','friends','to',$current_data->speaker); 
        echo'</td></tr>';
        if(empty($current_data->pub_date))$current_data->pub_date=date('Y-m-d');
        //javascript to bring up date picker
	echo'<script type="text/javascript">jQuery(document).ready(function(){jQuery(\'#pub_date\').datepicker({dateFormat : "yy-mm-dd", changeYear: true ,yearRange: "1910:'.date('Y').'"});});</script>';
	//javascript to bring up date picker
        echo'<tr><th scope="row">'.__('Publication Date','church-admin').'</th><td><input type="text" name="pub_date" id="pub_date" value="'.date('Y-m-d',strtotime($current_data->pub_date)).'"/></td></tr>';
        echo'<tr><th scope="row">'.__('Mp3 File to Upload','church-admin').'</th><td><input type="file" name="file" id="file"/></td></tr>';
        echo'<tr><th scope="row">'.__('External Audio mp3 URL','church-admin').'</th><td><input type="text" name="audio_url" id="audio_url"';
		if(!empty($current_data->external_file))echo' value="'.esc_url($current_data->external_file).'" ';
		echo'/></td></tr>';
		echo'<tr><th scope="row">'.__('Transcript to Upload ','church-admin').'</th><td><input type="file" name="transcript" id="transcript"/></td></tr>';
        echo'<tr><th scope="row">'.__('Video URL','church-admin').'</th><td><input type="text" name="video_url" id="video_url"';
		if(!empty($current_data->video_url))echo' value="'.esc_url($current_data->video_url).'" ';
		echo'/>'.__('Add [VIDEO_URL] to your sermon files template to display','church-admin').'</td></tr>';
		echo'<tr><th scope="row">'.__('Blog the sermon','church-admin').'</th><td><input type="checkbox" checked name="blog" value="1"/></td></tr>';
        echo '<tr><th scope="row">&nbsp;</th><td><input type="hidden" name="save_file" value="save_file"/><input type="submit"  class="button-primary" id="submit" class="primary-button" value="Save File"/></td></tr></tbody></table></form>';
    }//form
    
    
}

function ca_podcast_delete_file($id=NULL)
{
  /**
 *
 * Delete File
 * 
 * @author  Andy Moyle
 * @param    $id=null
 * @return   html string
 * @version  0.1
 * 
 */
    global $wpdb,$rm_podcast_settings;
    if(!empty($id))
    {//non empty $id
        $data=$wpdb->get_row('SELECT a.*,b.series_name AS series_name FROM '.CA_FIL_TBL.' a , '.CA_SERM_TBL.' b WHERE a.file_id="'.esc_sql($id).'" AND a.series_id=b.series_id');
        if(!empty($_POST['sure']))
        {//end sure so delete
			$upload_dir = wp_upload_dir();
            if(!empty($data->file_name)&&file_exists($upload_dir['basedir'].'/sermons/'.$data->file_name))unlink($upload_dir['basedir'].'/sermons/'.$data->file_name);
            $wpdb->query('DELETE FROM '.CA_FIL_TBL.' WHERE file_id="'.esc_sql($id).'"');
            ca_podcast_xml();//update podcast feed
            echo'<div class="notice notice-success inline">'.esc_html($data->file_title).' '.__('from','church-admin').' '.esc_html($data->series_name).' '.__('deleted','church-admin').'</p></div>';
            ca_podcast_list_files();
        }//end sure so delete
        else
        {
            echo'<p>'.printf(__('Are you sure you want to delete %1$s sermon form %2s?','church-admin'),esc_html($data->file_title),esc_html($data->series_name));
            echo'<form action="" method="post"><input type="hidden" name="sure" value="YES"/><input type="submit" value="'.__('Yes','church-admin').'" class="primary-button"/></form></p>';
        }
        
    }//end non empty $id
    else{echo'<p>'.__('No file specified','church-admin').' '.intval($id).'</p>';}
}

function ca_podcast_check_files()
{
    /**
 *
 * Checks Files in media directory, table of non db stored files
 * 
 * @author  Andy Moyle
 * @param    $id=null
 * @return   html string
 * @version  0.2
 * 0.2 fixed empty $form array 2016-03-20
 * 
 */
    global $wpdb,$rm_podcast_settings;

	$upload_dir = wp_upload_dir();
	$path=$upload_dir['basedir'].'/sermons/';
	$url=content_url().'/uploads/sermons/';
	$files=scandir($path);
    $exclude_list = array(".", "..", "index.php","podcast.xml",".htaccess");
    $files = array_diff($files, $exclude_list);
  
    
        $table='<h2>'.__('Unattached Media Files','church-admin').'</h2><table class="widefat striped"><thead><tr><th>'.__('Delete','church-admin').'</th><th>'.__('Filename','church-admin').'</th><th>'.__('Add to podcast','church-admin').'</th></tr></thead><tfoot><tr><th>'.__('Delete','church-admin').'</th><th>'.__('Filename','church-admin').'</th><th>'.__('Add to podcast','church-admin').'</th></tr></tfoot><tbody>';
    
        foreach($files as $entry)
        {
            $check=$wpdb->get_var('SELECT file_id FROM '.CA_FIL_TBL.' WHERE file_name="'.esc_sql(basename($entry)).'"');
            
            if(is_file($path.$entry)&&!$check)
            {
                
                $delete='<a onclick="return confirm(\''.__('Are you sure?','church-admin').'\');" href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=file_delete&file='.esc_html($entry),'file_delete').'">'.__('Delete','church-admin').'</a>';
                $add='<a href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=file_add&file='.esc_html($entry),'file_add').'">'.__('Add to podcast','church-admin').'</a>';
                $table.='<tr><td>'.$delete.'</td><td>'.$entry.'</td><td>'.$add.'</td></tr>';
            }
        }
        $table.='</tbody></table>';
        echo $table;
       
}

function ca_podcast_file_add($file_name=NULL)
{
  /**
 *
 * Edit podcast file from directory to podcasts
 * 
 * @author  Andy Moyle
 * @param    $id=null
 * @return   html string
 * @version  0.2
 * 0.2 fixed no blog post title and featured image
 * 
 */
 	$settings=get_option('ca_podcast_settings');
    if(!$file_name)wp_die("No file specified");
	$upload_dir = wp_upload_dir();
	$path=$upload_dir['basedir'].'/sermons/';
	$url=content_url().'/uploads/sermons/';
	$current_data=new stdClass();
	$settings=get_option('ca_podcast_settings');
	
    global $wpdb;

    $file_name=basename($file_name);
    echo'<h2>Add File - '.$file_name.'</h2>';
    if(!empty($_POST['save_file']))
    {//process form
        $speaker=esc_sql($_POST['speaker']);
        require_once(plugin_dir_path(dirname(__FILE__)).'includes/mp3.php');
        $file=$path.$file_name;
        $m = new mp3file($file);
        $a = $m->get_metadata();
        $length=esc_sql($a['Length mm:ss']);
        $form=$sqlsafe=array();
        foreach($_POST AS $key=>$value){$form[$key]=sanitize_text_field(stripslashes($value));}
        foreach($_POST AS $key=>$value){$sqlsafe[$key]=esc_sql(sanitize_text_field(stripslashes($value)));}
        
        if(empty($_POST['pub_date'])){$pub_date=date("Y-m-d H:i:s" );}else{$pub_date=date("Y-m-d H:i:s",strtotime($_POST['pub_date']) );}
        if(!empty($_POST['private'])){$private="1";}else{$private="0";}
         
        if(empty($id))$id=$wpdb->get_var('SELECT file_id FROM '.CA_FIL_TBL.' WHERE file_name="'.$file_name.'"' );
        if(!empty($id))
        {//update
            $sql='UPDATE '.CA_FIL_TBL.' SET video_url="'.$sqlsafe['video_url'].'",pub_date="'.$pub_date.'", length="'.$length.'", last_modified="'.date("Y-m-d H:i:s" ).'",private="'.$private.'",file_name="'.$file_name.'" ,file_subtitle= "'.$sql['file_subtitle'].'",file_title="'.$sqlsafe['file_title'].'" , file_description="'.$sqlsafe['file_description'].'" , series_id="'.$sqlsafe['series_id'].'" , speaker="'.$speaker.'" WHERE file_id="'.esc_sql($id).'"';
            
            $wpdb->query($sql);
        }//end update
        else
        {//insert
            $sql='INSERT INTO '.CA_FIL_TBL.' (file_name,file_subtitle,file_title,file_description,private,length,series_id,speaker,pub_date,last_modified,video_url)VALUES("'.$file_name.'","'.$sqlsafe['file_subtitle'].'","'.$sqlsafe['file_title'].'","'.$sqlsafe['file_description'].'" ,"'.$private.'","'.$length.'","'.$sqlsafe['series_id'].'","'.$speaker.'" ,"'.$pub_date.'","'.date("Y-m-d H:i:s" ).'","'.$sqlsafe['video_url'].'")';
           
            $wpdb->query($sql);
            $id=$wpdb->insert_id;
        }//end insert
		//post if set
		if(!empty($_POST['blog']))
		{
			
			$settings=church_admin_handle_podcast_image($settings);
			$title=$form['file_title'];
			$content='[church_admin type="podcast" file_id="'.$id.'"]';
			$cat_id=wp_create_category( __('Sermon Mp3s','church-admin'));
			$postID=$wpdb->get_var('SELECT postID FROM '.CA_FIL_TBL.' WHERE file_id="'.esc_sql($id).'"');
			
			$args=array('post_title'=>$title,'post_content'=>$content,'post_type'=>'post','post_status'=>'publish');
			
			if(empty($postID))$args['ID']=$postID;
			$postID=wp_insert_post($args);
			$message ='<p><a href="'.esc_url( get_permalink($postID) ).'">'.__('Sermon posted.','church-admin').' </a></p>';
			
			wp_set_post_categories($postID,array($cat_id));
			if(!empty($settings['thumbnail_id'])){set_post_thumbnail( $postID, $settings['thumbnail_id'] );echo'<p>'.__('Thumbnail set.','church-admin').'</p>';}
			$wpdb->query('UPDATE '.CA_FIL_TBL.' SET postID="'.$postID.'"');
		}
		//ping where sermons are shown
		$id=church_admin_get_id_by_shortcode('podcast');
		if(!empty($id))
		{
			generic_ping($id);
			$datetime = date("Y-m-d H:i:s");   
			$wpdb->query( "UPDATE `$wpdb->posts` SET `post_modified` = '".$datetime."' WHERE `ID` = '".$id."'" );
		}
        ca_podcast_xml();//update podcast feed
        echo'<div class="notice notice-success inline"><p>'.__('File Saved','church-admin').'</p></div>';
        ca_podcast_list_files();
    }//end process form
    else
    {//form
        echo '<form action="" method="POST" id="churchAdminForm" enctype="multipart/form-data">';
        echo'<table class="form-table"><tbody><tr><th scope="row">File Title</th><td><input type="text" name="file_title" id="file_name" ';
        if(!empty($current_data->file_title)) echo 'value="'.esc_html($current_data->file_title).'"';
        echo'/></td></tr>';
        echo'<tr><th scope="row">File SubTitle (a few words)</th><td><input type="text" name="file_subtitle" id="file_subtitle" ';
        if(!empty($current_data->file_subtitle)) echo 'value="'.esc_html($current_data->file_subtitle).'"';
        echo'/></td></tr>';
        echo'<tr><th scope="row">File Description</th><td>';
        echo '<textarea name="file_description">';
        if(!empty($current_data->file_description))echo esc_html($current_data->file_description);
        echo'</textarea></td></tr>';
        echo'<tr><th scope="row">Logged in only?</th><td><input type="checkbox" name="private" value="yes"/></p>';
        $ev_res=$wpdb->get_results('SELECT * FROM '.CA_SERM_TBL.' ORDER BY series_id DESC');
        if($ev_res)
        {
            echo'<tr><th scope="row">Event</th><td><select name="series_id">';
            $first=$option='';
            foreach($ev_res AS $series_row)
            {
                if($series_row->series_id==$current_data->series_id)
                {
                    $first='<option value="'.intval($series_row->series_id).'" selected="selected">'.esc_html($series_row->series_name).'</option>';
                }
                else
                {
                    $option.='<option value="'.intval($series_row->series_id).'">'.esc_html($series_row->series_name).'</option>';
                }
                
            }
            echo $first.$option.'</select></td></tr>';
        }
        
            echo'<tr><th scope="row">Speaker</th><td>';
            echo church_admin_autocomplete('speaker','friends','to', NULL);
            echo'</td></tr>';
        
        if(empty($current_data->pub_date))$current_data->pub_date=date('Y-m-d H:i:s');
        echo'<tr><th scope="row">Publication Date</th><td><input type="text" name="pub_date" value="'.esc_html($current_data->pub_date).'"/></td></tr>';
         echo'<tr><th scope="row">'.__('Video URL','church-admin').'</th><td><input type="text" name="video_url" id="video_url"';
		if(!empty($current_data->video_url))echo' value="'.esc_url($current_data->video_url).'" ';
		echo'/>'.__('Add [VIDEO_URL] to your sermon files template to display','church-admin').'</td></tr>';
		echo'<tr><th scope="row">'.__('Blog the sermon','church-admin').'</th><td><input type="checkbox" checked name="blog" value="1"/></td></tr>';
        echo '<tr><th scope="row">&nbsp;</th><td><input type="hidden" name="save_file" value="save_file"/><input type="submit" class="primary-button" value="'.__('Save File','church-admin').'"/></td></tr></table></form>';
    }//form
    
    
}
function ca_podcast_file_delete($file_name=NULL)
{
	$upload_dir = wp_upload_dir();
	$path=$upload_dir['basedir'].'/sermons/';
	$url=content_url().'/uploads/sermons/';
    if($file_name &&is_file($path.basename($file_name)))
    {
        unlink($path.basename($file_name));
        echo'<div class="notice notice-success inline"><p>'.esc_html(basename($file_name)).' '.__('deleted','church-admin').'</p></div>';
        ca_podcast_check_files();
    }
}


function ca_podcast_xml()
{
    global $wpdb,$ca_podcast_settings;
    $settings=get_option('ca_podcast_settings');
	$upload_dir = wp_upload_dir();
	$path=$upload_dir['basedir'].'/sermons/';
	$url=content_url().'/uploads/sermons/';
     $results=$wpdb->get_results('SELECT DATE_FORMAT(a.pub_date,"%a, %d %b %Y %T") AS publ_date,a.*,c.series_name AS series_name FROM '. CA_FIL_TBL.' a, '.CA_SERM_TBL.' c WHERE a.private="0" AND a.series_id=c.series_id ORDER BY pub_date DESC');
    if(!empty($results)&&!empty($settings['title']))
    {
 
        //CONSTRUCT RSS FEED HEADERS
        $output = '<rss xmlns:itunes="http://www.itunes.com/dtds/podcast-1.0.dtd" version="2.0">';
        $output .= '<channel>';
        $output .= '<title>'.ent2ncr($settings['title']).'</title>';
        $output .= '<link>'.ent2ncr($url).'podcast.xml'.'</link>';
        $output .= '<language>'.ent2ncr($settings['language']).'</language>';
        $output .= '<copyright>&#x2117; &amp; &#xA9; '.date('Y').' '.ent2ncr($settings['copyright']).'</copyright>';
        $output .= '<itunes:subtitle>'.ent2ncr($settings['subtitle']).'</itunes:subtitle>';
        $output .= '<itunes:author>'.ent2ncr($settings['author']).'</itunes:author>';
        $output .= '<itunes:summary>'.ent2ncr($settings['summary']).'</itunes:summary>';
        $output .= '<description>'.ent2ncr($settings['description']).'</description>';
        $output .= '<itunes:owner>';
        if(!empty($settings['owner_name']))$output .= '<itunes:name>'.ent2ncr($settings['owner_name']).'</itunes:name>';
        if(!empty($settings['owner_email']))$output .= '<itunes:email>'.ent2ncr($settings['owner_email']).'</itunes:email>';
        $output .= '</itunes:owner>';
        $output .= '<itunes:explicit>'.ent2ncr($settings['explicit']).'</itunes:explicit>';
       
        $output .='<itunes:image href="'.ent2ncr($settings['image']).'" />';
        if(!empty($settings['category']))
        {
            $cat=explode("-",$settings['category']);
            if(count($cat)==2){$output .='<itunes:category text="'.trim(ent2ncr($cat[0])).'"><itunes:category text="'.ent2ncr($cat[1]).'"/></itunes:category>';}
            elseif(count($cat)==1){$output .='<itunes:category text="'.trim(ent2ncr($cat[0])).'"/>';}
            
        }
       
            //BODY OF RSS FEED
        foreach($results AS $row)
        {
            //get speakers
            
            $names=church_admin_get_people($row->speaker);
          
            //end get speakers
            $service=$wpdb->get_var('SELECT CONCAT_WS(" ",service_name,service_time) FROM '.CA_SER_TBL.' WHERE service_id="'.esc_sql($row->service_id).'"');
            $output .= '<item>';
            $output .= '<title>'.ent2ncr($row->file_title).'</title>';
            $output .= '<itunes:author>'.ent2ncr($names).'</itunes:author>';
            $output .= '<itunes:subtitle>'.ent2ncr($row->file_subtitle).'</itunes:subtitle>';
            $output .= '<itunes:summary>'.ent2ncr($row->file_description).'</itunes:summary>';
            //$output .=  '<itunes:image href="'..'" />';
            if(!empty($row->file_name) &&file_exists($path.$row->file_name)){$output .= '<enclosure url="'.ent2ncr($url.$row->file_name).'" length="'.filesize($path.$row->file_name).'" type="audio/mpeg" />';$output .= '<guid>'.ent2ncr($url.$row->file_name).'</guid>';}else{$output .= '<enclosure url="'.ent2ncr($row->external_file).'" length="" type="audio/mpeg" />';$output .= '<guid>'.ent2ncr($row->external_file).'</guid>';}
            
            $output .= '<pubDate>'.ent2ncr($row->publ_date.' '.date('O')).'</pubDate>';
            $output .= '<itunes:duration>'.ent2ncr($row->length).'</itunes:duration>';
            //$output .= '<itunes:keywords></itunes:keywords>';
            $output .= '</item>';
        }
        //CLOSE RSS FEED
        $output .= '</channel>';
        $output .= '</rss>';
        
        //SEND COMPLETE RSS FEED TO podcast xml file
        $fp = fopen($path.'podcast.xml', 'w');
        fwrite($fp, $output);
        fclose($fp);
        return TRUE;
    }//end results
}
function church_admin_latest_sermons_widget_control()
{

    //get saved options
    $options=get_option('church_admin_widget');
    //handle user input
    if(!empty($_POST['latest_sermons_widget_submit']))
    {
        $options['title']=strip_tags(stripslashes($_POST['title']));
        if(ctype_digit($_POST['sermons'])){$options['sermons']=$_POST['sermons'];}else{$options['sermons']='5';}
        
        update_option('church_admin_latest_sermons_widget',$options);
    }
    church_admin_latest_sermons_widget_control_form();
}

function church_admin_latest_sermons_widget_control_form()
{
    global $wpdb;

    
    $option=get_option('church_admin_latest_sermons_widget');
    echo '<p><label for="title">'.__('Title','church-admin').':</label><input type="text" name="title" value="'.esc_html($option['title']).'" /></p>';
   
    echo '<p><label for="howmany">'.__('How many sermons to show','church-admin').'?</label><select name="sermons">';
    if(isset($option['sermons'])) echo '<option value="'.esc_html($option['sermons']).'">'.esc_html($option['sermons']).'</option>';
    for($x=1;$x<=10;$x++){echo '<option value="'.$x.'">'.$x.'</option>';}
    echo'</select><input type="hidden" name="latest_sermons_widget_submit" value="1"/>';
}

function church_admin_latest_sermons_widget_output($limit=5,$title)
{
	global $wpdb;
	$upload_dir = wp_upload_dir();
	$path=$upload_dir['basedir'].'/sermons/';
	$url=content_url().'/uploads/sermons/';
	;
	$out='<div class="church-admin-sermons-widget">';
	$ca_podcast_settings=get_option('ca_podcast_settings');
	
	if(!empty($ca_podcast_settings['link']))$out.='<p><a title="Download on Itunes" href="'.$ca_podcast_settings['itunes_link'].'">
<img  alt="badge_itunes-lrg" src="'.plugins_url('/images/badge_itunes-lrg.png',dirname(__FILE__) ).'" width="110" height="40" /></a></p>';
	$options=get_option('church_admin_latest_sermons_widget');
	
	$limit=$options['sermons'];
	if(empty($limit))$limit=5;
	$sermons=$wpdb->get_results('SELECT a.*,b.* FROM '.CA_FIL_TBL.' a, '.CA_SERM_TBL.' b WHERE a.series_id=b.series_id ORDER BY a.pub_date DESC LIMIT '.$limit);
	if(!empty($sermons))
	{
		foreach($sermons AS $sermon)
		{
			$speaker=church_admin_get_people($sermon->speaker);
			if(!empty($sermon->file_name)){$out.='<p><a href="'.esc_url($url.$sermon->file_name).'"  title="'.esc_html($sermon->file_title).'">'.esc_html($sermon->file_title).'</a>';}else{$out.='<p><a href="'.esc_url($sermon->external_file).'"  title="'.esc_html($sermon->file_title).'">'.esc_html($sermon->file_title).'</a>';}
			$out.='<br/>By '.esc_html($speaker).' on '.mysql2date(get_option('date_format'),$sermon->pub_date).'<br/>';
	
			$out.='<audio class="sermonmp3" id="'.$sermon->file_id.'" src="'.esc_url(CA_POD_URL.$sermon->file_name).'" preload="none"></audio><br/>'; 
			
		}
	}



$out.='</div>';
return $out;

}

function church_admin_handle_podcast_image($settings)
{
	if(empty($settings['thumbnail_id'])&&!empty($settings['image']))
			{
				$path = parse_url($settings['image']);
				$image=$_SERVER['DOCUMENT_ROOT'] . $path['path'];
				
				$filetype = wp_check_filetype( basename( $image), null );
				$filetitle = preg_replace('/\.[^.]+$/', '', basename( $image) );
				$filename = $filetitle . '.' . $filetype['ext'];
				$upload_dir = wp_upload_dir();
				/**
				* Check if the filename already exist in the directory and rename the
				* file if necessary
				*/
				$i = 0;
				while ( file_exists( $upload_dir['path'] .'/' . $filename ) )
				{
					$filename = $filetitle . '_' . $i . '.' . $filetype['ext'];
					$i++;
				}
				$filedest = $upload_dir['path'] . '/' . $filename;
			
				if(file_exists($image))
				{	
					copy($image, $filedest);
					$attachment = array('post_mime_type' => $filetype['type'],'post_title' => $filetitle,'post_content' => '','post_status' => 'inherit');
					$attachment_id = wp_insert_attachment( $attachment, $filedest );
					$settings['thumbnail_id']=$attachment_id;
					update_option('ca_podcast_settings',$settings);
					require_once( ABSPATH . "wp-admin" . '/includes/image.php' );
					$attach_data = wp_generate_attachment_metadata( $attachment_id, $filedest );
					wp_update_attachment_metadata( $attachment_id,  $attach_data );
				}
			}
			return $settings;
}
?>