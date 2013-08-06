<?php

define('KURE_ROOT', '');

// Autoload any class which is used in this file
function __autoload($class) {
	include KURE_ROOT . 'classes/' . $class . '.php';
}

$config = Engine::get_config();

require_once KURE_ROOT . 'functions.php';
session_start();

// logout
if(isset($_GET['logout'])) {
	
	unset($_SESSION['admin']);
	session_destroy();
	header('Location: ' . KURE_ROOT . 'index.php');
	
}

// login
if(!isset($_SESSION['admin']) || $_SESSION['admin'] != $config->admin_password) {
	
	if(isset($_POST['login'])) {
		
		if(md5($_POST['password']) == $config->admin_password) {
			
			$_SESSION['admin'] = $config->admin_password;
			header('Location: ' . KURE_ROOT . 'admin.php');
			
		} else {
			
			Template::run('admin_header');
			print('<div style="position: relative; left: 8px;">');
			Engine::error('<b><font style="color:red;">Oops: </font>Invalid Password...</b>', false);

		}
		
	} else {
		
Template::run('admin_header');
		
	}
	
	print('<div style="position: relative; left: 8px;">');
	
	if(isset($_SESSION['admin'])) 
		print('<b><font style="color:red;">Oops: </font>Session Invalid...</b>');
	
	print '<span class="sitetitle">Enter Password</span>';
	print '<form action="?" method="post">';
	print '<input type="password" name="password">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" name="login" value="Login"></form>';
	
	Engine::quit(); // don't allow any further access of administration unless logged in
	
}

Template::run('admin_header');

if(isset($_GET['config'])) {
	
	print '<span class="pagesub">Settings</span><br/><br/>' . "\n";
	
	if(isset($_POST['config_submit'])) {
		
		$config->blog_name             = $_POST['blog_name'];
		$config->entries_per_page      = $_POST['entries_per_page'];
		$config->abc_entries           = $_POST['abc_entries'];
		$config->show_admin_link       = $_POST['show_admin_link'];
		
		if(!$config->save())
			Engine::quit('<span class="error">Couldn\'t write to <tt>config.php</tt>; check permissions and try again.</span>');
		
		print '<span class="success">Configuration saved.</span>';
		
	} else {
		
		print '<form action="?config" method="post">';
		
		print 'Blog Title<br/><input type="text" name="blog_name" value="' . $config->blog_name . '" class="form_text"><br/><br/>';
		print 'Blog Posts Per Page&nbsp;&nbsp;&nbsp;&nbsp;(setting this to 0 will break the blog)<br/><input type="text" name="entries_per_page" value="' . $config->entries_per_page . '" class="form_text" size="3"> <span class="note"></span><br/><br/>';
		
		print 'Blog Post Order<br/><select name="abc_entries">';
		print '<option value="true"' . ($config->abc_entries ? 'selected' : '') . '>Alphabetical</option>';
		print '<option value="false"' . (!$config->abc_entries ? 'selected' : '') . '>Date descending</option>';
		print '</select><br/><br/>';
		
		print 'Show The Admin CP Link On The Main Page<br/><select name="show_admin_link">';
		print '<option value="true"' . ($config->show_admin_link ? 'selected' : '') . '>Yes</option>';
		print '<option value="false"' . (!$config->show_admin_link ? 'selected' : '') . '>No</option>';
		print '</select><br/><br/>';
		
		print '<input type="submit" name="config_submit" value="save" class="form_submit">';
		
	}
	
} elseif(isset($_GET['plugins'])) {
	
	print('<span class="pagesub">Extensions</span><br/>' . "\n");
	print('<div style="position: relative; left: 8px;">');
	Engine::plug('admplugins', 'listing');
	print('</div>');
	
} elseif(isset($_GET['plugin'])) {
	
	// blank page for plugins to use as a config/about page
	Engine::plug('admplugins', 'page');
	
} elseif(isset($_GET['templates'])) {
	
	print('<span class="pagesub">Skins</span><br/><br/>' . "\n");
	
	if(isset($_POST['template_submit'])) {
		
		$config->template = $_POST['template'];
		
		if($config->save())
			print('<b><font style="color:green;">Success: </font>Skin Changed...</b><BR>');
		else
			Engine::quit('Error writing to <tt>config.php</tt>. Check permissions and try again.');
		
	}
	
	print('<form action="?templates" method="post">' . "\n");
	$templates = glob(KURE_ROOT . 'templates/*', GLOB_ONLYDIR);
	
	foreach($templates as $template) {
		
		$template = str_replace(KURE_ROOT . 'templates/', '', $template);
		print('<input type="radio" name="template" value="' . $template . '"');
		
		if($template == $config->template)
			print(' checked');
		
		print('> <tt>' . $template. '</tt><br/>' . "\n");
		
	}
	
	print('<br/><input type="submit" name="template_submit" value="save" class="form_submit"></form>');
	
} elseif(isset($_GET['create'])) {
	
	print('<span class="pagesub">New Post</span><br/><br/>' . "\n");
	
	if(isset($_POST['submit_entry'])) {
	
		if(create_entry($_POST['title'], $_POST['content']))
			print('<span class="success">Entry created.</span>');
		
	} else {
		
		Engine::plug('admcreate', 'top');
		print('<form action="?create" method="post">' . "\n");
		print('Blog Title<br/><input class="form_text" name="title" size="50" type="text"><br/><br/>' . "\n");
		Engine::plug('admcreate', 'title_after');
		print('Blog Content&nbsp;&nbsp;&nbsp;&nbsp;(HTML and BBCodes are accepted)<br/><textarea class="form_textarea" cols="80" name="content" rows="12"></textarea><br/><br/>' . "\n");
		Engine::plug('admcreate', 'content_after');
		print('<br><br>' . "\n");
		print('<input name="submit_entry" type="submit" value="Submit Post">' . "\n");
		Engine::plug('admcreate', 'button_after');
		print('</form>' . "\n\n");
		
	}

} elseif(isset($_GET['modify'])) {
	
	print('<span class="pagesub">Edit Post</span><br/><br/>' . "\n");
	
	if($_GET['modify'] != null) {
		
		if(isset($_POST['submit'])) {
			
			$oldname = $_POST['oldfile'];
			$oldname = str_replace('entries/', '', $oldname);
			
			if(!delete_entry($oldname))
				Engine::quit('Old entry could not be removed. Check permissions and try again.');
			
			if(create_entry($_POST['title'], $_POST['content'], $_POST['type']))
				print '<span class="success">Entry saved.</span>';
			
		} else {
			
			$oldtitle = str_replace('entries/', '', $_GET['modify']);
			
			$oldtitle = deparse_title($oldtitle);
			$oldcontent = file_get_contents(KURE_ROOT . 'entries/' . $_GET['modify'] . '.txt');
			
			Engine::plug('admmodify', 'top');
			print('<form action="?modify=submit" method="post">' . "\n");
			print('Blog Title<br/><input class="form_text" name="title" size="50" type="text" value="' . $oldtitle . '"><br><br>' . "\n");
			Engine::plug('admmodify', 'title_after');
			print('Blog Content&nbsp;&nbsp;&nbsp;&nbsp;(HTML and BBCodes are accepted)<br/><textarea class="form_textarea" cols="80" name="content" rows="12">' . $oldcontent . '</textarea><br><br>' . "\n");
			Engine::plug('admmodify', 'content_after');
			print('<br><br>' . "\n");
			print('<input type="hidden" name="oldfile" value="' . $_GET['modify'] . '">' . "\n");
			print('<input name="submit" type="submit" value="Save Changes">' . "\n");
			Engine::plug('admmodify', 'button_after');
			print('</form>' . "\n\n");
			
		}
		
	} else {
		
		$entries = glob(KURE_ROOT . 'entries/*.txt');
		
		usort($entries, 'sort_by_mtime');
		
		foreach($entries as $entry) {
			
			$entry = str_replace(KURE_ROOT . 'entries/', '', $entry);
			$entry = str_replace('.txt', '', $entry);
			$entry_title = deparse_title($entry);
			print '&nbsp;&nbsp;<a href="?del=' . $entry . '" class="small"><font style="background-color:pink;">DELETE</font></a>&nbsp;<a href="?modify=' . $entry . '">' . $entry_title . '</a><br/>';
			
		}
		
	}
	
} elseif(isset($_GET['del'])) {
	
	$title = $_GET['del'];
	
	if(isset($_POST['confirm_delete'])) {
		
		if(delete_entry($title))
			print('<span class="success">Entry deleted.</span>');
		else
			print('<span class="error">Couldn\'t delete entry <tt>' . deparse_title($title) . '</tt>. Check permissions and try again.</span>');
		
	} else {
		
		print('<span class="pagesub">Delete Entry</span><br/><br/>' . "\n");
		print('Are you sure you want to delete the entry <b><tt>' . deparse_title($title) . '</tt></b>? This cannot be undone.<br/><br/>' . "\n");
		print('<div align="right"><form action="?del=' . $_GET['del'] . '" method="post"><input type="submit" name="confirm_delete" value="Yes, delete this entry"></form>' . "\n");
		print('<a href="?modify" class="navitem">Go back</a></div>');
		
	}
	
} elseif(isset($_GET['password'])) {
	
	print('<span class="pagesub">Change Password</span><br/><br/>' . "\n");
	
	if(isset($_POST['pass_submit'])) {
		
		if($_POST['newpass1'] != $_POST['newpass2'] || $_POST['newpass1'] == "")
			Engine::quit('Passwords did not match or were not entered. <a href="?password">Try again</a>.');
		if(md5($_POST['curpass']) != $config->admin_password)
			Engine::quit('Incorrect current password. <a href="?password">Try again</a>.');
		
		$config->admin_password = md5($_POST['newpass1']);

		if($config->save())
			print('<span class="success">Password changed.</span>');
		
	} else {
		
		print '<form action="?password" method="post">';
		print 'Current Password<br/><input type="password" name="curpass" class="form_text"><br/><br/>';
		print '<hr>';
		print 'New Password<br/><input type="password" name="newpass1" class="form_text"><br/><br/>';
		print 'Confirm New Password<br/><input type="password" name="newpass2" class="form_text"><br/><br/>';
		print '<input type="submit" name="pass_submit" value="Change Password"></form>';
		
	}

} else { // main
	
	// Redirect to create section
	header('Location: ?create');
	
}

Template::run('admin_footer');

?>
