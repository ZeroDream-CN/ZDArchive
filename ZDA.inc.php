<?php
class ZDArchive {
	
	/*
	*
	*  创建字典
	*
	*/
	private function createDict($sc, $data = '', &$dict)
	{
		if(isset($sc['k'])) {
			$dict[$sc['k']] = $data;
		} else {
			$this->createDict($sc['l'], "{$data}0", $dict);
			$this->createDict($sc['r'], "{$data}1", $dict);
		}
	}
	
	/*
	*
	*  初始化哈夫曼树
	*
	*/
	private function createHuffmanTree($data)
	{
		$hc = count_chars($data, 1);
		$ht = [];
		foreach ($hc as $k => $v) {
			$ht[] = [
				'k' => chr($k),
				'v' => $v,
				'l' => NULL,
				'r' => NULL,
			];
		}
		return $ht;
	}
	
	/*
	*
	*  建立哈夫曼树
	*
	*/
	private function buildTree($tree)
	{
		$count = count($tree);
		for($i = 0;$i !== $count - 1;$i++) {
			uasort($tree, function($a, $b) {
				if($a['v'] === $b['v']) {
					return 0;
				}
				return $a['v'] < $b['v'] ? -1 : 1;
			});
			$a = array_shift($tree);
			$b = array_shift($tree);
			$tree[] = [
				'v' => $a['v'] + $b['v'],
				'l' => $b,
				'r' => $a,
			];
		}
		return $tree;
	}
	
	/*
	*
	*  压缩文件
	*
	*/
	public function compress($fileData, $outputFile = '', $multiple = false, $encryptKey = '', $encryptMode = 'AES-256-CBC')
	{
		$dict   = [];
		$ht     = $this->createHuffmanTree($fileData);
		$ht     = $this->buildTree($ht);
		$prefix = empty($encryptKey) ? "ZDA:NOR|" : "ZDA:AES,{$encryptMode}|";
		$root   = current($ht);
		
		$this->createDict($root, '', $dict);
		$dtxt   = gzcompress(serialize($dict), 9);
		$header = pack('VV', strlen($dtxt), strlen($fileData));
		$buff   = '';
		$data   = '';
		$i      = 0;
		
		if(!empty($encryptKey)) {
			$multiple = false;
		}
		
		if($multiple) {
			if(empty($outputFile)) {
				throw new Exception("Output file cannot be empty when multiple mode On");
			}
			$fh = fopen($outputFile, "w+");
			fwrite($fh, $prefix . $header . $dtxt);
		} else {
			$data .= $header . $dtxt;
		}
		
		while(isset($fileData[$i])) {
			$buf .= $dict[$fileData[$i]];
			while(isset($buf[7])) {
				$chr = chr(bindec(substr($buf, 0, 8)));
				$buf = substr($buf, 8);
				$multiple ? fwrite($fh, $chr) : $data .= $chr;
			}
			$i++;
		}
		
		if(!empty($buf)) {
			$chr = chr(bindec(str_pad($buf, 8, '0')));
			$multiple ? fwrite($fh, $chr) : $data .= $chr;
		}
		
		if(!empty($encryptKey)) {
			$data = openssl_encrypt($data, $encryptMode, hash('sha256', $encryptKey, true), OPENSSL_RAW_DATA, substr(md5($encryptKey), 0, 16));
		}
		
		if(!$multiple && !empty($outputFile)) {
			file_put_contents($outputFile, $prefix . "gz:" . gzcompress($data));
		}
		
		return $multiple ? fclose($fh) : $prefix . "gz:" . gzcompress($data, 9);
	}
	
	
	/*
	*
	*  解压文件
	*
	*/
	public function decompress($data, $outputFile = '', $encryptKey = '')
	{
		if(substr($data, 0, 4) == "ZDA:") {
			$type = substr($data, 4, 3);
			switch($type) {
				case "NOR":
					$data = substr($data, 8, strlen($data) - 8);
					if(substr($data, 0, 3) == "gz:") {
						$data = gzuncompress(substr($data, 3));
					}
					break;
				case "AES":
					if(empty($encryptKey)) {
						echo "Enter the password: ";
						$fp      = fopen("php://stdin", "r");
						$encryptKey  = rtrim(fgets($fp, 1024));
					}
					$encMode = substr($data, 8);
					$encMode = substr($encMode, 0, strpos($encMode, "|"));
					$data    = substr($data, strpos($data, "|") + 1);
					$data    = openssl_decrypt($data, $encMode, hash('sha256', $encryptKey, true), OPENSSL_RAW_DATA|OPENSSL_ZERO_PADDING, substr(md5($encryptKey), 0, 16));
					if(substr($data, 0, 3) == "gz:") {
						$data = gzuncompress(substr($data, 3));
					}
					break;
				default:
					throw new Exception("Not a ZDArchive file!");
			}
			
			$header = unpack('VdictLen/VdataLen', $data);
			$dict   = array_flip(unserialize(gzuncompress(substr($data, 8, $header['dictLen']))));
			$bin    = substr($data, 8 + $header['dictLen']);
			$dl     = 0;
			$i      = 0;
			while($bin[$i] !== null && $dl !== $header['dataLen']) {
				$bins = str_pad(decbin(ord($bin[$i])), 8, '0', STR_PAD_LEFT);
				for($s = 0; $s !== 8; $s++) {
					$k .= $bins[$s];
					if($dict[$k] !== null) {
						$out .= $dict[$k];
						$k    = '';
						$dl++;
						if($dl === $header['dataLen']) {
							break;
						}
					}
				}
				$i++;
			}
			return !empty($outputFile) ? file_put_contents($outputFile, $out) : $out;
		} else {
			throw new Exception("Not a ZDArchive file");
		}
	}
}
