<?php


class simple_url_filter
{
    private $_eip = 0;
    private $_input_url = "";
    private $_input_url_len = 0;
    
    private $_scheme = "";
    private $_scheme_delimiter = "";
    private $_host = "";
    private $_host_array = array();
    private $_uri = "";
    private $_query = "";   //因具体需求，暂时不对query_string进行k-v解析以节约性能
    
    private $_ALLOW_DOMAINS = array(
        "*"
    );
    
    private $_DISALLOW_DOMAINS = array(
        
    );
    
    private $_DISALLOW_URI = array(
        
    );
    
    private $_ALLOW_SCHEME = array(
        "http", "https", "ftp", ""
    );   //注意，这项留空的含义为不限制协议。请务必保留空字符串，否则处理相对路径或协议继承时，会出现问题。
    

    public function safeURL($url)
    {
        if (empty($url) || strlen(trim($url)) < 1) return " ";
        $this->_input_url = trim((string)$url);
        $this->_input_url_len = strlen($this->_input_url);
        $this->_first();
        
        $this->_parserURL();
        

        //进行scheme白名单检测
        if (!in_array($this->_scheme, $this->_ALLOW_SCHEME)) return " ";
        
        //进行HOST黑名单检测
        //if ($this->_in_host($this->_host_array, $this->_DISALLOW_DOMAINS)) return " ";
        
        //进行HOST白名单检测
        //if (!$this->_in_host($this->_host_array, $this->_ALLOW_DOMAINS)) return " ";
        
        //进行URI黑名单检测
        //if ($this->_match_uri($this->_uri, $this->_DISALLOW_URI)) return " ";

        
        return $this->_toString();
        
    }
        
    
    private function _match_uri($needle, $haystack)
    {
        
    }
    
    private function _in_host($needle, $haystack)
    {
        
    }
    
    private function _first()
    {
        return $this->_index(0);
    }
    
    private function _last()
    {
        return $this->_index(($this->_input_url_len - 1));
    }
    
    private function _index($i=null)
    {
        if (is_numeric($i))
        {
            if ($i >= 0 && $i < $this->_input_url_len)
            {
                $this->_eip = $i;
                return $this->_input_url{$i};
            }else{
                return null;
            }
        }else{
            return $this->_eip;
        }
    }
    
    private function _current()
    {
        return $this->_input_url{$this->_eip};
    }
    
    private function _next()
    {
        if ($this->_eip + 1 >= $this->_input_url_len) return null;
        
        return $this->_input_url{$this->_eip++};
    }
    
    private function _pre()
    {
        if ($this->_eip < 1) return null;
        if ($this->_eip + 1 >= $this->_input_url_len) return null;
        
        return $this->_input_url{$this->_eip--};
    }
    
    private function _substr($start=0, $length=null)
    {
        if (!is_numeric($length)) return substr($this->_input_url, $start);
        
        return substr($this->_input_url, $start, $length);
    }
    
    
    private function _parserURL()
    {
        //初始化变量
        $this->_eip = 0;
        $this->_scheme = "";
        $this->_scheme_delimiter = "";
        $this->_host = "";
        $this->_host_array = array();
        $this->_uri = "";
        $this->_query = "";
        
        $this->_getScheme();       
        $this->_getHost();
        $this->_host2Arr();
        $this->_getUri();
        $this->_getQuery();        
    }
    
    private function _host2Arr()
    {
        $this->_host_array = explode(".", $this->_host);
        return true;
    }
    
    private function _getScheme()
    {
        $this->_scheme = "";
        $this->_scheme_delimiter = "";
        $chars = "";
        
        if ($this->_next() == "/" && $this->_next() == "/")
        {
            //单独判断是否为双斜杠打头的scheme继承写法
            $this->_scheme_delimiter = "//";
            return true;
            
        }else{
            $this->_pre();
            $this->_pre();
        }
        
        do {
            $char = $this->_next();
            if ($char == ":")
            {
                $this->_scheme = $chars;
                $this->_scheme_delimiter = $char;
                
                if ($this->_next() == "/")
                {
                    $this->_scheme_delimiter .= "/";
                }else{
                    $this->_pre();
                }
                
                if ($this->_next() == "/")
                {
                    $this->_scheme_delimiter .= "/";
                }else{
                    $this->_pre();
                }
                
                return true;
            }else{
                $chars .= $char;
            }
        }while($char !== null);
        
        //如果没有找到scheme_delimiter，则认为该URL没有指定scheme。
        $this->_first();
        
        return true;
    }
    
    private function _getHost()
    {
        $inHost = false;
        $this->_host = "";
        $chars = "";
        
        do {
            $char = $this->_next();
            if ($char == "/" && $inHost == false)
            {
                //排除最开始的双斜杠，如果
                $_next = $this->_next();
                if ($_next == "/")
                {
                    $inHost = true;
                    continue;
                }else{
                    //否则，认为是相对路径，回到上两级，停止host的遍历
                    $this->_pre();
                    $this->_pre();
                    break;
                }
            }
            
            if (($char == "/" || $char == "#" || $char == "?") && $inHost == true)
            {
                //这时，认为host部分已经取值完毕。这里额外兼容#?两个特殊符号，在部分浏览器中，在host后紧跟这两个符号的，也认为是host结尾。
                $this->_pre();
                break;
            }
            
            $chars .= $char;
            
        }while($char !== null);
        
        $this->_host = $chars;
        
        return true;
    }
    
    private function _getUri()
    {
        $this->_uri = "";
        $chars = "";
        $lastDirIndex = 0;
        $endIndex = 0;
        
        do {
            $char = $this->_next();

            if ($char == "/") $lastDirIndex = $this->_index();
            
            if ($char == "#" || $char == "?")
            {
                //这时，认为uri部分已经取值完毕。这里额外兼容#?两个特殊符号，在部分浏览器中，在host后紧跟这两个符号的，也认为是host结尾。
                $this->_pre();
                $endIndex = $this->_index();
                break;
            }
        
            $chars .= $char;
        
        }while($char !== null);
        
        //检查是否需要回退，特指遇到query_string时
        if ($endIndex > 0)
        {
            $chars = substr($chars, 0, $endIndex);
        }
        
        $this->_uri = $chars;
        
        return true;
    }
    
    private function _getQuery()
    {
        //注意，这里把锚文本标记也记为query（即#及之后部分）
        $this->_query = "";
        
        $query = $this->_substr($this->_index());
        
        if (!empty($query)) $this->_query = $query;
        
        return true;       
    }
        
    private function _toString()
    {
        $domain = join(".", $this->_host_array);
        
        $output = $this->_scheme;
        
        //如果domain为空，则scheme_delimiter需要减少一个/
        
        if (empty($domain))
        {
            $output .= substr($this->_scheme_delimiter, 0, -1);
        }else{
            $output .= $this->_scheme_delimiter;
        }
        
        $output .= $domain . $this->_uri . $this->_query;

        return $output;
    }
}