<?php

function church_admin_recent_people_activity()
{
    global $wpdb,$people_type;
	$member_type=church_admin_member_type_array();
    $out='';
        
    // number of total rows in the database
    require_once(plugin_dir_path(dirname(__FILE__)).'includes/pagination.class.php');
    $items=$wpdb->get_var('SELECT COUNT(people_id) FROM '.CA_PEO_TBL);
    if($items > 0)
    {
        echo '<hr/><h2><a id="recent_people">'.__('Recent People Activity','church-admin').'</a></h2>';
        echo'<p><a href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=church_admin_email_follow_up_activity','email_funnels').'">'.__('Email newly assigned follow-up activity','church-admin').'</a></p>';
	$p = new pagination;
	$p->items($items);
	$p->limit(get_option('church_admin_page_limit')); // Limit entries per page
	$p->target("admin.php?page=church_admin/index.php&tab=address&action=church_admin_people_activity");
	if(!isset($p->paging))$p->paging=1; 
	if(!isset($_GET[$p->paging]))$_GET[$p->paging]=1;
	$p->currentPage(intval($_GET[$p->paging])); // Gets and validates the current page
	$p->calculate(); // Calculates what to show
	$p->parameterName('paging');
	$p->adjacents(1); //No. of page away from the current page
	if(!isset($_GET['paging']))
	{
	    $p->page = 1;
	}
	else
	{
	    $p->page = intval($_GET['paging']);
	}
        //Query for limit paging
	$limit = "LIMIT " . ($p->page - 1) * $p->limit  . ", " . $p->limit;
        $results=$wpdb->get_results('SELECT * FROM '.CA_PEO_TBL.' ORDER BY last_updated DESC '.$limit);
        if($results)
        {
            
            // Pagination
            
            echo  '<div class="tablenav"><div class="tablenav-pages">';
            echo $p->show();  
            echo  '</div></div>';
            //prepare table
            
            echo '<table class="widefat striped"><thead><tr><th>'.__('Edit','church-admin').'</th><th>'.__('Delete','church-admin').'</th><th>'.__('Name','church-admin').'</th><th>'.__('Member Level','church-admin').'</th><th>'.__('Follow Up Action','church-admin').'</th><th>'.__('Mobile','church-admin').'</th><th>'.__('Email','church-admin').'</th><th>'.__('Last Updated','church-admin').'</th></tr></thead><tfoot><th>'.__('Edit','church-admin').'</th><th>'.__('Delete','church-admin').'</th><th>'.__('Name','church-admin').'</th><th>'.__('Member Level','church-admin').'</th><th>'.__('Follow Up Action','church-admin').'</th><th>'.__('Mobile','church-admin').'</th><th>'.__('Email','church-admin').'</th><th>'.__('Last Updated','church-admin').'</th></tfoot><tbody>';
            foreach($results AS $row)
            {
                $fun_display='';
                $sql='SELECT a.*,b.action,CONCAT_WS(" ",c.first_name,c.last_name) AS name FROM '.CA_FP_TBL.' a, '.CA_FUN_TBL.' b,'.CA_PEO_TBL.' c WHERE a.people_id="'.esc_sql($row->people_id).'" AND a.member_type_id="'.esc_sql($row->member_type_id).'" AND b.funnel_id=a.funnel_id AND c.people_id=a.assign_id';
                
                $funnel=$wpdb->get_row($sql);
                if($funnel)
                {//funnel has been assigned already
                    $fun_display=sprintf(__('%1$s assigned to %2$s on %3$s','church-admin'), $funnel->action,$funnel->name,mysql2date(get_option('date_format'),$funnel->assigned_date));
                    if($funnel->completion_date!='0000-00-00')$fun_display.=sprintf(__('completed on %1$s','church-admin'),mysql2date(get_option('date_format'),$funnel->completion_date));
                }
                else
                {
                    $funnel_id=$wpdb->get_var('SELECT funnel_id FROM '.CA_FUN_TBL.' WHERE member_type_id="'.esc_sql($row->member_type_id).'" LIMIT 1');
                    if($funnel_id){$fun_display.=church_admin_funnel_assign($row->people_id,$funnel_id,$row->member_type_id);}else{$fun_display.='';}
               
                }
                $edit='<a href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=edit_people&amp;people_id='.$row->people_id,'edit_people').'">'.__('Edit','church-admin').'</a>';
                $delete='<a onclick="return confirm(\''.__('Are you sure?','church-admin').'\');" href="'.wp_nonce_url('admin.php?page=church_admin/index.php&amp;action=delete_people&amp;people_id='.$row->people_id,'delete_people').'">'.__('Delete','church-admin').'</a>';
                echo '<tr><td>'.$edit.'</td><td>'.$delete.'</td><td>'.esc_html($row->first_name).' <strong>'. esc_html($row->last_name).'</strong></td><td>'.$member_type[$row->member_type_id].'</td><td>'.$fun_display.'</td><td>'.esc_html($row->mobile).'</td><td>';
                echo '';
                //only provide email link if actually an email
                if(is_email($row->email)) {echo '<a href="mailto:'.$row->email.'">'.esc_html($row->email).'</a>';}else{echo esc_html($row->email);}
                echo '</td><td>'.mysql2date(get_option('date_format'),$row->last_updated).'</td></tr>';

            }
            echo '</tbody></table>';
            // Pagination
            
            echo  '<div class="tablenav"><div class="tablenav-pages">';
            echo $p->show();  
            echo  '</div></div>';
        }
    }
   
    
    
}
function church_admin_funnel_assign($people_id,$funnel_id,$member_type_id)
{
       //returns form to assign someone to action a particular funnel for a particular person
    global $wpdb;
	$fun_display='';
    $funnel_details=$wpdb->get_row('SELECT * FROM '.CA_FUN_TBL.' WHERE funnel_id="'.esc_sql($funnel_id).'"');
    if($funnel_details)
    {
 
        $people=$wpdb->get_results('SELECT CONCAT_WS(" ",a.first_name,a.last_name) AS name, a.people_id AS people_id FROM '.CA_PEO_TBL.' a,'.CA_MET_TBL.' b WHERE b.meta_type="ministry" AND b.ID="'.esc_sql($funnel_details->department_id).'" AND b.people_id=a.people_id ORDER BY a.last_name');
        if($people)
        {//people available to assign to
            $fun_display.='<form action="admin.php?page=church_admin/index.php&amp;action=church_admin_assign_funnel" method="post">';
            $fun_display.='<input type="hidden" name="people_id" value="'.intval($people_id).'"/>';
            $fun_display.='<input type="hidden" name="funnel_id" value="'.intval($funnel_id).'"/>';
            $fun_display.='<input type="hidden" name="member_type_id" value="'.intval($member_type_id).'"/>';
            $fun_display.='<p>'.sprintf(__('Assign %1$s to ','church-admin'),esc_html($funnel_details->action)).': <select name="assign_id" onchange="this.form.submit()">';
            $fun_display.='<option value="">'.__('Select someone...','church-admin').'</option>';
            foreach ($people AS $person)
            {
                $fun_display.='<option value="'.intval($person->people_id).'">'.esc_html($person->name).'</option>';
            }
            $fun_display.='</select></form>';
        }
    }
    return $fun_display;
}

function church_admin_assign_funnel()
{
    //uses form data to adjust persons funnel data
    global $wpdb;
    
    ;
    
    if(empty($_POST['people_id']) || empty($_POST['funnel_id']) || empty($_POST['assign_id']) || !ctype_digit($_POST['people_id'])||!ctype_digit($_POST['funnel_id'])||!ctype_digit($_POST['assign_id']))
    {
        echo'<div class="notice notice-success inline"><p>'.__("Couldn't process data",'church-admin').'</p></strong></div>'.church_admin_recent_people_activity();
    }
    else
    {
        $funnel_id=(int)$_POST['funnel_id'];
        $people_id=(int)$_POST['people_id'];
        $assign_id=(int)$_POST['assign_id'];
        $member_type_id=(int)$_POST['member_type_id'];
        $assign_date=date('Y-m-d');
        $sql='INSERT INTO '.CA_FP_TBL .'(funnel_id,people_id,member_type_id,assign_id,assigned_date,completion_date)VALUES("'.$funnel_id.'","'.$people_id.'","'.$member_type_id.'","'.$assign_id.'","'.$assign_date.'","0000-00-00")';
        echo $sql;
        $wpdb->query($sql);
        echo'<div class="notice notice-success inline"><p><strong>'.__('Follow Up Funnel Assigned','church-admin').'</strong></p></div>';
        church_admin_recent_people_activity();
    }
}

function church_admin_email_follow_up_activity()
{
    global $wpdb;
    add_filter('wp_mail_content_type',create_function('', 'return "text/html";'));
    //grab ids of people with assigned follow-up actions
    $sql='SELECT DISTINCT assign_id FROM '.CA_FP_TBL.' WHERE email="0000-00-00" ';
    
    $results=$wpdb->get_results($sql);
if($results)
{
        
    echo'<div class="notice notice-success inline"><p><strong>'.__('Follow Up activities emailed to...','church-admin').'<br/>';
    foreach($results AS $row)
    {
        
        $assign=$wpdb->get_row('SELECT * FROM '.CA_PEO_TBL.' WHERE people_id="'.esc_sql($row->assign_id).'"');
        $sql='SELECT * FROM '.CA_FP_TBL.'  LEFT JOIN '.CA_FUN_TBL.' ON '.CA_FP_TBL.'.funnel_id = '.CA_FUN_TBL.'.funnel_id LEFT JOIN '.CA_PEO_TBL.' ON '.CA_FP_TBL.'.people_id = '.CA_PEO_TBL.'.people_id LEFT JOIN '.CA_HOU_TBL.' ON '.CA_PEO_TBL.'.household_id = '.CA_HOU_TBL.'.household_id WHERE '.CA_FP_TBL.'.assign_id="'.$row->assign_id.'" AND '.CA_FP_TBL.'.email="0000-00-00"';
        
        $re=$wpdb->get_results($sql);
        $message='<p>Hi '.$assign->first_name.' '.$assign->last_name.',</p><p>'.__("You've been assigned some follow up actions",'church-admin').'</p>';
        foreach($re AS $f_row)
        {
            $message.='<h2>'.$f_row->action.' '.__('assigned on','church-admin').' '.mysql2date(get_option('date_format'),$f_row->assigned_date).'</h2>';
            $message.='<table><tr><td>Name</td><td>'.esc_html($f_row->first_name.' '.$f_row->last_name).'</td></tr>';
            if(!empty($f_row->address))$message.='<tr><td>'.__('Address','church-admin').'</td><td>'.esc_html($f_row->address).'</td></tr>';
            if(!empty($f_row->email)&&is_email($f_row->email))$message.='<tr><td>'.__('Email','church-admin').'</td><td><a href="mailto:'.$f_row->email.'">'.$f_row->email.'</a></td></tr>';
            if(!empty($f_row->mobile))$message.='<tr><td>'.__('Mobile','church-admin').'</td><td>'.esc_html($f_row->mobile).'</td></tr>';
            if(!empty($f_row->phone))$message.='<tr><td>'.__('Phone','church-admin').'</td><td>'.esc_html($f_row->phone).'</td></tr>';
           $message.='</table>';
            
        }
        echo esc_html($assign->first_name.' '.$assign->last_name).'<br/>';
        wp_mail($assign->email,__("You've been assigned some follow up tasks",'church-admin'),$message);
        $wpdb->query('UPDATE '.CA_FP_TBL.' SET email="'.date('Y-m-d').'" WHERE assign_id="'.esc_sql($assign->people_id).'" AND email="0000-00-00"');
    }
    echo'</strong></p></div>';
}
else{echo'<div class="notice notice-success inline"><p><strong>'.__('Follow Up activities did not need to be emailed','church-admin').'</strong></p></div>';}
church_admin_recent_people_activity();
}
?>