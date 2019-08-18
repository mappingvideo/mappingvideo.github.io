<?php
defined('_VALID') or die('Restricted Access!');

define('DEFAULT_CATEGORY', 1);

require $config['BASE_DIR'].'/classes/curl.class.php';
require $config['BASE_DIR'].'/modules/embedder/config.php';

Auth::checkAdmin();

// lets defined our embed width/height here
define('E_WIDTH', 630);
define('E_HEIGHT', 495);

$embed		= array('url' => '', 'category' => '', 'username' => '', 'status' => 1);
$categories	= get_categories();
if (isset($_POST['membed_videos'])) {
	require $config['BASE_DIR'].'/classes/filter.class.php';
	require $config['BASE_DIR'].'/classes/validation.class.php';
	$filter		= new VFilter();
	$valid		= new VValidation();
	$url		= $filter->get('url');
	$username	= $filter->get('username');
	$category	= $filter->get('category');
	$status		= $filter->get('status');
	
	if ($url == '') {
		$errors[] 		= 'URL field cannot be left blank!';
	} else {
	    $parts = explode('/', str_replace(array('http://www.', 'http://'), '', $url));
        if (isset($parts['0'])) {
      		$site = $parts['0'];
            if (!isset($sites[$site])) {
          		$errors[] = 'Invalid url! Supported sites: '.implode(', ', $this->gcfg['sites']).'!';
            } else {
				$site			= $sites[$site];
				$embed['url']	= $url;
            }
        } else {
			$errors[] = 'Failed to get site identifier from url!';
		}
	}
	
	if ($username == '') {
		$errors[]	= 'Username field cannot be left blank!';
	} else {
		$rs = $conn->execute("SELECT UID FROM signup WHERE username = '".mysql_real_escape_string($username)."' LIMIT 1");
		if (!$conn->Affected_Rows()) {
			$errors[] = 'Username is not a valid username on this system!';
		} else {
			$user_id			= (int) $rs->fields['UID'];
			$embed['username'] 	= $username;
		}
	}
	
	if ($category != '') {
		$embed['category'] 	= (int) $category;
	}
	
	$embed['status'] = (int) $status;
	
	if (!$errors) {
		$graber_file	= $config['BASE_DIR'].'/modules/embedder/sites/'.$site.'.php';
		$graber_class	= 'MEmbed_'.$site;
		if (file_exists($graber_file) && is_file($graber_file)) {
			require $graber_file;
			$graber	= new $graber_class($url, $user_id, $category, $status);
			if ($graber->get_videos()) {
				$video_added	= $graber->video_added;
				$video_already	= $graber->video_already;
				$message		= 'Added '.$video_added.' videos!';
				if ($video_already !== 0) {
					$message .= ' '.$video_already.' videos are already added to your site!';
				}
				
				$messages[] = $message;
			} else {
				$errors	= array_merge($errors, $graber->errors);
			}
		} else {
			$errors[]	= 'Failed to load '.$site.' graber file!';
		}
	}
}

function duration_to_seconds($duration)
{
    $duration   = explode(':', $duration);
    $minutes    = sprintf('%01d', $duration['0']);
    $seconds    = sprintf('%01d', $duration['1']);
            
	return (($minutes * 60) + $seconds);
}

function add_video($video, $category)
{
	global $categories, $config, $conn;
	
        if ( $category == 0 )
        {
           
           $category = match_category($categories, $video['category'], $video['title'], $video['desc'], $video['tags']);
        }
	$sql	= "INSERT INTO video
               SET UID = ".$video['user_id'].", 
                   title = '".mysql_real_escape_string($video['title'])."',  
                   description = '".mysql_real_escape_string($video['desc'])."',  
                   keyword = '".mysql_real_escape_string($video['tags'])."',  
                   channel = ".$category.",  
                   duration = ".$video['duration'].",  
                   thumbs = ".(count($video['thumbs'])-1).",  
                   embed_code = '".mysql_real_escape_string($video['embed'])."',
				   addtime = ".time().",
				   adddate = '".date('Y-m-d')."',
				   vkey = '" .mt_rand(). "',
				   type = 'public',
				   active = '0'";
	$conn->execute($sql);
	if ($conn->Affected_Rows()) {
		$VID 		= mysql_insert_id();
		$thumb_dir  = $config['BASE_DIR'].'/media/videos/tmb/'.$VID;
        $count      = 1;
        $valid      = 0;
		
		$curl = new VCurl();
		if (mkdir($thumb_dir)) {
      		foreach ($video['thumbs'] as $thumb) {
          		if ($curl->saveToFile($thumb, $thumb_dir.'/'.$count.'.jpg')) {
          			++$valid;
              		++$count;
          		}
      		}
                    
            if ($valid !== 0) {
				$vkey = substr(md5($VID),11,20);
				$conn->execute("UPDATE video SET active = '".$video['status']."', thumbs = ".$valid.", vkey = '".$vkey."' WHERE VID = ".$VID." LIMIT 1");
				$conn->execute("INSERT INTO mass_embedder SET site = '".$video['site']."', url = '".$video['url']."'");
                return true;
			}	
		}
	} else {
		return false;
	}
}

function clean_html($html)
{
	$html   = str_replace(array("\n", "\r"), '', $html);
    $html   = preg_replace('/\s\s+/', ' ', $html);
        
    return $html;
}

function match_category($categories, $name, $title, $description, $tags)
{
/*
	foreach ($categories as $category) {
  		$category_name  = $category['name'];
        if ($name != '') {
      		if (stripos($category_name, $name) !== FALSE) {
                echo "returned true: ".$category['CHID'];
                return $category['CHID'];
            }
        }       
        else
       {
         return 1;
       }

       } 

*/


    foreach ($categories as $category) {
      $category_name  = $category['name'];
      if ($name != '') {
         if (stripos($category_name, $name) !== FALSE || stripos($name, $category_name) !== FALSE) {
         return $category['CHID'];
         }else {
          $name = '';
         } 
      }
      else if ($name == '' && $title != '') {
         if (stripos($category_name, $title) !== FALSE || stripos($title, $category_name) !== FALSE) {
         return $category['CHID'];
         }else {
         $title = '';
         }
      }
      else if ($name == '' && $title == "" && $description != '') {
         if (stripos($category_name, $description) !== FALSE || stripos($description, $category_name) !== FALSE) {
         return $category['CHID'];
         }else {
         $description = '';
         }
      }
      else if ($name == '' && $title == "" && $description == "" && $tags != '') {
         if (stripos($category_name, $tags) !== FALSE || stripos($tags, $category_name) !== FALSE) {
         return $category['CHID'];
         }else {
         return 1;
         }
      }
      else { return 1; }     
    }
	

}

function already_added($site, $url)
{
	global $conn;
	
	$conn->execute("SELECT site
	                FROM mass_embedder
					WHERE site = '".mysql_real_escape_string($site)."'
					AND url = '".mysql_real_escape_string($url)."'
					LIMIT 1");
	
	return $conn->Affected_Rows();
}

$smarty->assign('embed', $embed);
$smarty->assign('categories', $categories);
?>
