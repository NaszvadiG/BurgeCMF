<?php
//It's a log manager which insists on events 
//system logs are gathered by log system of CI
//we added it since we need an event log manager with ability to retreive 
//different types of reports from data

class Log_manager_model extends CI_Model
{
	private $logger;

	private $log_dir=LOG_DIR;
	private $log_prefix=LOGS_PREFIX;
	private $date_function=DATE_FUNCTION;
	private $log_extension="txt";

	private $today_day;
	private $today_month;
	private $today_year;

	private $event_types=array(
		"UNKOWN"						=>0
		,"NEW_VISIT"				=>1

		,"CMF_INSTALL" 			=>101
		,"CMF_UNINSTALL" 			=>102

		,"MODULE_INSTALL"			=>201
		,"MODULE_UNINSTALL"		=>202
		,"MODULE_ADD"				=>203
		,"MODULE_ADD_NAME"		=>204

		,"ACCESS_ALLOW_USER"		=>301
		,"ACCESS_UNSET_USER"		=>302
		,"ACCESS_UNSET_MODULE"	=>303
		,"ACCESS_CHECK"			=>304

		,"USER_ADD"					=>401
		,"USER_DELETE"				=>402
		,"USER_CHANGE_PASS"		=>403
		,"USER_LOGIN"				=>404
		,"USER_LOGOUT"				=>405
		);

	public function __construct()
	{
		parent::__construct();

		$date_function=$this->date_function;

		list($y,$m,$d)=explode("-", $date_function("Y-m-d"));
		$this->today_year=$y;
		$this->today_month=$m;
		$this->today_day=$d;

		$this->initialize_logger();
      
      $this->log_this_visit();

      return;
   }

   private function initialize_logger()
   {
   	$this->load->library("core/logger");
		
   	$options=array(
			'extension'      => 'txt',
			'dateFormat'     => 'Y/m/d H:i:s',
			'filename'       => $this->get_today_log_file_name(),
			'prefix'         => '',
			'logFormat'      => FALSE,
			'appendContext'  => TRUE,
    	);
   	
		$this->logger=new Logger($this->log_dir,LogLevel::DEBUG,$options);

   	//$this->options['prefix'].$date_function('Y-m-d').'.'.$this->options['extension'];
   }

   private function get_today_log_file_name()
   {
   	return $this->get_log_file_name($this->today_year,$this->today_month,$this->today_day);
   }

   private function get_log_file_name($y,$m,$d)
   {
   	return $this->log_prefix.$y."-".$m."-".$d.".".$this->log_extension;
   }

   private function log_this_visit()
   {
   	$CI=&get_instance();
     	$event_props=array(
      	"ip"			=> $_SERVER['REMOTE_ADDR']
      	,"url"		=> $CI->uri->uri_string
      );
      if(isset($_SERVER['HTTP_USER_AGENT']))
      	$event_props["ua"]=$_SERVER['HTTP_USER_AGENT'];
      if(isset($_SERVER['HTTP_REFERER']))
      	$event_props["referer"]=$_SERVER['HTTP_REFERER'];

		$this->info("NEW_VISIT",$event_props);

		return;
	}

	public function install()
	{
		$this->load->model("module_manager_model");

		$this->module_manager_model->add_module("log","log_manager");
		$this->module_manager_model->add_module_names_from_lang_file("log");
		
		return;
	}

	public function uninstall()
	{
		return;
	}

	public function get_dashbord_info()
	{
		return "";
		$CI=& get_instance();
		$lang=$CI->language->get();
		$CI->lang->load('admin_user',$lang);		
		
		$data=array();
		$data['users']=$this->get_all_users_info();
		$data['total_text']=$CI->lang->line("total");
		
		$CI->load->library('parser');
		$ret=$CI->parser->parse($CI->get_admin_view_file("user_dashboard"),$data,TRUE);
		
		return $ret;		
	}

	public function emergency($message, array $context = array())
	{
		$this->log(LogLevel::EMERGENCY, $message, $context);
	}
    
	public function alert($message, array $context = array())
	{
		$this->log(LogLevel::ALERT, $message, $context);
	}

	public function critical($message, array $context = array())
	{
		$this->log(LogLevel::CRITICAL, $message, $context);
	}

	public function error($message, array $context = array())
	{
		$this->log(LogLevel::ERROR, $message, $context);
	}

	public function warning($message, array $context = array())
	{
		$this->log(LogLevel::WARNING, $message, $context);
	}

	public function notice($message, array $context = array())
	{
		$this->log(LogLevel::NOTICE, $message, $context);
	}

	public function info($message, array $context = array())
	{
		$this->log(LogLevel::INFO, $message, $context);
	}

	public function debug($message, array $context = array())
	{
		$this->log(LogLevel::DEBUG, $message, $context);
	}

	//level: level of log
	//message: its message, but we ask people to say the type of event
	//context: other parameters
	private function log($level,$message,$context)
	{
		$event_type=$message;
		if(!isset($this->event_types[$event_type]))
			$event_type="UNKOWN";

		$context["event_name"]=$message;
		$context["event_id"]=$this->event_types[$event_type];

		$CI=&get_instance();
		if(isset($CI->in_admin_env) && $CI->in_admin_env)
		{
			$context["active_user_id"]=$CI->user->get_id();
			$context["active_user_email"]=$CI->user->get_email();
		}

		$this->logger->log($level,$event_type,$context);
	}
}