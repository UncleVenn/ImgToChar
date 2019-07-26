<?php
require 'GifFrameExtractor.php';
require 'GIFEncoder.class.php';
class ImageChar {
	public $font;

	public $FILES;

	public $str = 'swou+-=.e';

	public function __construct($file, $str='') {
		session_start();
		$this->font = $_SERVER['CONTEXT_DOCUMENT_ROOT'] . '\imgtochar\simsun.ttc';
		$this->FILES = $file;
		$str != '' && $this->str = $str;
		$this->create_img();
	}

	public function getImg($imgName) {
		$arr = getimagesize($imgName);
		if ($arr[2] == 1) {
			return $this->imageofgif($imgName);
		} else if ($arr[2] == 2) {
			return imagecreatefromjpeg($imgName);
		} else if ($arr[2] == 3) {
			return imagecreatefrompng($imgName);
		} else {
			return false;
		}
	}

	public function imageofgif($name) {
		$gfe = new GifFrameExtractor();
		$gfe->extract($name);
		$frameImages = $gfe->getFrameImages(); //每一帧资源集
		$frameDurations = $gfe->getFrameDurations(); //每一帧持续时间
		return [$frameImages, $frameDurations];
	}

	public function output($imgName) {
		/*
		 *	参数说明：
		 *	imageName	图像名称
		 *	echoText	功能：设置是否保存为txt文件
		 */
		$im = $this->getImg($imgName);
		if (is_array($im)) {
			return $this->gif_char($im);
		} else {
			return $this->img_char($im, 8, 5);
		}

	}

	public function img_char($im, $x_z, $y_z) {
		$output = "";
		$str = $this->ch2arr($this->str); //填充字符 转化为UTF-8的数组
		$x = imagesx($im);
		$y = imagesy($im);
		for ($j = 0; $j < $y; $j += $x_z) {
			for ($i = 0; $i < $x; $i += $y_z) {
				$colors = imagecolorsforindex($im, imagecolorat($im, $i, $j)); //获取像素块的代表点RGB信息
				$greyness = ((76 * $colors["red"] + 150 * $colors["green"] + 30 * $colors["blue"] + 128) >> 8) / 255; //灰度值计算公式
				$offset = (int) ceil($greyness * (count($str) - 1)); //根据灰度值选择合适的字符
				if ($offset == (count($str) - 1)) {
					$output .= "|";
				} else {
					$output .= $str[$offset];
				}
			}
			$output .= "*";
		}
		imagedestroy($im);
		//输出到文本(可选)
		// if ($echoText) {
		// 	$output = str_replace("<br/>", PHP_EOL, $output);
		// 	$output = str_replace("&nbsp;",' ', $output);
		// 	// file_put_contents("img.txt", $output);
		// 	header('Content-type:text/plain;charset=utf-8');
		// 	header('Content-Disposition:attachment;filename=img.txt');
		// 	// unlink("img.txt");
		// 	die;
		// }
		return ['text' => $output, 'x' => $x, 'y' => $y]; //默认输出到网页
	}

	public function gif_char($im) {
		foreach ($im[0] as $key => $value) {
			$data = $this->img_char($value, 4, 2.5);
			$this->gif_img($data);
			$rec[] = ob_get_contents();
			ob_clean();
		}
		$gif = new GIFEncoder($rec, $im[1], 0, 2, 0, 0, 0, "bin");
		$file = $gif->GetAnimation();
		return ['name'=>$this->save($file)];
	}

	public function save($file) {
		$name = explode('.', $this->FILES);
		$name = '.' . $name[1] . '_char.gif';
		$res = fopen($name, 'a');
		fwrite($res, $file);
		fclose($res);
		return $name;
	}

	public function gif_img($res) {
		ob_start();
		$im = imagecreate($res['x'] * 4, $res['y'] * 4);
		$white = imagecolorallocate($im, 255, 255, 255);
		$black = imagecolorallocate($im, 0, 0, 0);
		imagefill($im, 0, 0, $white);
		$arr = explode('*', $res['text']);
		$j = 0;
		for ($i = 0; $i < count($arr); $i++) {
			imagettftext($im, 15, 0, 0, $j, $black, $this->font, str_replace('|', ' ', $arr[$i]));
			$j = $j + 17;
		}
		// $name = uniqid();
		imagegif($im);
		// imagedestroy($im);
		// return './img/'.$name.'.gif';
	}
	// public function create_name() {
	// 	return uniqid();
	// }
	//图片上传
	// public function upload_img() {
	// 	$file = $this->FILES;
	// 	$allowedExts = array("gif", "jpeg", "jpg", "png");
	// 	$temp = explode(".", $file["img"]["name"]);
	// 	$extension = end($temp); // 获取文件后缀名
	// 	if ((($file["img"]["type"] == "image/gif")
	// 		|| ($file["img"]["type"] == "image/jpeg")
	// 		|| ($file["img"]["type"] == "image/jpg")
	// 		|| ($file["img"]["type"] == "image/pjpeg")
	// 		|| ($file["img"]["type"] == "image/x-png")
	// 		|| ($file["img"]["type"] == "image/png"))
	// 		&& in_array($extension, $allowedExts)) {
	// 		if ($file["img"]["error"] > 0) {
	// 			return false;
	// 		} else {
	// 			if (file_exists("./img/" . $file["img"]["name"])) {
	// 				return "./img/" . $file["img"]["name"];
	// 			} else {
	// 				$name = $this->create_name();
	// 				move_uploaded_file($file["img"]["tmp_name"], "./img/" . $name . '.png');
	// 				return "./img/" . $name . '.png';
	// 			}
	// 		}
	// 	} else {
	// 		return false;
	// 	}
	// }

	public function create_img() {
		$img = $this->FILES;
		$res = $this->output($img);
		if(isset($res['name'])){
			echo json_encode($res['name']);die;
		}
		$im = imagecreate($res['x'] * 2, $res['y'] * 2);
		$white = imagecolorallocate($im, 255, 255, 255);
		$black = imagecolorallocate($im, 0, 0, 0);
		imagefill($im, 0, 0, $white);
		$arr = explode('*', $res['text']);
		$j = 0;
		for ($i = 0; $i < count($arr); $i++) {
			imagettftext($im, 15, 0, 0, $j, $black, $this->font, str_replace('|', ' ', $arr[$i]));
			$j = $j + 17;
		}
		$name = explode('.', $img);
		imagepng($im, '.' . $name[1] . '_char.png');
		imagedestroy($im);
		echo json_encode('.' . $name[1] . '_char.png');
	}

	public function ch2arr($str) {
		$length = mb_strlen($str, 'utf-8');
		$array = [];
		for ($i = 0; $i < $length; $i++) {
			$array[] = mb_substr($str, $i, 1, 'utf-8');
		}

		return $array;
	}
}
?>