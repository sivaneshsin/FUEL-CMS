<?php
require_once(FUEL_PATH.'/libraries/Fuel_base_controller.php');
class Backup extends Fuel_base_controller {
	
	public $nav_selected = 'tools/backup';
	public $view_location = 'backup';
	
	function __construct()
	{
		parent::__construct();
		$this->config->load('backup');
		$this->load->language('backup');
	}
	
	function index()
	{
		$this->_validate_user('tools/backup');
		echo $this->fuel->backup->config('db_backup_path');
		exit();
		$backup_config = $this->config->item('backup');
		$download_path = $backup_config['db_backup_path'];
		$is_writable = is_writable($download_path);
		if ($post = $this->input->post('action'))
		{
			// Load the DB utility class
			$this->load->dbutil();
			
			// Backup your entire database and assign it to a variable
			//$config = array('newline' => "\r", 'format' => 'zip');
			
			// need to do text here to make some fixes
			$db_back_prefs = $backup_config['db_backup_prefs'];
			$db_back_prefs['format'] = 'txt';
			$backup =& $this->dbutil->backup($db_back_prefs); 
			
			//fixes to work with PHPMYAdmin
			// $backup = str_replace('\\\t', "\t",	$backup);
			// $backup = str_replace('\\\n', '\n', $backup);
			// $backup = str_replace("\\'", "''", $backup);
			// $backup = str_replace('\\\\', '', $backup);
			
			// load the file helper and write the file to your server
			$this->load->helper('file');
			$this->load->library('zip');
			
			$backup_date = date($backup_config['backup_file_date_format']);
			if ($backup_config['backup_file_prefix'] == 'AUTO')
			{
				$this->load->helper('url');
				$backup_config['backup_file_prefix'] = url_title($this->config->item('site_name', FUEL_FOLDER), '_', TRUE);
			}
			
			$filename = $backup_file_prefix.'_'.$backup_date.'.sql';
			
			if (!empty($backup_config['backup_zip']))
			{
				$this->load->library('zip');
				$this->zip->add_data($filename, $backup);

				// include assets folder
				if (!empty($_POST['include_assets']))
				{
					$this->zip->read_dir(assets_server_path());
				}
				$download_file = $download_path.$filename.'.zip';
				
				// write the zip file to a folder on your server. 
				$this->zip->archive($download_file); 

				// download the file to your desktop. 
				$this->zip->download($filename.'.zip');

				$msg = lang('data_backup');
				
			}
			else
			{
				$download_file = $download_path.$filename;
				write_file($download_file, $backup);
			}
			
			$msg = lang('data_backup');
			$this->logs_model->logit($msg);
		}
		else 
		{
			$vars['download_path'] = $download_path;
			$vars['is_writable'] = $is_writable;
			$vars['backup_assets'] = $backup_config['backup_assets'];
			
			$crumbs = array('tools' => lang('section_tools'), lang('module_backup'));
			$this->fuel->admin->set_breadcrumb($crumbs, 'ico_tools_backup');
			$this->fuel->admin->render('backup', $vars, Fuel_admin::DISPLAY_NO_ACTION);
		}
	}
}
/* End of file backup.php */
/* Location: ./fuel/modules/backup/controllers/backup.php */