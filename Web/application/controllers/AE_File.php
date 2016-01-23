<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class AE_File extends Burge_CMF_Controller {

	function __construct()
	{
		parent::__construct();

		$this->load->model("file_manager_model");
	}

	public function index()
	{
		
		$this->data['message']=get_message();

		$this->lang->load('ae_file',$this->selected_lang);
	
		$this->data['lang_pages']=get_lang_pages(get_link("admin_file",TRUE));
		$this->data['header_title']=$this->lang->line("files");
		
		$this->send_admin_output("file");
	
		return;		
	}

	public function inline()
	{
		$this->load->library('parser');
		//$this->data[]
		
		$this->parser->parse($this->get_admin_view_file("file_inline"),$this->data);
		
	}

	public function conf()
	{
		echo $this->file_manager_model->get_conf();

		return;
	}

	public function lang($lang)
	{
		echo file_get_contents(SCRIPTS_DIR."/roxy/lang/".$lang.".json");

		return;
	}

	public function image($link1,$link2="",$link3="")
	{
		$path=SCRIPTS_DIR."/roxy/images/".$link1;
		if($link2)
			$path.="/".$link2;
		if($link3)
			$path.="/".$link3;
		
		$extension=pathinfo($path, PATHINFO_EXTENSION);
		$this->output->set_content_type($extension);
		$this->output->set_output(file_get_contents($path));

		return;
	}

	public function action($action)
	{
		$this->file_manager_model->initialize_roxy();

		switch($action)
		{
			case 'dirtree':
				return $this->dirtree();

			case 'fileslist':
				return $this->fileslist();

			case 'thumb':
				return $this->thumb();

			case 'createdir':
				return $this->createdir();
				
			case 'deletedir':
				return $this->deletedir();
		}
	}

	private function deletedir()
	{
		$path = trim(empty($_GET['d'])?'':$_GET['d']);
		verifyPath($path);

		if(is_dir(fixPath($path))){
		  if(fixPath($path.'/') == fixPath(getFilesPath().'/'))
		    echo getErrorRes(t('E_CannotDeleteRoot'));
		  elseif(count(glob(fixPath($path)."/*")))
		    echo getErrorRes(t('E_DeleteNonEmpty'));
		  elseif(rmdir(fixPath($path)))
		    echo getSuccessRes();
		  else
		    echo getErrorRes(t('E_CannotDeleteDir').' '.basename($path));
		}
		else
		  echo getErrorRes(t('E_DeleteDirInvalidPath').' '.$path);

		return;
	}

	private function createdir()
	{
		$path = trim(empty($_POST['d'])?'':$_POST['d']);
		$name = trim(empty($_POST['n'])?'':$_POST['n']);
		verifyPath($path);

		if(is_dir(fixPath($path))){
		  if(mkdir(fixPath($path).'/'.$name, octdec(DIRPERMISSIONS)))
		    echo getSuccessRes();
		  else
		    echo getErrorRes(t('E_CreateDirFailed').' '.basename($path));
		}
		else
		  echo  getErrorRes(t('E_CreateDirInvalidPath'));

		return;
	}

	private function thumb()
	{
		header("Pragma: cache");
		header("Cache-Control: max-age=3600");

		$path = urldecode(empty($_GET['f'])?'':$_GET['f']);
		verifyPath($path);

		@chmod(fixPath(dirname($path)), octdec(DIRPERMISSIONS));
		@chmod(fixPath($path), octdec(FILEPERMISSIONS));

		$w = intval(empty($_GET['width'])?'100':$_GET['width']);
		$h = intval(empty($_GET['height'])?'0':$_GET['height']);

		header('Content-type: '.RoxyFile::GetMIMEType(basename($path)));
		if($w && $h)
		  RoxyImage::CropCenter(fixPath($path), null, $w, $h);
		else 
		  RoxyImage::Resize(fixPath($path), null, $w, $h);
	}

	private function fileslist()
	{		
		$path = (empty($_POST['d'])? getFilesPath(): $_POST['d']);
		$type = (empty($_POST['type'])?'':strtolower($_POST['type']));
		if($type != 'image' && $type != 'flash')
		  $type = '';
		verifyPath($path);

		$files = listDirectory(fixPath($path), 0);
		natcasesort($files);
		$str = '';
		echo '[';
		foreach ($files as $f){
		  $fullPath = $path.'/'.$f;
		  if(!is_file(fixPath($fullPath)) || ($type == 'image' && !RoxyFile::IsImage($f)) || ($type == 'flash' && !RoxyFile::IsFlash($f)))
		    continue;
		  $size = filesize(fixPath($fullPath));
		  $time = filemtime(fixPath($fullPath));
		  $w = 0;
		  $h = 0;
		  if(RoxyFile::IsImage($f)){
		    $tmp = @getimagesize(fixPath($fullPath));
		    if($tmp){
		      $w = $tmp[0];
		      $h = $tmp[1];
		    }
		  }
		  $str .= '{"p":"'.mb_ereg_replace('"', '\\"', $fullPath).'","s":"'.$size.'","t":"'.$time.'","w":"'.$w.'","h":"'.$h.'"},';
		}
		$str = mb_substr($str, 0, -1);
		echo $str;
		echo ']';
	}

	private function dirtree()
	{	
		$type = (empty($_GET['type'])?'':strtolower($_GET['type']));
		if($type != 'image' && $type != 'flash')
		  $type = '';

		echo "[\n";
		$tmp = getFilesNumber(fixPath(getFilesPath()), $type);
		echo '{"p":"'.  mb_ereg_replace('"', '\\"', getFilesPath()).'","f":"'.$tmp['files'].'","d":"'.$tmp['dirs'].'"}';
		GetDirs(getFilesPath(), $type);
		echo "\n]";
	}

}